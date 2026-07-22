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
        'publica',
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
     * Inclui TODAS as notas do vendedor + notas públicas de outros vendedores.
     */
    public function getByClientAndVendor(string $cnpj, string $matricula): array
    {
        return $this->where('cnpj', $cnpj)
                    ->groupStart()
                        ->where('matricula_vendedor', $matricula)
                        ->orWhere('publica', true)
                    ->groupEnd()
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Retorna as últimas N notas privadas + públicas de um vendedor.
     * Usado no dashboard do próprio vendedor.
     */
    public function getRecentByVendor(string $matricula, int $limit = 10): array
    {
        return $this->where('matricula_vendedor', $matricula)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Retorna todas as notas públicas de um cliente (de qualquer vendedor).
     * Visível para todos os usuários do sistema.
     */
    public function getPublicByClient(string $cnpj): array
    {
        return $this->where('cnpj', $cnpj)
                    ->where('publica', true)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Alterna visibilidade de uma nota. Retorna false se a nota não pertencer
     * ao vendedor informado (proteção contra alteração alheia).
     */
    public function togglePublica(int $id, string $matricula): bool
    {
        $nota = $this->where('id', $id)->where('matricula_vendedor', $matricula)->first();
        if (!$nota) {
            return false;
        }
        $this->update($id, ['publica' => !$nota['publica']]);
        return true;
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
