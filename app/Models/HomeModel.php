<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model: HomeModel
 * Responsável pelos dados da página inicial.
 *
 * Segue o padrão MVC do CodeIgniter 4.
 * Tabela: pages
 */
class HomeModel extends Model
{
    protected $table            = 'pages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'title',
        'slug',
        'content',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[255]',
        'slug'  => 'required|is_unique[pages.slug,id,{id}]',
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'O título é obrigatório.',
            'min_length' => 'O título deve ter ao menos 3 caracteres.',
        ],
        'slug' => [
            'required'   => 'O slug é obrigatório.',
            'is_unique'  => 'Este slug já está em uso.',
        ],
    ];

    // ── Métodos de negócio ─────────────────────────────────

    /**
     * Retorna todas as páginas ativas.
     */
    public function getActivePages(): array
    {
        return $this->where('is_active', true)
                    ->orderBy('title', 'ASC')
                    ->findAll();
    }

    /**
     * Retorna uma página pelo slug.
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)
                    ->where('is_active', true)
                    ->first();
    }
}
