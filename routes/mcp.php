<?php

use Laravel\Mcp\Facades\Mcp;
use OursBlanc\Xms\Http\Middleware\AuthenticateMcpToken;
use OursBlanc\Xms\Mcp\XmsMcpServer;

Mcp::web(config('xms.mcp.route', '/mcp/xms'), XmsMcpServer::class)
    ->middleware([AuthenticateMcpToken::class, 'throttle:60,1']);
