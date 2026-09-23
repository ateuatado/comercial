<?php

declare(strict_types=1);

use App\Database\Migrations\CreateVendedorEventualFoundation;
use App\Services\CnpjLookupService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/2026-08-25-100001_CreateVendedorEventualFoundation.php';

/**
 * Testa a CnpjLookupService contra tabelas stub no banco de testes SQLite.
 * A tabela client_wallets e carteira_raw são criadas/destruídas em cada teste
 * para garantir isolamento total.
 */
final class CnpjLookupServiceTest extends CIUnitTestCase
{
    private $testDb;
    private CreateVendedorEventualFoundation $foundation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb   = Database::connect('tests');
        $this->foundation = new CreateVendedorEventualFoundation(Database::forge('tests'));

        // Garantir estado limpo das tabelas stub.
        $this->dropStubs();
    }

    protected function tearDown(): void
    {
        $this->dropStubs();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Testes de CNPJ não encontrado
    // -------------------------------------------------------------------------

    public function testNotFoundWhenNoTablesExist(): void
    {
        $result = (new CnpjLookupService($this->testDb))->lookup('12345678000195');

        $this->assertFalse($result['found']);
        $this->assertNull($result['source']);
        $this->assertNull($result['data']['razao_social']);
    }

    public function testNotFoundWhenCnpjAbsent(): void
    {
        $this->createClientWalletsStub();
        $result = (new CnpjLookupService($this->testDb))->lookup('99999999000100');

        $this->assertFalse($result['found']);
        $this->assertNull($result['source']);
    }

    public function testInvalidCnpjFormatReturnsNotFound(): void
    {
        $result = (new CnpjLookupService($this->testDb))->lookup('123');

        $this->assertFalse($result['found']);
    }

    // -------------------------------------------------------------------------
    // Testes de CNPJ encontrado em carteira_raw
    // -------------------------------------------------------------------------

    public function testFoundInCarteiraRaw(): void
    {
        $this->createCarteiraRawStub();
        $this->testDb->table('db_carteira_raw')->insert([
            'cnpj'         => '12345678000195',
            'razao_social' => 'Empresa Teste LTDA',
            'cnae'         => '4711301',
        ]);

        $result = (new CnpjLookupService($this->testDb))->lookup('12.345.678/0001-95');

        $this->assertTrue($result['found']);
        $this->assertSame('carteira_raw', $result['source']);
        $this->assertSame('Empresa Teste LTDA', $result['data']['razao_social']);
        $this->assertSame('4711301', $result['data']['cnae_principal']);
        $this->assertNotEmpty($result['consulted_at']);
    }

    public function testCnpjNormalizationWithMask(): void
    {
        $this->createCarteiraRawStub();
        $this->testDb->table('db_carteira_raw')->insert([
            'cnpj'         => '12345678000195',
            'razao_social' => 'Empresa Mascarada',
        ]);

        // Deve encontrar mesmo informando com máscara.
        $result = (new CnpjLookupService($this->testDb))->lookup('12.345.678/0001-95');

        $this->assertTrue($result['found']);
        $this->assertSame('Empresa Mascarada', $result['data']['razao_social']);
    }

    // -------------------------------------------------------------------------
    // Testes de CNPJ encontrado em carteira_raw (fallback)
    // -------------------------------------------------------------------------

    public function testFoundInCarteiraRawWhenNotInWallets(): void
    {
        $this->createCarteiraRawStub();
        $this->testDb->table('db_carteira_raw')->insert([
            'cnpj'         => '98765432000188',
            'razao_social' => 'Empresa Raw LTDA',
            'cnae'         => '4722901',
        ]);

        $result = (new CnpjLookupService($this->testDb))->lookup('98765432000188');

        $this->assertTrue($result['found']);
        $this->assertSame('carteira_raw', $result['source']);
        $this->assertSame('Empresa Raw LTDA', $result['data']['razao_social']);
        $this->assertSame('4722901', $result['data']['cnae_principal']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createClientWalletsStub(): void
    {
        $this->testDb->query('DROP TABLE IF EXISTS db_client_wallets');
        $this->testDb->query("
            CREATE TABLE db_client_wallets (
                cnpj               VARCHAR(14),
                razao_social       VARCHAR(200) DEFAULT NULL,
                nome_fantasia      VARCHAR(200) DEFAULT NULL,
                situacao_cadastral VARCHAR(50)  DEFAULT NULL,
                cnae_fiscal        VARCHAR(20)  DEFAULT NULL,
                logradouro         VARCHAR(200) DEFAULT NULL,
                municipio          VARCHAR(100) DEFAULT NULL,
                uf                 VARCHAR(2)   DEFAULT NULL,
                vendor_id          INTEGER      DEFAULT NULL
            )
        ");
    }

    private function createCarteiraRawStub(): void
    {
        $this->testDb->query('DROP TABLE IF EXISTS db_carteira_raw');
        $this->testDb->query("
            CREATE TABLE db_carteira_raw (
                cnpj         VARCHAR(14),
                razao_social VARCHAR(200) DEFAULT NULL,
                cnae         VARCHAR(15)  DEFAULT NULL
            )
        ");
    }

    private function dropStubs(): void
    {
        $this->testDb->query('DROP TABLE IF EXISTS db_client_wallets');
        $this->testDb->query('DROP TABLE IF EXISTS db_carteira_raw');
    }
}
