<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class HomeControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = null;

    public function testIndexPageRedirectsToLogin(): void
    {
        $result = $this->get('/');

        $result->assertRedirectTo('/login');
    }
}
