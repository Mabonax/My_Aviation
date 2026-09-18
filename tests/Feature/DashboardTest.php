<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertOk();
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