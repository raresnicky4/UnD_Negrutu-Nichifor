<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Cache.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $db = Database::getInstance()->getConnection();
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle, 1000, ',');

    $stmt = $db->prepare("INSERT INTO statistici (judet, anul, luna, grupa_varsta, nivel_educatie, mediu, numar_someri) VALUES (:judet, :anul, :luna, :grupa_varsta, :nivel_educatie, :mediu, :numar_someri)");

    $count = 0;
    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        if (count($row) < 7) continue;
        $stmt->execute([
            ':judet'          => trim($row[0]),
            ':anul'           => (int)$row[1],
            ':luna'           => (int)$row[2],
            ':grupa_varsta'   => trim($row[3]),
            ':nivel_educatie' => trim($row[4]),
            ':mediu'          => trim($row[5]),
            ':numar_someri'   => (int)$row[6],
        ]);
        $count++;
    }
    fclose($handle);
    Cache::clear();
    echo json_encode(['success' => true, 'randuri_importate' => $count]);
    exit;
}

echo json_encode(['error' => 'Fisier invalid']);
?>