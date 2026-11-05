<?php

namespace App\Services;

use DomainException;

class GeometriaMunicipal
{
    public function validar(mixed $g): array
    {
        if (! is_array($g) || ! in_array($g['type'] ?? '', ['Polygon', 'MultiPolygon'], true) || ! is_array($g['coordinates'] ?? null)) {
            throw new DomainException('Esperada geometria Polygon/MultiPolygon GeoJSON.');
        }
        $poligonos = $g['type'] === 'Polygon' ? [$g['coordinates']] : $g['coordinates'];
        $total = 0;
        if (! $poligonos) {
            throw new DomainException('Geometria vazia.');
        }
        foreach ($poligonos as $poligono) {
            if (! is_array($poligono) || ! $poligono) {
                throw new DomainException('Polígono vazio.');
            }
            foreach ($poligono as $anel) {
                if (! is_array($anel) || count($anel) < 4 || $anel[0] !== $anel[count($anel) - 1]) {
                    throw new DomainException('Anel inválido ou não fechado.');
                }
                foreach ($anel as $p) {
                    if (++$total > 100000 || ! is_array($p) || count($p) !== 2 || ! is_numeric($p[0]) || ! is_numeric($p[1])
                        || ! is_finite((float) $p[0]) || ! is_finite((float) $p[1]) || abs($p[0]) > 180 || abs($p[1]) > 90) {
                        throw new DomainException('Coordenadas inválidas ou geometria excessiva.');
                    }
                }
            }
        }

        return ['type' => $g['type'], 'coordinates' => $g['coordinates']];
    }

    public function contem(array $g, float $lng, float $lat): bool
    {
        foreach ($g['type'] === 'Polygon' ? [$g['coordinates']] : $g['coordinates'] as $p) {
            if (! $this->anel($p[0], $lng, $lat)) {
                continue;
            }
            $buraco = false;
            foreach (array_slice($p, 1) as $anel) {
                $buraco = $buraco || $this->anel($anel, $lng, $lat);
            }
            if (! $buraco) {
                return true;
            }
        }

        return false;
    }

    private function anel(array $anel, float $x, float $y): bool
    {
        $dentro = false;
        for ($i = 0, $j = count($anel) - 1; $i < count($anel); $j = $i++) {
            [$xi, $yi] = $anel[$i];
            [$xj, $yj] = $anel[$j];
            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $dentro = ! $dentro;
            }
        }

        return $dentro;
    }
}
