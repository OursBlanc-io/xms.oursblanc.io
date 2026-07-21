<?php

namespace OursBlanc\Xms;

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
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'xms');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if (config('xms.generic_blocks_enabled')) {
            $registry = $this->app->make(BlockRegistry::class);

            foreach ($this->genericBlocks as $blockClass) {
                $registry->register($blockClass);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/xms.php' => config_path('xms.php'),
            ], 'xms-config');
        }
    }
}
