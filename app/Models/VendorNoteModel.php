<?php

namespace App\Models;

use CodeIgniter\Model;

class VendorNoteModel extends Model
{
    protected $table         = 'vendor_notes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';

    protected $allowedFields = [
        'matricula_vendedor',
        'cnpj',
        'tipo',
        'conteudo',
        'sentimento',
        'created_at',
    ];

    protected $validationRules = [
        'matricula_vendedor' => 'required|max_length[20]',
        'cnpj'               => 'required|max_length[14]',
        'tipo'               => 'required|in_list[visita,observacao,contato_telefonico,reuniao,estrategia]',
        'conteudo'           => 'required',
        'sentimento'         => 'permit_empty|in_list[positivo,neutro,negativo]',
    ];

    /**
     * Retorna notas de um cliente para um vendedor (mais recentes primeiro).
     */
    public function getByClientAndVendor(string $cnpj, string $matricula): array
    {
        return $this->where('cnpj', $cnpj)
                    ->where('matricula_vendedor', $matricula)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Retorna as últimas N notas de um vendedor.
     */
    public function getRecentByVendor(string $matricula, int $limit = 10): array
    {
        return $this->where('matricula_vendedor', $matricula)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Conta notas por tipo para um vendedor.
     */
    public function countByType(string $matricula): array
    {
        return $this->select('tipo, COUNT(*) as total')
                    ->where('matricula_vendedor', $matricula)
                    ->groupBy('tipo')
                    ->findAll();
    }
}
