<?php

namespace OursBlanc\Xms\Blocks;

use Filament\Forms\Components\Builder\Block as BuilderBlock;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Str;
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
     * Every registered block, as the Filament `Builder\Block` schema shared
     * by the page-level blocks Builder (PageForm) and any block that nests
     * other blocks inside itself (e.g. Tabbed Showcase's per-tab content).
     *
     * @param  array<int, string>  $except  Block names to leave out — used
     *                                      to stop a block from nesting
     *                                      itself, which would recurse
     *                                      forever while eagerly building
     *                                      the admin form's schema.
     * @return array<int, BuilderBlock>
     */
    public function builderBlocks(array $except = []): array
    {
        return collect($this->blocks)
            ->except($except)
            ->map(fn (string $blockClass, string $name) => BuilderBlock::make($name)
                ->label($blockClass::label())
                ->schema([
                    Hidden::make('uuid')->default(fn () => (string) Str::uuid()),
                    ...$blockClass::fields(),
                ]))
            ->values()
            ->all();
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
