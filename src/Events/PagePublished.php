<?php

namespace OursBlanc\Xms\Events;

use OursBlanc\Xms\Models\Page;

class PagePublished
{
    public function __construct(public Page $page) {}
}
