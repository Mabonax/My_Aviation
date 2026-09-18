<?php

namespace App\Domains\Uas\Access\Application;

final class WorkosConfiguration
{
    public static function enabled(): bool
    {
        return (bool) config('workos.enabled')
            && filled(config('workos.api_key'))
            && filled(config('workos.client_id'))
            && filled(config('workos.redirect_uri'));
    }
}
