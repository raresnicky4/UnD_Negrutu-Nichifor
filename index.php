<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platforma UnD - Statistici Șomaj</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="public/css/style.css" />
</head>
<body>

<div class="container" id="continut-pdf">
    <h1>Platforma UnD - Analiză Șomaj România</h1>

    <div class="filtre" data-html2canvas-ignore>
        <div class="filtru-item">
            <label>Județ:</label>
            <input type="text" id="filtru-judet" placeholder="Ex: IASI">
        </div>
        <div class="filtru-item">
            <label>De la:</label>
            <div style="display:flex; gap:6px;">
                <input type="number" id="filtru-luna-start" min="1" max="12" value="1" placeholder="Luna" style="width:65px;">
                <input type="number" id="filtru-an-start" min="2020" max="2026" value="2023" placeholder="An" style="width:75px;">
            </div>
        </div>
        <div class="filtru-item">
            <label>Până la:</label>
            <div style="display:flex; gap:6px;">
                <input type="number" id="filtru-luna-stop" min="1" max="12" value="12" placeholder="Luna" style="width:65px;">
                <input type="number" id="filtru-an-stop" min="2020" max="2026" value="2024" placeholder="An" style="width:75px;">
            </div>
        </div>
        <div class="filtru-item">
            <label>Mediu:</label>
            <select id="filtru-mediu">
                <option value="">Toate</option>
                <option value="urban">Urban</option>
                <option value="rural">Rural</option>
            </select>
        </div>
        <div class="filtru-item">
            <label>Grupă Vârstă:</label>
            <select id="filtru-varsta">
                <option value="">Toate</option>
                <option value="varsta_sub25">Sub 25 ani</option>
                <option value="varsta_25_29">25-29 ani</option>
                <option value="varsta_30_39">30-39 ani</option>
                <option value="varsta_40_49">40-49 ani</option>
                <option value="varsta_50_55">50-55 ani</option>
                <option value="varsta_peste55">Peste 55 ani</option>
            </select>
        </div>
        <div class="filtru-item">
            <label>Sex:</label>
            <select id="filtru-sex">
                <option value="">Toate</option>
                <option value="masculin">Masculin</option>
                <option value="feminin">Feminin</option>
            </select>
        </div>
        <div class="filtru-item" style="justify-content: flex-end;">
            <button class="btn-aplica" onclick="aplicaFiltre()">Aplică Filtrele</button>
        </div>
    </div>

    <div class="vizualizare-split">
        <div class="partea-stanga">
            <div id="harta"></div>
        </div>

        <div class="partea-dreapta">
            <div class="view-toggles" data-html2canvas-ignore>
                <button class="btn-view active" onclick="schimbaGrafic('bar', this)">Grafic Județe</button>
                <button class="btn-view" onclick="schimbaGrafic('pie', this)">Distribuție Mediu</button>
                <button class="btn-view" onclick="schimbaGrafic('varste', this)">Grupe Vârstă</button>
            </div>

            <div id="container-bar" class="grafic-box">
                <canvas id="graficSomaj"></canvas>
            </div>

            <div id="container-pie" class="grafic-box hidden">
                <canvas id="graficPie"></canvas>
            </div>

            <div id="container-varste" class="grafic-box hidden">
                <canvas id="graficVarste"></canvas>
            </div>
        </div>
    </div>

    <div class="export-box" data-html2canvas-ignore>
        <button class="btn-export btn-csv" onclick="exportCSV()">Exportă CSV</button>
        <button class="btn-export btn-svg" onclick="exportSVG()">Exportă SVG</button>
        <button class="btn-export btn-json" onclick="exportJSON()">Exportă JSON</button>
        <button class="btn-export btn-pdf" onclick="exportPDF()">Exportă PDF</button>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="public/js/app.js"></script>
<a href="admin/" style="position:fixed; bottom:10px; right:10px; background:#1e40af; color:white; padding:8px 14px; border-radius:8px; text-decoration:none; font-size:0.85em; opacity:0.7;">⚙️ Admin</a>
</body>
</html>