<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class HomeControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testIndexPageLoads(): void
    {
        $result = $this->get('/');

        $result->assertOK();
        $result->assertSee('Gestão de Carteira de Prospecção');
        $result->assertSee('Área do Vendedor');
        $result->assertSee('Módulos do Sistema');
    }
}
