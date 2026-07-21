<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Blocks\BlockValidator;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\BlockNormalizer;
use RuntimeException;

class PatchBlocksTool extends AbstractXmsTool
{
    protected string $name = 'patch_blocks';

    protected string $description = 'Apply targeted operations to a page\'s blocks without resending the whole '.
        'page: insert (position, block), update (uuid, data), remove (uuid), or move (uuid, position). '.
        'Operations are applied in order.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'operations' => $schema->array()->items(
                $schema->object([
                    'op' => $schema->string()->enum(['insert', 'update', 'remove', 'move'])->required(),
                    'position' => $schema->integer()->description('Target index. Used by insert and move.'),
                    'uuid' => $schema->string()->description('Target block. Used by update, remove, and move.'),
                    'block' => $schema->object([])->description('New block ({type, data}). Used by insert.'),
                    'data' => $schema->object([])->description('Fields to merge into the block\'s data. Used by update.'),
                ])
            )->min(1)->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate([
            'id' => 'required|integer',
            'operations' => 'required|array|min:1',
            'operations.*.op' => 'required|in:insert,update,remove,move',
        ]);

        $page = Page::find($data['id']);

        if (! $page) {
            return Response::error("No page with id [{$data['id']}].");
        }

        $blocks = $page->blocks ?? [];

        // See CreatePageTool: validate() only returns keys with an explicit
        // rule, which would strip each operation's `uuid`/`data`/`block`/
        // `position` (only `op` has a rule). Use the raw arguments instead.
        $operations = $request->get('operations');

        try {
            foreach ($operations as $index => $operation) {
                $blocks = match ($operation['op']) {
                    'insert' => $this->applyInsert($blocks, $operation, $index),
                    'update' => $this->applyUpdate($blocks, $operation, $index),
                    'remove' => $this->applyRemove($blocks, $operation, $index),
                    'move' => $this->applyMove($blocks, $operation, $index),
                };
            }
        } catch (RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        try {
            app(BlockValidator::class)->validateBlocks($blocks);
        } catch (ValidationException $e) {
            return $this->blockValidationError($e, $blocks);
        }

        $page->update(['blocks' => $blocks]);

        return Response::structured(['id' => $page->id, 'blocks' => $page->fresh()->blocks]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $operation
     * @return array<int, array<string, mixed>>
     */
    protected function applyInsert(array $blocks, array $operation, int $index): array
    {
        if (! isset($operation['block']['type'])) {
            throw new RuntimeException("operations[{$index}]: insert requires a `block` with a `type`.");
        }

        $newBlock = BlockNormalizer::normalize([$operation['block']])[0];
        $position = min($operation['position'] ?? count($blocks), count($blocks));

        array_splice($blocks, $position, 0, [$newBlock]);

        return $blocks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $operation
     * @return array<int, array<string, mixed>>
     */
    protected function applyUpdate(array $blocks, array $operation, int $index): array
    {
        $uuid = $operation['uuid'] ?? null;

        if (! $uuid) {
            throw new RuntimeException("operations[{$index}]: update requires a `uuid`.");
        }

        $found = false;

        foreach ($blocks as &$block) {
            if ($block['uuid'] === $uuid) {
                $block['data'] = array_merge($block['data'] ?? [], $operation['data'] ?? []);
                $found = true;
                break;
            }
        }
        unset($block);

        if (! $found) {
            throw new RuntimeException("operations[{$index}]: no block with uuid [{$uuid}].");
        }

        return $blocks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $operation
     * @return array<int, array<string, mixed>>
     */
    protected function applyRemove(array $blocks, array $operation, int $index): array
    {
        $uuid = $operation['uuid'] ?? null;

        if (! $uuid) {
            throw new RuntimeException("operations[{$index}]: remove requires a `uuid`.");
        }

        $filtered = array_values(array_filter($blocks, fn (array $b) => $b['uuid'] !== $uuid));

        if (count($filtered) === count($blocks)) {
            throw new RuntimeException("operations[{$index}]: no block with uuid [{$uuid}].");
        }

        return $filtered;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $operation
     * @return array<int, array<string, mixed>>
     */
    protected function applyMove(array $blocks, array $operation, int $index): array
    {
        $uuid = $operation['uuid'] ?? null;
        $position = $operation['position'] ?? null;

        if (! $uuid || $position === null) {
            throw new RuntimeException("operations[{$index}]: move requires `uuid` and `position`.");
        }

        $currentIndex = collect($blocks)->search(fn (array $b) => $b['uuid'] === $uuid);

        if ($currentIndex === false) {
            throw new RuntimeException("operations[{$index}]: no block with uuid [{$uuid}].");
        }

        $block = $blocks[$currentIndex];
        array_splice($blocks, $currentIndex, 1);
        array_splice($blocks, min($position, count($blocks)), 0, [$block]);

        return $blocks;
    }
}
