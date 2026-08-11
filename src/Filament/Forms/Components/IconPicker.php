<?php

namespace OursBlanc\Xms\Filament\Forms\Components;

use BladeUI\Icons\Factory;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;

/**
 * A searchable Select over every "regular"-weight Phosphor icon (codeat3/
 * blade-phosphor-icons) — dehydrates to the bare icon slug (e.g.
 * "shopping-cart"), rendered in a block's own view with
 * `@svg('phosphor-'.$icon)`.
 */
class IconPicker extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->options(fn () => static::phosphorIcons());
        $this->searchable();
        $this->native(false);
        $this->allowHtml();
    }

    /**
     * @return array<string, string>
     */
    public static function phosphorIcons(): array
    {
        static $icons = null;

        if ($icons !== null) {
            return $icons;
        }

        $path = app(Factory::class)->all()['phosphor-icons']['paths'][0] ?? null;

        if (! $path || ! is_dir($path)) {
            return $icons = [];
        }

        return $icons = collect(glob($path.'/*.svg'))
            ->map(fn (string $file) => pathinfo($file, PATHINFO_FILENAME))
            // Every icon also ships bold/thin/light/duotone/fill variants as
            // their own separate files — only the bare (regular) name is
            // offered here, one entry per icon, to keep the list to a
            // browsable ~1,500 rather than ~9,000.
            ->reject(fn (string $name) => preg_match('/-(bold|thin|light|duotone|fill)$/', $name) === 1)
            ->sort()
            ->mapWithKeys(fn (string $name) => [$name => static::optionLabel($name)])
            ->all();
    }

    /**
     * The icon itself (inline SVG, via `allowHtml()`), at a fixed 32x32
     * size — the name stays in the markup (`sr-only`, not removed) so
     * typing still searches by it, just not shown next to the icon.
     */
    protected static function optionLabel(string $name): string
    {
        $svg = svg('phosphor-'.$name, 'shrink-0')
            ->toHtml();

        $svg = str_replace('<svg ', '<svg style="width: 32px; height: 32px;" ', $svg);

        return '<span class="flex items-center gap-2">'.$svg.'<span class="sr-only">'.e(Str::headline($name)).'</span></span>';
    }
}
