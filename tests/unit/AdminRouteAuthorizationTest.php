<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;

final class AdminRouteAuthorizationTest extends CIUnitTestCase
{
    public function testEveryAdminRouteRequiresSessionAndAdminPermission(): void
    {
        /** @var RouteCollection $routes */
        $routes = service('routes', false);
        require APPPATH . 'Config/Routes.php';

        $adminRoutes = 0;

        foreach (['GET', 'POST'] as $method) {
            foreach ($routes->getRoutes($method) as $route => $handler) {
                if ($route !== 'admin' && ! str_starts_with($route, 'admin/')) {
                    continue;
                }

                $filters = (array) ($routes->getRoutesOptions($route, $method)['filter'] ?? []);

                $this->assertContains('session', $filters, "{$method} {$route} deve exigir autenticação.");
                $this->assertContains(
                    'permission:admin.access',
                    $filters,
                    "{$method} {$route} deve exigir a permissão administrativa."
                );
                $adminRoutes++;
            }
        }

        $this->assertGreaterThan(0, $adminRoutes, 'Nenhuma rota administrativa foi encontrada para validação.');
    }
}
