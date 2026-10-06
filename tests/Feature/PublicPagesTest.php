<?php

use Inertia\Testing\AssertableInertia as Assert;

it('serves public YAW pages without authentication', function (string $path, string $component, ?string $page) {
    $this->get($path)->assertOk()->assertInertia(function (Assert $view) use ($component, $page) {
        $view->component($component)->where('auth.user', null);
        if ($page !== null) {
            $view->where('page', $page);
        }
    });
})->with([
    ['/', 'welcome', null],
    ['/solutions', 'public-page', 'solutions'],
    ['/for-pilots', 'public-page', 'pilots'],
    ['/for-operators', 'public-page', 'operators'],
    ['/compliance', 'public-page', 'compliance'],
    ['/how-it-works', 'public-page', 'how-it-works'],
    ['/about', 'public-page', 'about'],
]);

