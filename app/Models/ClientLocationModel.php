<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientLocationModel extends Model
{
    protected $table         = 'client_locations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'cnpj',
        'latitude',
        'longitude',
        'endereco_formatado',
        'registrado_por',
    ];

    protected $validationRules = [
        'cnpj'      => 'required|max_length[14]',
        'latitude'  => 'required|decimal',
        'longitude' => 'required|decimal',
    ];

    /**
     * Busca localização por CNPJ.
     */
    public function findByCnpj(string $cnpj): ?array
    {
        return $this->where('cnpj', $cnpj)->first();
    }

    /**
     * Upsert: insere ou atualiza localização de um CNPJ.
     */
    public function upsert(array $data): bool
    {
        $existing = $this->findByCnpj($data['cnpj']);

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        return (bool) $this->insert($data);
    }

    /**
     * Calcula distância Haversine entre duas coordenadas (em km).
     */
    public static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
