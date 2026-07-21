<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Models\ApiToken;
use OursBlanc\Xms\Models\Page;

abstract class AbstractXmsTool extends Tool
{
    /**
     * @throws AuthorizationException
     */
    protected function authorize(string $ability): void
    {
        $token = app(ApiToken::class);

        if (! $token->can($ability)) {
            throw new AuthorizationException("This token is missing the [{$ability}] ability.");
        }
    }

    /**
     * Slug rules shared by create_page/update_page/translate_page. An empty
     * string is a legitimate slug (the page is the locale's homepage), which
     * Page::SLUG_REGEX itself doesn't match and Laravel's `required` rule
     * would reject — so the format is only checked when non-empty.
     *
     * @return array<int, mixed>
     */
    protected function slugRules(): array
    {
        return [
            'present',
            'string',
            'max:500',
            function (string $attribute, mixed $value, \Closure $fail) {
                if ($value !== '' && preg_match(Page::SLUG_REGEX, (string) $value) !== 1) {
                    $fail('The slug format is invalid.');
                }
            },
        ];
    }

    /**
     * Structured, actionable validation errors: the field-level messages
     * plus the JSON Schema of every block type involved, so the calling AI
     * can self-correct without external documentation.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    protected function blockValidationError(ValidationException $e, array $blocks): Response
    {
        $registry = app(BlockRegistry::class);
        $blockSchemas = [];

        foreach (array_keys($e->errors()) as $key) {
            if (preg_match('/^blocks\.(\d+)\./', $key, $matches) === 1) {
                $type = $blocks[(int) $matches[1]]['type'] ?? null;

                if ($type && $blockClass = $registry->find($type)) {
                    $blockSchemas[$type] = $blockClass::schema();
                }
            }
        }

        return Response::error(json_encode([
            'errors' => $e->errors(),
            'block_schemas' => $blockSchemas,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
