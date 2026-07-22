<?php

namespace App\Services;

class ReclameAquiScanner
{
    protected $apiKey;

    public function __construct()
    {
        // Puxa a chave configurada no .env
        $this->apiKey = getenv('SERPER_API_KEY');
    }

    public function scan(string $empresaNome): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'SERPER_API_KEY não configurada no .env'];
        }

        $client = \Config\Services::curlrequest();
        $query = 'site:reclameaqui.com.br "' . $empresaNome . '" (frete OR postal OR sedex OR pac OR encomenda OR "logística reversa")';

        try {
            $response = $client->post('https://google.serper.dev/search', [
                'headers' => [
                    'X-API-KEY' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'q' => $query,
                    'gl' => 'br',
                    'hl' => 'pt-br',
                    'num' => 5
                ],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                return ['error' => 'Falha na requisição para a API Serper. Status: ' . $response->getStatusCode()];
            }

            $data = json_decode($response->getBody(), true);
            return $data['organic'] ?? [];

        } catch (\Exception $e) {
            return ['error' => 'Erro ao processar a busca: ' . $e->getMessage()];
        }
    }
}
