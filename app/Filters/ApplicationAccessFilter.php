<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\ApplicationAccessService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\VendedorEventual;

class ApplicationAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $application = (string) ($arguments[0] ?? '');
        $capability  = (string) ($arguments[1] ?? 'access');

        /** @var VendedorEventual $config */
        $config = config(VendedorEventual::class);
        if ($application === 'vendedor_eventual' && ! $config->enabled) {
            return service('response')->setStatusCode(404)->setBody('Aplicação indisponível.');
        }

        $user = auth()->user();
        if ($application === '' || ! (new ApplicationAccessService())->hasAccess((int) $user->id, $application, $capability)) {
            return service('response')->setStatusCode(403)->setBody('Acesso não autorizado.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
