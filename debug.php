<?php
function descarcaCSV(string $url): array {
    $context = stream_context_create(['http' => ['header' => 'User-Agent: Mozilla/5.0', 'timeout' => 15, 'follow_location' => 1]]);
    $continut = @file_get_contents($url, false, $context);
    if (!$continut) return [];
    $continut = mb_convert_encoding($continut, 'UTF-8', 'auto');
    $linii = explode("\n", trim($continut));
    array_shift($linii);
    $rezultat = [];
    foreach ($linii as $linie) {
        $linie = trim($linie);
        if (empty($linie)) continue;
        $col = explode(';', $linie);
        $col = array_map(fn($c) => trim(str_replace('"', '', $c)), $col);
        $judet = strtoupper(trim($col[0]));
        if (empty($judet) || strpos($judet, 'TOTAL') !== false || strlen($judet) < 2) continue;
        $rezultat[$judet] = $col;
    }
    return $rezultat;
}

$medii = descarcaCSV('https://data.gov.ro/dataset/d96cb258-7951-46a3-922f-d5aa9e34750e/resource/ec45a9dc-4f6c-4167-bba2-c08c843e4e43/download/medii-ian-2023.csv');

echo "Numar judete medii: " . count($medii) . "<br>";
echo "Prima intrare: <pre>";
print_r(array_slice($medii, 0, 2));
echo "</pre>";
?>