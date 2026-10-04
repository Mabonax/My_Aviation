<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('experience')
            ->has('operatorOverview')
            ->has('operatorOverview.pipeline')
            ->has('operatorOverview.fleet')
            ->has('operatorOverview.missions')
        );
});

test('super admins can access protected UAS sections', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($user);

    $this->get('/dashboard')->assertOk();
    $this->get('/operators')->assertOk();
    $this->get('/training-courses')->assertOk();
    $this->get('/regulatory-requirements')->assertOk();
    $this->get('/regulatory-forms')->assertOk();
    $this->get('/compliance/register')->assertOk();
});