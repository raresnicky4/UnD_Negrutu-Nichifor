<?php
// Incarca dependintele necesare pentru validare, import si interogare BD
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/DataImporter.php';
require_once __DIR__ . '/../models/StatisticiModel.php';

// Valideaza toti parametrii primiti din URL prin GET
$filters = Security::validateFilters($_GET);

// Citeste intervalul de timp din URL pentru import
// Daca lipsesc parametrii foloseste valori implicite
$anStart   = (int)($_GET['an_start']   ?? date('Y') - 1);
$lunaStart = (int)($_GET['luna_start'] ?? 1);
$anStop    = (int)($_GET['an_stop']    ?? date('Y'));
$lunaStop  = (int)($_GET['luna_stop']  ?? (int)date('m'));

// Importa datele lipsa din intervalul cerut de pe data.gov.ro
$importer = new DataImporter();
$importer->importaInterval($anStart, $lunaStart, $anStop, $lunaStop);

// Preia datele filtrate din baza de date si le returneaza ca JSON
$model = new StatisticiModel();
$data = $model->filtreaza($filters);
Response::json($data);
?>