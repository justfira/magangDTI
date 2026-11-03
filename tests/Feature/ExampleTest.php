<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_informasi_program_bisa_dibuka(): void
    {
        $this->get('/')->assertStatus(200)->assertSee('Program Magang DTI');
    }

    public function test_dashboard_wajib_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
