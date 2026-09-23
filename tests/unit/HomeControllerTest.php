<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

final class HomeControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        config('Settings')->handlers = ['array'];
        Services::resetSingle('settings');
        Services::resetSingle('auth');
    }

    protected function tearDown(): void
    {
        config('Settings')->handlers = ['database'];
        Services::resetSingle('settings');
        Services::resetSingle('auth');

        parent::tearDown();
    }

    public function testIndexPageRedirectsToLogin(): void
    {
        $result = $this->get('/');

        $result->assertRedirectTo('/login');
    }
}
