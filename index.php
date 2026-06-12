<?php
// Incarca configurarea bazei de date (constante DB_HOST, DB_NAME, etc.)
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platforma UnD - Statistici Șomaj</title>
    <!-- CSS pentru harta Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- CSS propriu al aplicatiei -->
    <link rel="stylesheet" href="public/css/style.css" />
</head>
<body>

<!-- Containerul principal al paginii -->
<div class="container" id="continut-pdf">
    <h1>Platforma UnD - Analiză Șomaj România</h1>

    <!-- Bara de filtre - nu apare in exportul PDF -->
    <div class="filtre" data-html2canvas-ignore>

        <!-- Filtru dupa judet - utilizatorul scrie numele judetului -->
        <div class="filtru-item">
            <label>Județ:</label>
            <input type="text" id="filtru-judet" placeholder="Ex: IASI">
        </div>

        <!-- Filtru interval inceput - luna si an de start -->
        <div class="filtru-item">
            <label>De la:</label>
            <div style="display:flex; gap:6px;">
                <input type="number" id="filtru-luna-start" min="1" max="12" value="1" placeholder="Luna" style="width:65px;">
                <input type="number" id="filtru-an-start" min="2018" max="2026" value="2023" placeholder="An" style="width:75px;">
            </div>
        </div>

        <!-- Filtru interval sfarsit - luna si an de stop -->
        <div class="filtru-item">
            <label>Până la:</label>
            <div style="display:flex; gap:6px;">
                <input type="number" id="filtru-luna-stop" min="1" max="12" value="12" placeholder="Luna" style="width:65px;">
                <input type="number" id="filtru-an-stop" min="2018" max="2026" value="2024" placeholder="An" style="width:75px;">
            </div>
        </div>

        <!-- Filtru mediu - urban, rural sau toate -->
        <div class="filtru-item">
            <label>Mediu:</label>
            <select id="filtru-mediu">
                <option value="">Toate</option>
                <option value="urban">Urban</option>
                <option value="rural">Rural</option>
            </select>
        </div>

        <!-- Filtru grupa varsta - afecteaza coloana afisata in grafic pe client -->
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

        <!-- Filtru nivel educatie - afecteaza coloana afisata in grafic pe client -->
        <div class="filtru-item">
            <label>Nivel Educație:</label>
            <select id="filtru-educatie">
                <option value="">Toate</option>
                <option value="edu_fara_studii">Fără studii</option>
                <option value="edu_primar">Primar</option>
                <option value="edu_gimnazial">Gimnazial</option>
                <option value="edu_liceal">Liceal</option>
                <option value="edu_postliceal">Postliceal</option>
                <option value="edu_profesional">Profesional</option>
                <option value="edu_universitar">Universitar</option>
            </select>
        </div>

        <!-- Filtru sex - trimis la API, schimba coloana SQL -->
        <div class="filtru-item">
            <label>Sex:</label>
            <select id="filtru-sex">
                <option value="">Toate</option>
                <option value="masculin">Masculin</option>
                <option value="feminin">Feminin</option>
            </select>
        </div>

        <!-- Buton care declanseaza apelul Ajax catre api/statistici.php -->
        <div class="filtru-item" style="justify-content: flex-end;">
            <button class="btn-aplica" onclick="aplicaFiltre()">Aplică Filtrele</button>
        </div>
    </div>

    <!-- Layout split: harta stanga, grafice dreapta -->
    <div class="vizualizare-split" style="height: 600px;">
        <div class="partea-stanga" style="position: relative;">

        <!-- Partea stanga - harta Leaflet cu markeri colorati pe judete -->
        <div class="partea-stanga" style="position: relative;">
            <div id="harta"></div>

            <div class="harta-legenda" style="position: absolute; bottom: 20px; left: 20px; z-index: 1000; background: rgba(255, 255, 255, 0.9); padding: 10px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.2); font-family: sans-serif; font-size: 14px;">
                <h4 style="margin: 0 0 8px 0; font-size: 15px;">Top Județe Șomaj</h4>
                <div style="display: flex; align-items: center; margin-bottom: 6px;">
                    <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png" width="14" style="margin-right: 8px;"> Scăzut (Top 35%)
                </div>
                <div style="display: flex; align-items: center; margin-bottom: 6px;">
                    <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-gold.png" width="14" style="margin-right: 8px;"> Mediu (36% - 75%)
                </div>
                <div style="display: flex; align-items: center;">
                    <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png" width="14" style="margin-right: 8px;"> Ridicat (Peste 75%)
                </div>
            </div>
        </div>

        <!-- Partea dreapta - butoane navigare grafice + containerele graficelor -->
        <div class="partea-dreapta">

            <!-- Butoane pentru comutarea intre grafice - nu apar in PDF -->
            <div class="view-toggles" data-html2canvas-ignore>
                <button class="btn-view active" onclick="schimbaGrafic('bar', this)">Grafic Județe</button>
                <button class="btn-view" onclick="schimbaGrafic('pie', this)">Distribuție Mediu</button>
                <button class="btn-view" onclick="schimbaGrafic('varste', this)">Grupe Vârstă</button>
                <button class="btn-view" onclick="schimbaGrafic('educatie', this)">Nivel Educație</button>
                <button class="btn-view" onclick="schimbaGrafic('comparatie', this)">Comparație Județe</button>
            </div>

            <!-- Grafic bara - numarul de someri pe fiecare judet -->
            <div id="container-bar" class="grafic-box">
                <canvas id="graficSomaj"></canvas>
            </div>

            <!-- Grafic tort - distributie urban vs rural (ascuns initial) -->
            <div id="container-pie" class="grafic-box hidden">
                <canvas id="graficPie"></canvas>
            </div>

            <!-- Grafic bara - distributie pe grupe de varsta (ascuns initial) -->
            <div id="container-varste" class="grafic-box hidden">
                <canvas id="graficVarste"></canvas>
            </div>

            <!-- Grafic bara - distributie pe nivel de educatie (ascuns initial) -->
            <div id="container-educatie" class="grafic-box hidden">
                <canvas id="graficEducatie"></canvas>
            </div>

            <!-- Sectiunea comparatie judete (ascunsa initial) -->
            <div id="container-comparatie" class="hidden">

                <!-- Controale pentru selectarea criteriului si judetului de comparat -->
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:10px;">

                    <!-- Dropdown cu criteriile de comparatie disponibile -->
                    <select id="compara-criteriu" onchange="deseneazaComparatie()">
                        <option value="numar_someri">Total șomeri</option>
                        <option value="rata_somaj">Rata șomajului (%)</option>
                        <option value="someri_femei">Femei</option>
                        <option value="someri_barbati">Bărbați</option>
                        <option value="urban">Urban</option>
                        <option value="rural">Rural</option>
                        <option value="edu_fara_studii">Educație: Fără studii</option>
                        <option value="edu_primar">Educație: Primar</option>
                        <option value="edu_gimnazial">Educație: Gimnazial</option>
                        <option value="edu_liceal">Educație: Liceal</option>
                        <option value="edu_postliceal">Educație: Postliceal</option>
                        <option value="edu_profesional">Educație: Profesional</option>
                        <option value="edu_universitar">Educație: Universitar</option>
                        <option value="varsta_sub25">Vârstă: Sub 25</option>
                        <option value="varsta_25_29">Vârstă: 25-29</option>
                        <option value="varsta_30_39">Vârstă: 30-39</option>
                        <option value="varsta_40_49">Vârstă: 40-49</option>
                        <option value="varsta_50_55">Vârstă: 50-55</option>
                        <option value="varsta_peste55">Vârstă: Peste 55</option>
                    </select>

                    <!-- Dropdown cu lista de judete - populat dinamic din app.js -->
                    <select id="compara-judet"></select>

                    <!-- Buton care adauga judetul selectat in graficul de comparatie -->
                    <button class="btn-aplica" onclick="adaugaComparatie()">+ Adaugă județ</button>
                </div>

                <!-- Lista cu judetele adaugate la comparatie -->
                <div id="lista-comparatie" style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px;"></div>

                <!-- Canvas pentru graficul de comparatie -->
                <div style="position:relative; height:420px;">
                    <canvas id="graficComparatie"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Butoane export - nu apar in PDF -->
    <div class="export-box" data-html2canvas-ignore>
        <button class="btn-export btn-csv" onclick="exportCSV()">Exportă CSV</button>
        <button class="btn-export btn-svg" onclick="exportSVG()">Exportă SVG</button>
        <button class="btn-export btn-json" onclick="exportJSON()">Exportă JSON</button>
        <button class="btn-export btn-pdf" onclick="exportPDF()">Exportă PDF</button>
    </div>
</div>

<!-- Libraria Leaflet pentru harta interactiva -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Libraria Chart.js pentru grafice -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Libraria jsPDF pentru exportul PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="public/js/app.js?v=7"></script>
<a href="admin/" style="position:fixed; bottom:10px; right:10px; background:#1e40af; color:white; padding:8px 14px; border-radius:8px; text-decoration:none; font-size:0.85em; opacity:0.7;">⚙️ Admin</a>
</body>
</html>