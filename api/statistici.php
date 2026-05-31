<?php
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/DataImporter.php';
require_once __DIR__ . '/../models/StatisticiModel.php';

$filters = Security::validateFilters($_GET);

$anStart   = (int)($_GET['an_start']   ?? date('Y') - 1);
$lunaStart = (int)($_GET['luna_start'] ?? 1);
$anStop    = (int)($_GET['an_stop']    ?? date('Y'));
$lunaStop  = (int)($_GET['luna_stop']  ?? (int)date('m'));

$importer = new DataImporter();
$importer->importaInterval($anStart, $lunaStart, $anStop, $lunaStop);

$model = new StatisticiModel();
$data = $model->filtreaza($filters);
Response::json($data);
?>