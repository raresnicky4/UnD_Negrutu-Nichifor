<?php
// Incarca dependintele necesare pentru validare si interogare BD
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../models/StatisticiModel.php';

// Valideaza filtrele din URL si preia datele din baza de date
$filters = Security::validateFilters($_GET);
$model = new StatisticiModel();
$data = $model->filtreaza($filters);

// Citeste formatul dorit din URL, implicit csv
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'csv';

// Export CSV - seteaza headerele si scrie datele rand cu rand
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="raport_somaj.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Judet', 'Anul', 'Luna', 'Numar Someri', 'Femei', 'Barbati', 'Urban', 'Rural', 'Rata Somaj']);
    foreach ($data as $rand) {
        fputcsv($out, [
            $rand['judet'],
            $rand['anul'],
            $rand['luna'],
            $rand['numar_someri_filtrat'] ?? $rand['numar_someri'],
            $rand['someri_femei'],
            $rand['someri_barbati'],
            $rand['urban'],
            $rand['rural'],
            $rand['rata_somaj']
        ]);
    }
    fclose($out);
    exit;
}

// Export JSON - seteaza headerele si returneaza datele ca JSON formatat
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="raport_somaj.json"');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
?>