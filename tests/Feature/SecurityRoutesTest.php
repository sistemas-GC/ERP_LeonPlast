<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;

class SecurityRoutesTest extends TestCase
{
    public function test_fix_password_route_does_not_exist()
    {
        $response = $this->get('/fix-password');
        $response->assertStatus(404);
    }

    public function test_produccion_routes_require_authentication()
    {
        $response = $this->get('/produccion/ordenes');
        $response->assertRedirectToRoute('login');
    }

    public function test_terceros_routes_require_authentication()
    {
        $response = $this->get('/terceros/salidas');
        $response->assertRedirectToRoute('login');
    }

    public function test_reportes_routes_require_authentication()
    {
        $response = $this->get('/reportes');
        $response->assertRedirectToRoute('login');
    }

    public function test_forgot_password_view_renders_successfully()
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('¿Olvidaste tu contraseña?');
    }
}
