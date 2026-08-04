<?php

namespace OursBlanc\Xms\Blocks;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BlockValidator
{
    public function __construct(protected BlockRegistry $registry) {}

    /**
     * Validate a single block entry (uuid, type, data) against its block
     * type's rules, then recurse into any nested-blocks field it declares
     * (see Block::nestedBlockFields()) — a nested block's own `data` is
     * validated against its own type's rules too, not left unchecked.
     *
     * $errorPrefix is prepended to every error key, so a block nested
     * several levels deep still reports a fully-qualified path back to the
     * top-level `blocks` array (see validateNestedPath()).
     *
     * @param  array<string, mixed>  $block
     *
     * @throws ValidationException
     */
    public function validateBlock(array $block, string $errorPrefix = ''): void
    {
        $type = $block['type'] ?? null;

        if (! is_string($type) || ! $this->registry->has($type)) {
            throw ValidationException::withMessages([
                "{$errorPrefix}data.type" => "Unknown block type: \"{$type}\".",
            ]);
        }

        $blockClass = $this->registry->find($type);
        $data = $block['data'] ?? [];

        try {
            Validator::make($data, $blockClass::rules())->validate();
        } catch (ValidationException $exception) {
            $prefixed = [];

            foreach ($exception->errors() as $field => $messages) {
                $prefixed["{$errorPrefix}data.{$field}"] = $messages;
            }

            throw ValidationException::withMessages($prefixed);
        }

        foreach ($blockClass::nestedBlockFields() as $path) {
            $this->validateNestedPath($data, $path, $errorPrefix);
        }
    }

    /**
     * Validate a full blocks array (as stored in the `blocks` column).
     *
     * @param  array<int, array<string, mixed>>  $blocks
     *
     * @throws ValidationException
     */
    public function validateBlocks(array $blocks): void
    {
        foreach ($blocks as $index => $block) {
            $this->validateBlock($block, "blocks.{$index}.");
        }
    }

    /**
     * Walks a `key` or `key.*.subfield` nested-blocks path (same `.*.`
     * repeater-wildcard convention as Block::mediaFields()) and validates
     * every block found there.
     *
     * @param  array<string, mixed>  $data
     */
    protected function validateNestedPath(array $data, string $path, string $errorPrefix): void
    {
        if (! str_contains($path, '.*.')) {
            if (isset($data[$path]) && is_array($data[$path])) {
                foreach ($data[$path] as $i => $block) {
                    if (is_array($block)) {
                        $this->validateBlock($block, "{$errorPrefix}data.{$path}.{$i}.");
                    }
                }
            }

            return;
        }

        [$repeaterKey, $subField] = explode('.*.', $path, 2);

        foreach ($data[$repeaterKey] ?? [] as $i => $item) {
            if (! is_array($item) || ! isset($item[$subField]) || ! is_array($item[$subField])) {
                continue;
            }

            foreach ($item[$subField] as $j => $block) {
                if (is_array($block)) {
                    $this->validateBlock($block, "{$errorPrefix}data.{$repeaterKey}.{$i}.{$subField}.{$j}.");
                }
            }
        }
    }
}
