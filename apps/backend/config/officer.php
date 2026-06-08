<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hub-active session TTL
    |--------------------------------------------------------------------------
    |
    | How long (in hours) an officer workflow session remains active after
    | logging in within their assigned hub radius.
    |
    */

    'hub_active_ttl_hours' => (int) env('OFFICER_HUB_ACTIVE_TTL_HOURS', 10),

];
