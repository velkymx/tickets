<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local (stdio) acting user
    |--------------------------------------------------------------------------
    |
    | Local MCP servers run as Artisan commands with no HTTP auth layer, so
    | they resolve the acting user from this value. Read via config() (not
    | env() directly) so it survives `php artisan config:cache` in production.
    |
    */

    'user_id' => env('MCP_USER_ID'),

];
