<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Cache.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_cache') {
        Cache::clear();
        $mesaj = 'Cache șters cu succes.';
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - UnD</title>
    <link rel="stylesheet" href="../public/css/style.css" />
</head>
<body>
<div class="container">
    <h1>Modul Administrare</h1>

    <?php if (!empty($mesaj)): ?>
        <p class="mesaj-succes"><?= htmlspecialchars($mesaj) ?></p>
    <?php endif; ?>

    <div class="admin-sectiune">
        <h2>Import Date CSV</h2>
        <form action="../import/import_csv.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit" class="btn-aplica">Importă</button>
        </form>
    </div>

    <div class="admin-sectiune">
        <h2>Cache</h2>
        <form method="POST">
            <input type="hidden" name="action" value="clear_cache">
            <button type="submit" class="btn-aplica">Șterge Cache</button>
        </form>
    </div>

    <div class="admin-sectiune">
        <h2>Navigare</h2>
        <a href="../index.php" class="btn-aplica">Înapoi la aplicație</a>
    </div>
</div>
</body>
</html>