<?php

namespace App\Services;

/**
 * ReclameAquiScanner — busca reclamações de frete/logística no Reclame Aqui via Serper API.
 *
 * Resolução da chave (por ordem de prioridade):
 *   1. Chave pessoal do usuário ($userApiKey, passada via construtor)
 *   2. Chave global do servidor (SERPER_API_KEY no .env)
 *   3. Nenhuma chave → retorna erro estruturado com orientação ao usuário
 */
class ReclameAquiScanner
{
    protected string $apiKey = '';

    /** Código de erro para ausência de chave (usado pelo controller para personalizar a resposta) */
    const ERR_NO_KEY = 'NO_API_KEY';

    public function __construct(?string $userApiKey = null)
    {
        // Prioridade: chave pessoal > chave global do .env
        $this->apiKey = $userApiKey
            ?: (getenv('SERPER_API_KEY') ?: '');
    }

    /** Retorna true se há uma chave configurada (pessoal ou global). */
    public function hasKey(): bool
    {
        return $this->apiKey !== '';
    }

    public function scan(string $empresaNome): array
    {
        if (!$this->hasKey()) {
            return [
                'error'      => self::ERR_NO_KEY,
                'error_type' => self::ERR_NO_KEY,
            ];
        }

        $client = \Config\Services::curlrequest();
        $query  = 'site:reclameaqui.com.br "' . $empresaNome . '" (frete OR postal OR sedex OR pac OR encomenda OR "logística reversa")';

        try {
            $response = $client->post('https://google.serper.dev/search', [
                'headers' => [
                    'X-API-KEY'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'q'   => $query,
                    'gl'  => 'br',
                    'hl'  => 'pt-br',
                    'num' => 5,
                ],
                'http_errors' => false,
                'timeout'     => 8,
            ]);

            $status = $response->getStatusCode();

            // Chave inválida ou sem créditos
            if ($status === 401 || $status === 403) {
                return [
                    'error'      => self::ERR_NO_KEY,
                    'error_type' => 'INVALID_KEY',
                ];
            }

            if ($status !== 200) {
                return ['error' => 'Falha na requisição Serper (HTTP ' . $status . ').'];
            }

            $data = json_decode($response->getBody(), true);
            return $data['organic'] ?? [];

        } catch (\Exception $e) {
            return ['error' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }
}
