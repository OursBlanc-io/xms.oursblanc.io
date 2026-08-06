<?php

namespace OursBlanc\Xms\Filament\Resources\PageResource\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
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
                                    // A trashed page keeps its row (soft delete), so
                                    // without this its (locale, slug) would still
                                    // count as taken and block reusing it.
                                    modifyRuleUsing: fn ($rule, $get) => $rule
                                        ->where('locale', $get('locale'))
                                        ->where('deleted_at', null),
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
                                ->helperText('Raw JSON-LD, optional.')
                                // MCP tools accept `seo.structured_data` as a
                                // JSON object (the natural shape for an AI
                                // caller to produce), while this field always
                                // edits/saves it as the raw JSON-LD string the
                                // front-end <script> tag expects — encode on
                                // the way in so admin-authored data stays
                                // consistent with MCP-authored data.
                                ->afterStateHydrated(fn ($component, $state) => $component->state(
                                    is_array($state) ? json_encode($state) : $state,
                                )),
                        ]),
                    Tab::make('Settings')
                        ->schema([
                            Select::make('locale')
                                ->options(array_combine(
                                    config('xms.locales'),
                                    config('xms.locales'),
                                ))
                                ->helperText('Leave empty for a locale-agnostic page served at the site root (e.g. "/my-page"), outside any /fr or /en prefix.'),
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
                            Placeholder::make('illustration_preview')
                                ->hiddenLabel()
                                ->visible(fn (Get $get) => filled($get('illustration')))
                                ->content(fn (Get $get) => PageMediaUpload::imagePreviewHtml($get('illustration'))),
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
        $blocks = app(BlockRegistry::class)->builderBlocks();

        return Builder::make('blocks')
            ->blocks($blocks)
            ->collapsible()
            ->collapsed()
            ->blockNumbers(false)
            ->addActionLabel('Add block');
    }
}
