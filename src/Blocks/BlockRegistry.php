<?php

namespace OursBlanc\Xms\Blocks;

use InvalidArgumentException;

class BlockRegistry
{
    /**
     * @var array<string, class-string<Block>>
     */
    protected array $blocks = [];

    public function register(string $blockClass): static
    {
        if (! is_subclass_of($blockClass, Block::class)) {
            throw new InvalidArgumentException("{$blockClass} must extend ".Block::class);
        }

        $this->blocks[$blockClass::name()] = $blockClass;

        return $this;
    }

    /**
     * @return array<string, class-string<Block>>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * @return class-string<Block>|null
     */
    public function find(string $name): ?string
    {
        return $this->blocks[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->blocks);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function schemas(): array
    {
        $schemas = [];

        foreach ($this->blocks as $name => $blockClass) {
            $schemas[$name] = [
                'name' => $blockClass::name(),
                'label' => $blockClass::label(),
                'description' => $blockClass::description(),
                'schema' => $blockClass::schema(),
                'media_fields' => $blockClass::mediaFields(),
            ];
        }

        return $schemas;
    }
}
