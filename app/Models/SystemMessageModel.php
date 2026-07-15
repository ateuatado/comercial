<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemMessageModel extends Model
{
    protected $table         = 'system_messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'slug',
        'titulo',
        'conteudo',
        'ativo',
        'updated_by',
    ];

    protected $validationRules = [
        'slug'   => 'required|max_length[50]',
        'titulo' => 'required|max_length[200]',
    ];

    /**
     * Busca mensagem ativa por slug.
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)
                    ->where('ativo', true)
                    ->first();
    }

    /**
     * Retorna todas as mensagens ordenadas por slug.
     */
    public function getAll(): array
    {
        return $this->orderBy('slug', 'ASC')->findAll();
    }
}
