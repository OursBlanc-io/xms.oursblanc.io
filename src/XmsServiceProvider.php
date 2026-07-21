<?php

namespace OursBlanc\Xms;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Blocks\BlockValidator;
use OursBlanc\Xms\Blocks\Generic\ColumnsBlock;
use OursBlanc\Xms\Blocks\Generic\CtaBlock;
use OursBlanc\Xms\Blocks\Generic\GalleryBlock;
use OursBlanc\Xms\Blocks\Generic\HeadingBlock;
use OursBlanc\Xms\Blocks\Generic\HeroBlock;
use OursBlanc\Xms\Blocks\Generic\ImageBlock;
use OursBlanc\Xms\Blocks\Generic\TextBlock;
use OursBlanc\Xms\Blocks\Generic\VideoBlock;
use OursBlanc\Xms\Cache\CacheInvalidator;
use OursBlanc\Xms\Cache\CloudflareInvalidator;
use OursBlanc\Xms\Cache\NullInvalidator;
use OursBlanc\Xms\Console\PruneOrphanedMediaCommand;
use OursBlanc\Xms\Events\PagePublished;
use OursBlanc\Xms\Events\PageSaved;
use OursBlanc\Xms\Events\PageUnpublished;
use OursBlanc\Xms\Listeners\DispatchCdnPurge;
use OursBlanc\Xms\Media\FfmpegVideoProcessor;
use OursBlanc\Xms\Media\PageMediaSynchronizer;
use OursBlanc\Xms\Media\VideoProcessor;
use OursBlanc\Xms\Rendering\PageRenderer;
use OursBlanc\Xms\Rendering\ThemeManager;
use OursBlanc\Xms\Rendering\ViewResolver;

class XmsServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string<Block>>
     */
    protected array $genericBlocks = [
        HeadingBlock::class,
        TextBlock::class,
        HeroBlock::class,
        ImageBlock::class,
        GalleryBlock::class,
        VideoBlock::class,
        CtaBlock::class,
        ColumnsBlock::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/xms.php', 'xms');

        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(BlockValidator::class);
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(ViewResolver::class);
        $this->app->singleton(PageRenderer::class);
        $this->app->singleton(PageMediaSynchronizer::class);
        $this->app->bind(VideoProcessor::class, FfmpegVideoProcessor::class);

        $this->app->bind(CacheInvalidator::class, function () {
            if (config('xms.cloudflare.zone_id') && config('xms.cloudflare.token')) {
                return new CloudflareInvalidator;
            }

            return new NullInvalidator;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'xms');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/mcp.php');

        if (config('xms.generic_blocks_enabled')) {
            $registry = $this->app->make(BlockRegistry::class);

            foreach ($this->genericBlocks as $blockClass) {
                $registry->register($blockClass);
            }
        }

        $theme = $this->app->make(ThemeManager::class);

        if ($themeViewsPath = $theme->viewsPath()) {
            $this->loadViewsFrom($themeViewsPath, ThemeManager::VIEW_NAMESPACE);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/xms.php' => config_path('xms.php'),
            ], 'xms-config');

            $this->publishes([
                __DIR__.'/../resources/css/xms.css' => public_path('vendor/xms/xms.css'),
                __DIR__.'/../resources/js/xms.js' => public_path('vendor/xms/xms.js'),
            ], 'xms-assets');

            $this->commands([
                PruneOrphanedMediaCommand::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(PruneOrphanedMediaCommand::class)->daily();
        });

        Event::listen([PageSaved::class, PagePublished::class, PageUnpublished::class], DispatchCdnPurge::class);
    }
}
