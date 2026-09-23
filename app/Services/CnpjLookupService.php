<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;

/** Consulta dados públicos de CNPJ exclusivamente nas bases locais do SPIV. */
class CnpjLookupService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public function lookup(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        $consultedAt = (new DateTimeImmutable())->format(DATE_ATOM);
        if (strlen($cnpj) !== 14) {
            return $this->notFound($cnpj, $consultedAt);
        }

        $fallback = null;
        try {
            $row = $this->db->table('carteira_raw')->select('razao_social, cnae AS cnae_principal')
                ->where('cnpj', $cnpj)->get()->getRowArray();
            if ($row !== null) {
                $fallback = $row;
            }
        } catch (\Throwable) {
            // Fonte opcional ausente no ambiente de testes.
        }

        // SQL PostgreSQL; o schema receita não existe no SQLite de testes.
        try {
            $row = $this->db->query(
                "SELECT emp.razao_social, est.nome_fantasia,
                        est.situacao_cadastral AS situacao,
                        est.cnae_fiscal_principal AS cnae_principal,
                        TRIM(CONCAT_WS(' ', est.tipo_logradouro, est.logradouro, est.numero, est.complemento)) AS logradouro,
                        mun.descricao AS municipio, est.uf
                   FROM receita.estabelecimentos est
              LEFT JOIN receita.empresas emp ON emp.cnpj_basico = est.cnpj_basico
              LEFT JOIN receita.municipios mun ON mun.codigo = est.municipio
                  WHERE (est.cnpj_basico || est.cnpj_ordem || est.cnpj_dv) = ? LIMIT 1",
                [$cnpj]
            )->getRowArray();
            if ($row !== null) {
                return $this->found('receita_federal_local', $cnpj, $row, $consultedAt);
            }
        } catch (\Throwable) {
            // Fonte opcional ausente no ambiente de testes.
        }

        if ($fallback !== null) {
            return $this->found('carteira_raw', $cnpj, $fallback, $consultedAt);
        }

        return $this->notFound($cnpj, $consultedAt);
    }

    private function found(string $source, string $cnpj, array $row, string $consultedAt): array
    {
        return ['found' => true, 'source' => $source, 'consulted_at' => $consultedAt, 'data' => [
            'cnpj' => $cnpj,
            'razao_social' => $this->str($row['razao_social'] ?? null),
            'nome_fantasia' => $this->str($row['nome_fantasia'] ?? null),
            'situacao' => $this->str($row['situacao'] ?? null),
            'cnae_principal' => $this->str($row['cnae_principal'] ?? null),
            'logradouro' => $this->str($row['logradouro'] ?? null),
            'municipio' => $this->str($row['municipio'] ?? null),
            'uf' => $this->str($row['uf'] ?? null),
        ]];
    }
    private function notFound(string $cnpj, string $consultedAt): array
    {
        return ['found' => false, 'source' => null, 'consulted_at' => $consultedAt, 'data' => [
            'cnpj' => $cnpj, 'razao_social' => null, 'nome_fantasia' => null,
            'situacao' => null, 'cnae_principal' => null, 'logradouro' => null,
            'municipio' => null, 'uf' => null,
        ]];
    }

    private function str(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }
}
