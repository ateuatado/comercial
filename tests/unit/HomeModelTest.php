<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\HomeModel;
use Config\Database;

final class HomeModelTest extends CIUnitTestCase
{
    protected HomeModel $model;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect();
        $this->db->query('CREATE TABLE IF NOT EXISTS db_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            slug TEXT,
            content TEXT,
            is_active INTEGER,
            created_at TEXT,
            updated_at TEXT
        )');
        $this->db->query("DELETE FROM db_pages");
        $this->db->query("INSERT INTO db_pages (title, slug, content, is_active, created_at, updated_at) VALUES
            ('Início', 'home', 'Página inicial do SPIV', 1, datetime('now'), datetime('now'))");
        $this->db->query("INSERT INTO db_pages (title, slug, content, is_active, created_at, updated_at) VALUES
            ('Sobre', 'about', 'Página sobre o sistema', 0, datetime('now'), datetime('now'))");

        $this->model = new HomeModel($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_pages');
        parent::tearDown();
    }

    public function testGetActivePagesReturnsOnlyActive(): void
    {
        $pages = $this->model->getActivePages();

        $this->assertIsArray($pages);
        $this->assertCount(1, $pages);
        $this->assertSame('home', $pages[0]['slug']);
        $this->assertSame('Início', $pages[0]['title']);
    }

    public function testGetBySlugReturnsActivePage(): void
    {
        $page = $this->model->getBySlug('home');

        $this->assertIsArray($page);
        $this->assertSame('home', $page['slug']);
        $this->assertSame('Início', $page['title']);
    }

    public function testGetBySlugDoesNotReturnInactivePage(): void
    {
        $page = $this->model->getBySlug('about');

        $this->assertNull($page);
    }
}
