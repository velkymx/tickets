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

    /*
    |--------------------------------------------------------------------------
    | tools/list page size
    |--------------------------------------------------------------------------
    |
    | The framework default is 15. Keep this at or above the number of tools
    | the server registers so tools/list returns them all in one page and no
    | tool hides behind nextCursor for clients that don't paginate. Must not
    | exceed the server's maxPaginationLength (50).
    |
    */

    'pagination_length' => 50,

];
