<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Security\Exceptions\SecurityException;
use Config\Filters;
use Config\Security;

final class CsrfProtectionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testCsrfIsGlobalAndStableForAjaxSession(): void
    {
        /** @var Filters $filters */
        $filters = config(Filters::class);
        /** @var Security $security */
        $security = config(Security::class);

        $this->assertContains('csrf', $filters->globals['before']);
        $this->assertSame('session', $security->csrfProtection);
        $this->assertFalse($security->regenerate);
    }

    public function testPostWithoutTokenIsRejected(): void
    {
        $this->expectException(SecurityException::class);
        $this->withRoutes($this->probeRoutes())->post('csrf-probe');
    }

    public function testPostWithInvalidTokenIsRejected(): void
    {
        $this->expectException(SecurityException::class);
        $this->withRoutes($this->probeRoutes())->post('csrf-probe', [
            csrf_token() => 'token-invalido',
        ]);
    }

    public function testPostWithValidTokenPasses(): void
    {
        $withToken = $this->withRoutes($this->probeRoutes())->post('csrf-probe', [
            csrf_token() => csrf_hash(),
        ]);
        $withToken->assertStatus(204);
    }

    private function probeRoutes(): array
    {
        return [[
            'post',
            'csrf-probe',
            static fn () => service('response')->setStatusCode(204),
        ]];
    }

    public function testEveryExplicitPostFormContainsCsrfField(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APPPATH . 'Views'));
        $postForms = 0;

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            preg_match_all(
                '~<form\b(?=[^>]*\bmethod\s*=\s*["\']post["\'])[^>]*>.*?</form>~is',
                $contents,
                $matches
            );

            foreach ($matches[0] as $form) {
                $this->assertStringContainsString(
                    'csrf_field(',
                    $form,
                    "Formulário POST sem csrf_field() em {$file->getPathname()}"
                );
                $postForms++;
            }
        }

        $this->assertGreaterThan(0, $postForms);
    }

    public function testMainLayoutLoadsSameOriginAjaxProtection(): void
    {
        $layout = file_get_contents(APPPATH . 'Views/layouts/main.php');
        $script = file_get_contents(FCPATH . 'assets/js/csrf.js');

        $this->assertStringContainsString("csrf_meta('spiv-csrf')", $layout);
        $this->assertStringContainsString("assets/js/csrf.js", $layout);
        $this->assertStringContainsString('headers.set(headerName, token)', $script);
        $this->assertStringContainsString('isSameOrigin(url)', $script);
    }
}
