<?php

namespace OursBlanc\Xms\Events;

use OursBlanc\Xms\Models\Page;

class PageSaved
{
    public function __construct(public Page $page) {}
}
