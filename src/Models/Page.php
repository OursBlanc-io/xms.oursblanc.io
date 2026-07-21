<?php

namespace OursBlanc\Xms\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OursBlanc\Xms\Events\PagePublished;
use OursBlanc\Xms\Events\PageSaved;
use OursBlanc\Xms\Events\PageUnpublished;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Page extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * Overridable resolver for who is performing the current write, used to
     * attribute page revisions. Defaults to the authenticated Filament user;
     * the MCP layer (Phase 6) swaps this for the acting API token.
     */
    public static ?Closure $authorResolver = null;

    protected $table = 'xms_pages';

    protected $fillable = [
        'translation_group_id',
        'locale',
        'slug',
        'title',
        'blocks',
        'seo',
        'template',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'blocks' => 'array',
        'seo' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Page $page) {
            if ($page->translation_group_id === null) {
                $page->translation_group_id = TranslationGroup::create()->id;
            }
        });

        static::updating(function (Page $page) {
            if ($page->isDirty(['title', 'slug', 'blocks', 'seo'])) {
                $page->recordRevisionOfOriginalState();
            }
        });

        static::saved(function (Page $page) {
            event(new PageSaved($page));

            if ($page->wasChanged('status')) {
                event($page->status === 'published'
                    ? new PagePublished($page)
                    : new PageUnpublished($page));
            }
        });
    }

    public function translationGroup(): BelongsTo
    {
        return $this->belongsTo(TranslationGroup::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest('created_at');
    }

    public function siblingLocales(): HasMany
    {
        return $this->hasMany(static::class, 'translation_group_id', 'translation_group_id')
            ->where('id', '!=', $this->id);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Every media collection on a page is named `block-{uuid}`; conversions
     * apply regardless of collection, but only to image files (videos are
     * stored as-is, see Media\VideoProcessor for poster generation).
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media && ! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        foreach ([480, 960, 1920] as $width) {
            $this->addMediaConversion("w{$width}")
                ->width($width)
                ->format('webp')
                ->quality(82)
                ->nonQueued();
        }
    }

    protected function recordRevisionOfOriginalState(): void
    {
        $author = static::resolveAuthor();

        $this->revisions()->create([
            'title' => $this->getOriginal('title'),
            'slug' => $this->getOriginal('slug'),
            'blocks' => $this->getOriginal('blocks'),
            'seo' => $this->getOriginal('seo'),
            'author_type' => $author['type'],
            'author_id' => $author['id'],
        ]);

        $this->pruneOldRevisions();
    }

    protected function pruneOldRevisions(): void
    {
        $limit = (int) config('xms.revisions_per_page', 50);

        $this->revisions()
            ->skip($limit)
            ->take(PHP_INT_MAX)
            ->pluck('id')
            ->each(fn (int $id) => PageRevision::destroy($id));
    }

    /**
     * @return array{type: string, id: string}
     */
    public static function resolveAuthor(): array
    {
        if (static::$authorResolver) {
            return (static::$authorResolver)();
        }

        return [
            'type' => 'user',
            'id' => (string) (auth()->id() ?? 'system'),
        ];
    }
}
