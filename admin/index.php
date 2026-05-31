<?php
$PAROLA_ADMIN = 'admin123';

if (!isset($_POST['parola']) || $_POST['parola'] !== $PAROLA_ADMIN) {
    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <title>Login Admin - UnD</title>
        <style>
            body { background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: sans-serif; }
            .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 320px; }
            h2 { color: #1e40af; text-align: center; margin-top: 0; }
            input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; font-size: 1em; }
            button { width: 100%; padding: 12px; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; }
            .eroare { color: #ef4444; text-align: center; margin-bottom: 10px; }
        </style>
    </head>
    <body>
    <div class="box">
        <h2>🔐 Admin UnD</h2>
        <?php if (isset($_POST['parola'])): ?>
            <div class="eroare">❌ Parolă incorectă!</div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="parola" placeholder="Parolă admin" required autofocus>
            <button type="submit">Intră</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Cache.php';
require_once __DIR__ . '/../core/DataImporter.php';

$db = Database::getInstance()->getConnection();
$mesaj = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sterge_cache'])) {
        Cache::clear();
        $mesaj = 'Cache șters cu succes!';
    }
    if (isset($_POST['reimport'])) {
        $an = (int)$_POST['an'];
        $luna = (int)$_POST['luna'];
        if ($an >= 2020 && $an <= 2026 && $luna >= 1 && $luna <= 12) {
            $stmt = $db->prepare("DELETE FROM statistici WHERE anul = :an AND luna = :luna");
            $stmt->execute([':an' => $an, ':luna' => $luna]);
            Cache::clear();
            $importer = new DataImporter();
            $importer->importaInterval($an, $luna, $an, $luna);
            $mesaj = "Reimport finalizat pentru $luna/$an!";
        }
    }
}

$totalInregistrari = $db->query("SELECT COUNT(*) FROM statistici")->fetchColumn();
$anMin = $db->query("SELECT MIN(anul) FROM statistici")->fetchColumn();
$anMax = $db->query("SELECT MAX(anul) FROM statistici")->fetchColumn();

$luniImportate = $db->query("
    SELECT anul, luna, COUNT(*) as nr_judete, SUM(numar_someri) as total_someri
    FROM statistici
    GROUP BY anul, luna
    ORDER BY anul DESC, luna DESC
")->fetchAll();

$cacheFiles = glob(__DIR__ . '/../cache/*.cache') ?: [];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Platforma UnD</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .admin-container { max-width: 1100px; margin: 30px auto; padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card h2 { margin-top: 0; color: #1e40af; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .stat-box { background: #f0f9ff; border-radius: 8px; padding: 15px; text-align: center; }
        .stat-box .val { font-size: 2em; font-weight: bold; color: #1e40af; }
        .stat-box .lbl { color: #64748b; font-size: 0.9em; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; }
        .btn-red { background: #ef4444; color: white; }
        .btn-blue { background: #3b82f6; color: white; }
        .btn:hover { opacity: 0.85; }
        .mesaj { background: #dcfce7; color: #166534; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e40af; color: white; padding: 10px; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        tr:hover { background: #f8fafc; }
        .form-inline { display: flex; gap: 10px; align-items: center; }
        .form-inline input { padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; width: 80px; }
        a.back { display: inline-block; margin-bottom: 20px; color: #3b82f6; text-decoration: none; }
    </style>
</head>
<body style="background:#f1f5f9;">
<div class="admin-container">
    <a class="back" href="../">← Înapoi la aplicație</a>
    <h1 style="color:#1e40af;">🛠️ Panou de Administrare - UnD</h1>

    <?php if ($mesaj): ?>
        <div class="mesaj">✅ <?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>📊 Statistici Generale</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="val"><?= number_format($totalInregistrari) ?></div>
                <div class="lbl">Total înregistrări</div>
            </div>
            <div class="stat-box">
                <div class="val"><?= count($luniImportate) ?></div>
                <div class="lbl">Luni importate</div>
            </div>
            <div class="stat-box">
                <div class="val"><?= $anMin ?></div>
                <div class="lbl">Primul an</div>
            </div>
            <div class="stat-box">
                <div class="val"><?= $anMax ?></div>
                <div class="lbl">Ultimul an</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>🗑️ Management Cache</h2>
        <p>Fișiere cache active: <strong><?= count($cacheFiles) ?></strong></p>
        <form method="POST">
            <input type="hidden" name="parola" value="<?= htmlspecialchars($_POST['parola']) ?>">
            <button type="submit" name="sterge_cache" class="btn btn-red">Șterge tot cache-ul</button>
        </form>
    </div>

    <div class="card">
        <h2>🔄 Reimport Date</h2>
        <p>Forțează reimportul datelor pentru o lună specifică:</p>
        <form method="POST">
            <input type="hidden" name="parola" value="<?= htmlspecialchars($_POST['parola']) ?>">
            <div class="form-inline">
                <label>Luna:</label>
                <input type="number" name="luna" min="1" max="12" value="1" required>
                <label>Anul:</label>
                <input type="number" name="an" min="2020" max="2026" value="2023" required>
                <button type="submit" name="reimport" class="btn btn-blue">Reimportă</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>📅 Date Importate</h2>
        <table>
            <thead>
                <tr><th>An</th><th>Lună</th><th>Județe</th><th>Total Șomeri</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($luniImportate as $rand): ?>
                <tr>
                    <td><?= $rand['anul'] ?></td>
                    <td><?= $rand['luna'] ?></td>
                    <td><?= $rand['nr_judete'] ?></td>
                    <td><?= number_format($rand['total_someri']) ?></td>
                    <td><?= $rand['nr_judete'] >= 40 ? '✅ Complet' : '⚠️ Incomplet' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>