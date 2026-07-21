<?php

namespace OursBlanc\Xms\Events;

use OursBlanc\Xms\Models\Page;

class PageUnpublished
{
    public function __construct(public Page $page) {}
}
