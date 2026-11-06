<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_the_inertia_page_in_spanish(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('lang="es"', false);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Inicio', false));
    }
}
