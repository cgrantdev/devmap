<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // The Inertia root view calls @vite, and tests have no built manifest.
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
