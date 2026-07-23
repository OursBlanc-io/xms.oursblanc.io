<?php

namespace OursBlanc\Xms\Filament\Resources\PageResource\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;
use OursBlanc\Xms\Models\Page;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Page')
                ->tabs([
                    Tab::make('Content')
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->maxLength(500)
                                ->helperText('Leave empty for the homepage.')
                                ->rule(function () {
                                    return function (string $attribute, mixed $value, \Closure $fail) {
                                        if ($value !== '' && preg_match(Page::SLUG_REGEX, (string) $value) !== 1) {
                                            $fail('The slug format is invalid.');
                                        }
                                    };
                                })
                                ->unique(
                                    table: 'xms_pages',
                                    column: 'slug',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn ($rule, $get) => $rule->where('locale', $get('locale')),
                                ),
                            static::blocksBuilder(),
                        ]),
                    Tab::make('SEO')
                        ->schema([
                            TextInput::make('seo.title')
                                ->maxLength(255),
                            Textarea::make('seo.description')
                                ->rows(3),
                            TextInput::make('seo.canonical')
                                ->url(),
                            TextInput::make('seo.og_title')
                                ->maxLength(255),
                            Textarea::make('seo.og_description')
                                ->rows(3),
                            TextInput::make('seo.og_image_media_id')
                                ->numeric(),
                            Select::make('seo.robots')
                                ->options([
                                    'index,follow' => 'index, follow',
                                    'noindex,follow' => 'noindex, follow',
                                    'index,nofollow' => 'index, nofollow',
                                    'noindex,nofollow' => 'noindex, nofollow',
                                ])
                                ->default('index,follow'),
                            Textarea::make('seo.structured_data')
                                ->rows(4)
                                ->helperText('Raw JSON-LD, optional.'),
                        ]),
                    Tab::make('Settings')
                        ->schema([
                            Select::make('locale')
                                ->options(array_combine(
                                    config('xms.locales'),
                                    config('xms.locales'),
                                ))
                                ->required(),
                            TextInput::make('template')
                                ->helperText('Blade layout override, e.g. "landing". Leave empty to use the theme default.'),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                ])
                                ->default('draft')
                                ->required(),
                            DateTimePicker::make('published_at'),
                            Select::make('categories')
                                ->relationship('categories', 'name')
                                ->multiple()
                                ->preload()
                                ->searchable(),
                        ]),
                    Tab::make('Listing')
                        ->schema([
                            TextInput::make('list_title')
                                ->maxLength(255)
                                ->helperText('Shown instead of the title above when this page appears as a card in a page_list block. Leave empty to reuse the title.'),
                            Textarea::make('list_excerpt')
                                ->rows(3)
                                ->helperText('Short summary shown in listing cards.'),
                            PageMediaUpload::make('illustration')
                                ->image()
                                ->helperText('Cover image used in listing cards — separate from any image used in the page\'s own blocks.'),
                            KeyValue::make('meta')
                                ->label('Metadata')
                                ->keyLabel('Key')
                                ->valueLabel('Value')
                                ->helperText('Freeform key/value pairs (e.g. format: video, visual_type: portrait) that a page_list block can filter on.'),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function blocksBuilder(): Builder
    {
        $registry = app(BlockRegistry::class);

        $blocks = collect($registry->all())->map(
            fn (string $blockClass, string $name) => Builder\Block::make($name)
                ->label($blockClass::label())
                ->schema([
                    Hidden::make('uuid')->default(fn () => (string) Str::uuid()),
                    ...$blockClass::fields(),
                ])
        )->values()->all();

        return Builder::make('blocks')
            ->blocks($blocks)
            ->collapsible()
            ->blockNumbers(false)
            ->addActionLabel('Add block');
    }
}
