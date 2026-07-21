<?php

namespace OursBlanc\Xms\Blocks;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BlockValidator
{
    public function __construct(protected BlockRegistry $registry) {}

    /**
     * Validate a single block entry (uuid, type, data) against its block type's rules.
     *
     * @param  array<string, mixed>  $block
     *
     * @throws ValidationException
     */
    public function validateBlock(array $block): void
    {
        $type = $block['type'] ?? null;

        if (! is_string($type) || ! $this->registry->has($type)) {
            throw ValidationException::withMessages([
                'type' => "Unknown block type: \"{$type}\".",
            ]);
        }

        $blockClass = $this->registry->find($type);

        Validator::make(
            $block['data'] ?? [],
            $blockClass::rules(),
        )->validate();
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
            try {
                $this->validateBlock($block);
            } catch (ValidationException $exception) {
                $prefixed = [];

                foreach ($exception->errors() as $field => $messages) {
                    $prefixed["blocks.{$index}.data.{$field}"] = $messages;
                }

                throw ValidationException::withMessages($prefixed);
            }
        }
    }
}
