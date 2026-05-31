<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Cache.php';

class DataImporter {

    private $db;

    private $luniRo = [
        1 => 'ianuarie', 2 => 'februarie', 3 => 'martie',
        4 => 'aprilie', 5 => 'mai', 6 => 'iunie',
        7 => 'iulie', 8 => 'august', 9 => 'septembrie',
        10 => 'octombrie', 11 => 'noiembrie', 12 => 'decembrie'
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function importaInterval(int $anStart, int $lunaStart, int $anStop, int $lunaStop): void {
        $an = $anStart;
        $luna = $lunaStart;
        while ($an < $anStop || ($an === $anStop && $luna <= $lunaStop)) {
            if (!$this->existaDate($an, $luna)) {
                $dataLuna = mktime(0, 0, 0, $luna, 1, $an);
                if ($dataLuna <= time()) {
                    $this->stergeDate($an, $luna);
                    Cache::clear();
                    $this->importaLuna($an, $luna);
                }
            }
            $luna++;
            if ($luna > 12) { $luna = 1; $an++; }
        }
    }

    private function existaDate(int $an, int $luna): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM statistici WHERE anul = :an AND luna = :luna AND numar_someri > 0 AND urban > 0");
        $stmt->execute([':an' => $an, ':luna' => $luna]);
        return $stmt->fetchColumn() >= 30;
    }

    private function stergeDate(int $an, int $luna): void {
        $stmt = $this->db->prepare("DELETE FROM statistici WHERE anul = :an AND luna = :luna");
        $stmt->execute([':an' => $an, ':luna' => $luna]);
    }

    private function importaLuna(int $an, int $luna): void {
        $numeLuna = $this->luniRo[$luna];
        $apiUrl = "https://data.gov.ro/api/3/action/package_search?q=somaj+{$numeLuna}+{$an}&rows=10";
        $context = stream_context_create(['http' => ['header' => 'User-Agent: Mozilla/5.0', 'timeout' => 5]]);

        $response = @file_get_contents($apiUrl, false, $context);
        if (!$response) return;

        $json = json_decode($response, true);
        if (!$json || empty($json['result']['results'])) return;

        $linkRata = $linkMedii = $linkVarste = $linkEducatie = null;

        foreach ($json['result']['results'] as $pachet) {
            $titlu = strtolower($pachet['title'] ?? '');
            if (strpos($titlu, $numeLuna) === false || strpos($titlu, (string)$an) === false) continue;

            foreach ($pachet['resources'] as $resursa) {
                $url = str_replace('http://', 'https://', $resursa['url'] ?? '');
                $urlLower = strtolower($url);
                if (strpos($urlLower, 'rata') !== false && strpos($urlLower, '.csv') !== false) $linkRata = $url;
                if (strpos($urlLower, 'medii') !== false && strpos($urlLower, '.csv') !== false) $linkMedii = $url;
                if (strpos($urlLower, 'varste') !== false && strpos($urlLower, '.csv') !== false) $linkVarste = $url;
                if (strpos($urlLower, 'nivel-educatie') !== false && strpos($urlLower, '.csv') !== false) $linkEducatie = $url;
            }
            if ($linkRata) break;
        }

        if (!$linkRata) return;

        $dateRata     = $this->descarcaCSVRata($linkRata);
        $dateMedii    = $linkMedii    ? $this->descarcaCSV($linkMedii)    : [];
        $dateVarste   = $linkVarste   ? $this->descarcaCSV($linkVarste)   : [];
        $dateEducatie = $linkEducatie ? $this->descarcaCSV($linkEducatie) : [];

        $this->salveaza($an, $luna, $dateRata, $dateMedii, $dateVarste, $dateEducatie);
    }

    private function normalizeazaJudet(string $judet): string {
        $judet = strtoupper(trim($judet));
        $judet = preg_replace('/\s+/', ' ', $judet);
        $map = [
            'BISTRITANASAUD'      => 'BISTRITA NASAUD',
            'BISTRITA-NASAUD'     => 'BISTRITA NASAUD',
            'CARAS-SEVERIN'       => 'CARAS SEVERIN',
            'CARA?-SEVERIN'       => 'CARAS SEVERIN',
            'SATUMARE'            => 'SATU MARE',
            'SATU-MARE'           => 'SATU MARE',
            'MUN.BUC.'            => 'MUNICIPIUL BUCURESTI',
            'MUN. BUC.'           => 'MUNICIPIUL BUCURESTI',
            'MUNICIPIUL BUC.'     => 'MUNICIPIUL BUCURESTI',
            'MUNICIPIULBUCURESTI' => 'MUNICIPIUL BUCURESTI',
        ];
        return $map[$judet] ?? $judet;
    }

    private function descarcaCSVRata(string $url): array {
        $context = stream_context_create(['http' => ['header' => 'User-Agent: Mozilla/5.0', 'timeout' => 15, 'follow_location' => 1]]);
        $continut = @file_get_contents($url, false, $context);
        if (!$continut) return [];

        $continut = mb_convert_encoding($continut, 'UTF-8', 'auto');
        $linii = explode("\n", trim($continut));
        if (count($linii) < 2) return [];

        array_shift($linii);
        $rezultat = [];

        foreach ($linii as $linie) {
            $linie = trim($linie);
            if (empty($linie)) continue;

            $col = str_getcsv($linie, ';');
            if (count($col) < 2) $col = str_getcsv($linie, ',');

            $col = array_map(fn($c) => trim(str_replace('"', '', $c)), $col);
            $col[0] = preg_replace('/\s+/', ' ', trim($col[0]));

            $judet = $this->normalizeazaJudet($col[0]);
            $judet = substr($judet, 0, 100);
            if (empty($judet) || strpos($judet, 'TOTAL') !== false || strlen($judet) < 2) continue;

            $rezultat[$judet] = $col;
        }

        return $rezultat;
    }

    private function descarcaCSV(string $url): array {
        $context = stream_context_create(['http' => ['header' => 'User-Agent: Mozilla/5.0', 'timeout' => 15, 'follow_location' => 1]]);
        $continut = @file_get_contents($url, false, $context);
        if (!$continut) return [];

        $continut = mb_convert_encoding($continut, 'UTF-8', 'auto');
        $linii = explode("\n", trim($continut));
        if (count($linii) < 2) return [];

        $header = $linii[0];
        $delimiter = substr_count($header, ';') >= substr_count($header, ',') ? ';' : ',';

        array_shift($linii);
        $rezultat = [];

        foreach ($linii as $linie) {
            $linie = trim($linie);
            if (empty($linie)) continue;

            $col = explode($delimiter, $linie);
            $col = array_map(fn($c) => trim(str_replace('"', '', $c)), $col);
            $col[0] = preg_replace('/\s+/', ' ', trim($col[0]));

            $judet = $this->normalizeazaJudet($col[0]);
            $judet = substr($judet, 0, 100);
            if (empty($judet) || strpos($judet, 'TOTAL') !== false || strlen($judet) < 2) continue;

            $rezultat[$judet] = $col;
        }

        return $rezultat;
    }

    private function parseInt(string $val): int {
        return (int)preg_replace('/[^0-9]/', '', $val);
    }

    private function parseFloat(string $val): float {
        return (float)str_replace(',', '.', preg_replace('/[^0-9,.]/', '', $val));
    }

    private function salveaza(int $an, int $luna, array $rata, array $medii, array $varste, array $educatie): void {
        $stmt = $this->db->prepare("
            INSERT INTO statistici
                (judet, anul, luna, numar_someri, someri_femei, someri_barbati, rata_somaj,
                 urban, rural,
                 varsta_sub25, varsta_25_29, varsta_30_39, varsta_40_49, varsta_50_55, varsta_peste55,
                 edu_fara_studii, edu_primar, edu_gimnazial, edu_liceal, edu_postliceal, edu_profesional, edu_universitar)
            VALUES
                (:judet, :an, :luna, :numar_someri, :someri_femei, :someri_barbati, :rata_somaj,
                 :urban, :rural,
                 :varsta_sub25, :varsta_25_29, :varsta_30_39, :varsta_40_49, :varsta_50_55, :varsta_peste55,
                 :edu_fara_studii, :edu_primar, :edu_gimnazial, :edu_liceal, :edu_postliceal, :edu_profesional, :edu_universitar)
            ON DUPLICATE KEY UPDATE
                numar_someri = VALUES(numar_someri), someri_femei = VALUES(someri_femei),
                someri_barbati = VALUES(someri_barbati), rata_somaj = VALUES(rata_somaj),
                urban = VALUES(urban), rural = VALUES(rural),
                varsta_sub25 = VALUES(varsta_sub25), varsta_25_29 = VALUES(varsta_25_29),
                varsta_30_39 = VALUES(varsta_30_39), varsta_40_49 = VALUES(varsta_40_49),
                varsta_50_55 = VALUES(varsta_50_55), varsta_peste55 = VALUES(varsta_peste55),
                edu_fara_studii = VALUES(edu_fara_studii), edu_primar = VALUES(edu_primar),
                edu_gimnazial = VALUES(edu_gimnazial), edu_liceal = VALUES(edu_liceal),
                edu_postliceal = VALUES(edu_postliceal), edu_profesional = VALUES(edu_profesional),
                edu_universitar = VALUES(edu_universitar)
        ");

        foreach ($rata as $judet => $col) {
            $r = $medii[$judet]    ?? $medii[str_replace(' ', '', $judet)]    ?? [];
            $v = $varste[$judet]   ?? $varste[str_replace(' ', '', $judet)]   ?? [];
            $e = $educatie[$judet] ?? $educatie[str_replace(' ', '', $judet)] ?? [];

            $stmt->execute([
                ':judet'            => $judet,
                ':an'               => $an,
                ':luna'             => $luna,
                ':numar_someri'     => $this->parseInt($col[1] ?? '0'),
                ':someri_femei'     => $this->parseInt($col[2] ?? '0'),
                ':someri_barbati'   => $this->parseInt($col[3] ?? '0'),
                ':rata_somaj'       => $this->parseFloat($col[7] ?? '0'),
                ':urban'            => $this->parseInt($r[4] ?? '0'),
                ':rural'            => $this->parseInt($r[7] ?? '0'),
                ':varsta_sub25'     => $this->parseInt($v[2] ?? '0'),
                ':varsta_25_29'     => $this->parseInt($v[3] ?? '0'),
                ':varsta_30_39'     => $this->parseInt($v[4] ?? '0'),
                ':varsta_40_49'     => $this->parseInt($v[5] ?? '0'),
                ':varsta_50_55'     => $this->parseInt($v[6] ?? '0'),
                ':varsta_peste55'   => $this->parseInt($v[7] ?? '0'),
                ':edu_fara_studii'  => $this->parseInt($e[2] ?? '0'),
                ':edu_primar'       => $this->parseInt($e[3] ?? '0'),
                ':edu_gimnazial'    => $this->parseInt($e[4] ?? '0'),
                ':edu_liceal'       => $this->parseInt($e[5] ?? '0'),
                ':edu_postliceal'   => $this->parseInt($e[6] ?? '0'),
                ':edu_profesional'  => $this->parseInt($e[7] ?? '0'),
                ':edu_universitar'  => $this->parseInt($e[8] ?? '0'),
            ]);
        }
    }
}
?>