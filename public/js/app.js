/* public/js/app.js */
/* ============================================================ */
/* app.js - UnD Vizualizator Date Somaj */
/* Logica client: grafice, harta, filtre, export */
/* ============================================================ */

/* Instantele globale ale graficelor si hartii */
let harta;
let grafic;
let graficPie;
let graficVarste;
let graficEducatie;
let graficComparatie;

/* Lista judetelor adaugate curent in graficul de comparatie */
let judeteComparate = [];

/* Lista completa a judetelor din Romania */
const TOATE_JUDETELE = [
    'ALBA', 'ARAD', 'ARGES', 'BACAU', 'BIHOR', 'BISTRITA NASAUD', 'BOTOSANI',
    'BRAILA', 'BRASOV', 'BUZAU', 'CALARASI', 'CARAS SEVERIN', 'CLUJ', 'CONSTANTA',
    'COVASNA', 'DAMBOVITA', 'DOLJ', 'GALATI', 'GIURGIU', 'GORJ', 'HARGHITA',
    'HUNEDOARA', 'IALOMITA', 'IASI', 'ILFOV', 'MARAMURES', 'MEHEDINTI', 'MURES',
    'NEAMT', 'OLT', 'PRAHOVA', 'SALAJ', 'SATU MARE', 'SIBIU', 'SUCEAVA',
    'TELEORMAN', 'TIMIS', 'TULCEA', 'VALCEA', 'VASLUI', 'VRANCEA', 'MUNICIPIUL BUCURESTI'
];

/* Stocheaza ultimele date primite de la API */
let dateCurente = [];

/* Initializeaza harta si graficele dupa incarcarea paginii */
document.addEventListener("DOMContentLoaded", function() {
    /* Limiteaza harta la granita Romaniei */
    const limiteRomania = [[43.5, 20.0], [48.5, 30.0]];

    harta = L.map('harta', {
        maxBounds: limiteRomania,
        maxBoundsViscosity: 1.0,
        minZoom: 6
    }).setView([45.9, 25.0], 6);

    /* Incarca placile OpenStreetMap */
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(harta);

    /* Grafic bara - someri pe judete */
    const ctx = document.getElementById('graficSomaj').getContext('2d');
    grafic = new Chart(ctx, {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Someri pe Judete' } },
            scales: {
                y: { beginAtZero: true },
                x: {
                    ticks: {
                        maxRotation: 90,
                        minRotation: 90,
                        autoSkip: false,
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    /* Grafic tort - distributie urban vs rural */
    const ctxPie = document.getElementById('graficPie').getContext('2d');
    graficPie = new Chart(ctxPie, {
        type: 'pie',
        data: { labels: ['Urban', 'Rural'], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Distributie Urban vs Rural' } }
        }
    });

    /* Grafic bara - distributie pe grupe de varsta */
    const ctxVarste = document.getElementById('graficVarste').getContext('2d');
    graficVarste = new Chart(ctxVarste, {
        type: 'bar',
        data: {
            labels: ['Sub 25 ani', '25-29 ani', '30-39 ani', '40-49 ani', '50-55 ani', 'Peste 55 ani'],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Distributie pe Grupe de Varsta' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    /* Grafic bara - distributie pe nivel de educatie */
    const ctxEducatie = document.getElementById('graficEducatie').getContext('2d');
    graficEducatie = new Chart(ctxEducatie, {
        type: 'bar',
        data: {
            labels: ['Fara studii', 'Primar', 'Gimnazial', 'Liceal', 'Postliceal', 'Profesional', 'Universitar'],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Distributie pe Nivel de Educatie' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    /* Grafic bara - comparatie intre judete selectate */
    const ctxComp = document.getElementById('graficComparatie').getContext('2d');
    graficComparatie = new Chart(ctxComp, {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Comparatie intre Judete' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    /* Populeaza lista de judete din dropdown-ul de comparatie */
    const selComp = document.getElementById('compara-judet');
    TOATE_JUDETELE.forEach(j => {
        const opt = document.createElement('option');
        opt.value = j;
        opt.textContent = j;
        selComp.appendChild(opt);
    });
});

/* Afiseaza graficul selectat si ascunde celelalte */
function schimbaGrafic(tip, btn) {
    document.getElementById('container-bar').classList.add('hidden');
    document.getElementById('container-pie').classList.add('hidden');
    document.getElementById('container-varste').classList.add('hidden');
    document.getElementById('container-educatie').classList.add('hidden');
    document.getElementById('container-comparatie').classList.add('hidden');
    document.querySelectorAll('.btn-view').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    if (tip === 'bar') document.getElementById('container-bar').classList.remove('hidden');
    else if (tip === 'pie') document.getElementById('container-pie').classList.remove('hidden');
    else if (tip === 'varste') document.getElementById('container-varste').classList.remove('hidden');
    else if (tip === 'educatie') document.getElementById('container-educatie').classList.remove('hidden');
    else if (tip === 'comparatie') document.getElementById('container-comparatie').classList.remove('hidden');
}

/* Citeste valorile filtrelor si preia datele de la API prin Ajax */
function aplicaFiltre() {
    const judet     = document.getElementById('filtru-judet').value.toUpperCase().trim();
    const lunaStart = document.getElementById('filtru-luna-start').value;
    const anStart   = document.getElementById('filtru-an-start').value;
    const lunaStop  = document.getElementById('filtru-luna-stop').value;
    const anStop    = document.getElementById('filtru-an-stop').value;
    const mediu     = document.getElementById('filtru-mediu').value;
    const varsta    = document.getElementById('filtru-varsta').value;
    const educatie  = document.getElementById('filtru-educatie').value;
    const sex       = document.getElementById('filtru-sex').value;

    /* Construieste URL-ul API cu parametrii de filtrare */
    let url = `api/statistici.php?an_start=${anStart}&luna_start=${lunaStart}&an_stop=${anStop}&luna_stop=${lunaStop}`;
    if (judet) url += `&judet=${judet}`;
    if (mediu) url += `&mediu=${mediu}`;
    if (sex) url += `&sex=${sex}`;

    /* Dezactiveaza butonul pe durata incarcarii */
    document.querySelector('.btn-aplica').textContent = 'Se incarca...';
    document.querySelector('.btn-aplica').disabled = true;

    /* Apel asincron catre API-ul REST */
    fetch(url)
        .then(response => response.json())
        .then(data => {
            dateCurente = data;
            actualizeazaInterfata(data, varsta, educatie);
            /* Redeseneaza graficul de comparatie daca sunt judete selectate */
            if (judeteComparate.length > 0) deseneazaComparatie();
        })
        .catch(() => alert("Eroare la aducerea datelor!"))
        .finally(() => {
            document.querySelector('.btn-aplica').textContent = 'Aplica Filtrele';
            document.querySelector('.btn-aplica').disabled = false;
        });
}

/* Functie care primeste procentajul calculat in top si returneaza o iconita standard Leaflet colorata corespunzator */
function getPinIcon(procentaj) {
    let color = "red";
    if (procentaj <= 35) {
        color = "green";
    } else if (procentaj <= 75) {
        color = "gold";
    }

    return new L.Icon({
        iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
}

/* Actualizeaza toate graficele si harta cu datele noi */
function actualizeazaInterfata(date, varstaFiltru = '', educatieFiltru = '') {
    /* Totaluri agregate pe judet */
    let someriPeJudet = {};
    let urban = 0, rural = 0;
    let varste = [0, 0, 0, 0, 0, 0];
    let educatie = [0, 0, 0, 0, 0, 0, 0];

    date.forEach(rand => {
        let j = rand.judet.toUpperCase();
        /* Normalizeaza numele Bucurestiului */
        if (j === 'MUN. BUCURESTI') j = 'MUNICIPIUL BUCURESTI';
        if (j === 'TOTAL') return;
        if (!someriPeJudet[j]) someriPeJudet[j] = 0;

        /* Foloseste coloana filtrata daca e activ filtrul de varsta sau educatie */
        if (varstaFiltru && rand[varstaFiltru] !== undefined) {
            someriPeJudet[j] += parseInt(rand[varstaFiltru]) || 0;
        } else if (educatieFiltru && rand[educatieFiltru] !== undefined) {
            someriPeJudet[j] += parseInt(rand[educatieFiltru]) || 0;
        } else {
            /* Foloseste valoarea filtrata de server daca exista, altfel totalul */
            someriPeJudet[j] += parseInt(rand.numar_someri_filtrat ?? rand.numar_someri) || 0;
        }

        /* Acumuleaza totalurile urban/rural */
        urban += parseInt(rand.urban) || 0;
        rural += parseInt(rand.rural) || 0;

        /* Acumuleaza totalurile pe grupe de varsta */
        varste[0] += parseInt(rand.varsta_sub25) || 0;
        varste[1] += parseInt(rand.varsta_25_29) || 0;
        varste[2] += parseInt(rand.varsta_30_39) || 0;
        varste[3] += parseInt(rand.varsta_40_49) || 0;
        varste[4] += parseInt(rand.varsta_50_55) || 0;
        varste[5] += parseInt(rand.varsta_peste55) || 0;

        /* Acumuleaza totalurile pe nivel de educatie */
        educatie[0] += parseInt(rand.edu_fara_studii) || 0;
        educatie[1] += parseInt(rand.edu_primar) || 0;
        educatie[2] += parseInt(rand.edu_gimnazial) || 0;
        educatie[3] += parseInt(rand.edu_liceal) || 0;
        educatie[4] += parseInt(rand.edu_postliceal) || 0;
        educatie[5] += parseInt(rand.edu_profesional) || 0;
        educatie[6] += parseInt(rand.edu_universitar) || 0;
    });

    /* Pastreaza judetele in ordine alfabetica */
    const judeteOrdonate = [
        'ALBA', 'ARAD', 'ARGES', 'BACAU', 'BIHOR', 'BISTRITA NASAUD', 'BOTOSANI',
        'BRAILA', 'BRASOV', 'BUZAU', 'CALARASI', 'CARAS SEVERIN', 'CLUJ', 'CONSTANTA',
        'COVASNA', 'DAMBOVITA', 'DOLJ', 'GALATI', 'GIURGIU', 'GORJ', 'HARGHITA',
        'HUNEDOARA', 'IALOMITA', 'IASI', 'ILFOV', 'MARAMURES', 'MEHEDINTI', 'MURES',
        'NEAMT', 'OLT', 'PRAHOVA', 'SALAJ', 'SATU MARE', 'SIBIU', 'SUCEAVA',
        'TELEORMAN', 'TIMIS', 'TULCEA', 'VALCEA', 'VASLUI', 'VRANCEA', 'MUNICIPIUL BUCURESTI'
    ];

    /* Afiseaza doar judetele care au date */
    const judete = judeteOrdonate.filter(j => someriPeJudet[j] !== undefined);
    const valori = judete.map(j => someriPeJudet[j]);

    /* Extragem valorile si le sortam crescator pentru a calcula percentila (topul) */
    const valoriSortate = Object.values(someriPeJudet).sort((a, b) => a - b);
    const numarTotalJudete = valoriSortate.length;

    /* Eticheta dinamica in functie de filtrul de varsta activ */
    const labelGrafic = varstaFiltru
        ? `Someri - ${document.getElementById('filtru-varsta').options[document.getElementById('filtru-varsta').selectedIndex].text}`
        : 'Numar Total Someri';

    /* Actualizeaza graficul bara - judete */
    grafic.data.labels = judete;
    grafic.data.datasets = [{
        label: labelGrafic,
        data: valori,
        backgroundColor: 'rgba(59, 130, 246, 0.7)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
    }];
    grafic.update();

    /* Actualizeaza graficul tort - urban vs rural */
    graficPie.data.datasets = [{
        data: [urban, rural],
        backgroundColor: ['#0ea5e9', '#ef4444'],
        hoverOffset: 4
    }];
    graficPie.update();

    /* Actualizeaza graficul bara - grupe de varsta */
    graficVarste.data.datasets = [{
        label: 'Numar Someri',
        data: varste,
        backgroundColor: [
            'rgba(59,130,246,0.7)', 'rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)',
            'rgba(239,68,68,0.7)', 'rgba(139,92,246,0.7)', 'rgba(236,72,153,0.7)'
        ],
        borderWidth: 1
    }];
    graficVarste.update();

    /* Actualizeaza graficul bara - nivel de educatie */
    graficEducatie.data.datasets = [{
        label: 'Numar Someri',
        data: educatie,
        backgroundColor: [
            'rgba(59,130,246,0.7)', 'rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)',
            'rgba(239,68,68,0.7)', 'rgba(139,92,246,0.7)', 'rgba(236,72,153,0.7)', 'rgba(14,165,233,0.7)'
        ],
        borderWidth: 1
    }];
    graficEducatie.update();

    /* Coordonatele GPS ale centrului fiecarui judet */
    const coordonate = {
        "ALBA": [46.07, 23.57], "ARAD": [46.18, 21.31], "ARGES": [44.85, 24.87],
        "BACAU": [46.56, 26.91], "BIHOR": [47.04, 21.91], "BISTRITA NASAUD": [47.13, 24.48],
        "BOTOSANI": [47.74, 26.66], "BRAILA": [45.26, 27.95], "BRASOV": [45.64, 25.59],
        "BUZAU": [45.15, 26.81], "CALARASI": [44.19, 27.33], "CARAS SEVERIN": [45.29, 21.88],
        "CLUJ": [46.77, 23.60], "CONSTANTA": [44.17, 28.63], "COVASNA": [45.86, 25.78],
        "DAMBOVITA": [44.93, 25.45], "DOLJ": [44.33, 23.79], "GALATI": [45.43, 28.05],
        "GIURGIU": [43.90, 25.96], "GORJ": [45.03, 23.27], "HARGHITA": [46.36, 25.80],
        "HUNEDOARA": [45.87, 22.90], "IALOMITA": [44.56, 27.36], "IASI": [47.15, 27.58],
        "ILFOV": [44.57, 26.20], "MARAMURES": [47.65, 23.58], "MEHEDINTI": [44.62, 22.65],
        "MURES": [46.54, 24.56], "NEAMT": [46.92, 26.37], "OLT": [44.42, 24.36],
        "PRAHOVA": [44.93, 26.02], "SALAJ": [47.19, 23.05], "SATU MARE": [47.79, 22.88],
        "SIBIU": [45.79, 24.14], "SUCEAVA": [47.65, 26.25], "TELEORMAN": [43.96, 25.33],
        "TIMIS": [45.75, 21.22], "TULCEA": [45.17, 28.80], "VALCEA": [45.09, 24.36],
        "VASLUI": [46.64, 27.73], "VRANCEA": [45.70, 27.18], "MUNICIPIUL BUCURESTI": [44.42, 26.10]
    };

    /* Sterge markerii existenti inainte de a adauga unii noi */
    harta.eachLayer(layer => {
        if (layer instanceof L.Marker) {
            harta.removeLayer(layer);
        }
    });

    /* Adauga markerele tip PIN clasic Leaflet colorat pentru fiecare judet */
    judete.forEach(j => {
        if (coordonate[j]) {
            let valoareCurenta = someriPeJudet[j];
            /* Gasim a cata valoare este in top, incepand de la cea mai mica */
            let indexTop = valoriSortate.indexOf(valoareCurenta);

            /* Calculam procentajul ca pozitie in top (de la 0% la 100%) */
            let procentaj = numarTotalJudete > 1 ? (indexTop / (numarTotalJudete - 1)) * 100 : 0;

            L.marker(coordonate[j], { icon: getPinIcon(procentaj) })
            .addTo(harta)
            .bindPopup(`<b>${j}</b><br>Șomeri: ${valoareCurenta.toLocaleString()}`);
        }
    });
}

/* Adauga un judet in graficul de comparatie */
function adaugaComparatie() {
    const jd = document.getElementById('compara-judet').value;
    if (!jd) return;
    /* Datele trebuie incarcate mai intai */
    if (dateCurente.length === 0) {
        alert('Apasa intai Aplica Filtrele ca sa incarci datele!');
        return;
    }
    /* Previne adaugarea duplicatelor */
    if (judeteComparate.includes(jd)) {
        alert('Judetul ' + jd + ' e deja in comparatie!');
        return;
    }
    judeteComparate.push(jd);
    deseneazaComparatie();
}

/* Sterge un judet din graficul de comparatie */
function stergeComparatie(jd) {
    judeteComparate = judeteComparate.filter(x => x !== jd);
    deseneazaComparatie();
}

/* Redeseneaza graficul de comparatie pe baza judetelor si criteriului selectat */
function deseneazaComparatie() {
    const select = document.getElementById('compara-criteriu');
    const criteriu = select.value;
    const numeCriteriu = select.options[select.selectedIndex].text;
    /* Rata somajului foloseste media, celelalte folosesc suma */
    const esteRata = (criteriu === 'rata_somaj');

    /* Calculeaza valoarea pentru fiecare judet selectat */
    const valori = judeteComparate.map(jd => {
        let suma = 0, nr = 0;
        dateCurente.forEach(rand => {
            let j = rand.judet.toUpperCase();
            if (j === 'MUN. BUCURESTI') j = 'MUNICIPIUL BUCURESTI';
            if (j === jd) {
                suma += parseFloat(rand[criteriu]) || 0;
                nr++;
            }
        });
        let val = (esteRata && nr > 0) ? (suma / nr) : suma;
        return Math.round(val * 100) / 100;
    });

    /* Actualizeaza datele graficului de comparatie */
    graficComparatie.data.labels = judeteComparate.slice();
    graficComparatie.data.datasets = [{
        label: numeCriteriu,
        data: valori,
        backgroundColor: 'rgba(16, 185, 129, 0.7)',
        borderColor: 'rgba(16, 185, 129, 1)',
        borderWidth: 1
    }];
    graficComparatie.options.plugins.title.text = 'Comparatie Judete - ' + numeCriteriu;
    graficComparatie.update();

    /* Randeaza lista de judete selectate cu buton de stergere */
    const lista = document.getElementById('lista-comparatie');
    lista.innerHTML = '';
    judeteComparate.forEach(jd => {
        const chip = document.createElement('span');
        chip.style.cssText = 'background:#1e40af; color:white; padding:5px 10px; border-radius:14px; font-size:0.85em; display:inline-flex; align-items:center; gap:6px;';
        chip.textContent = jd;
        const x = document.createElement('span');
        x.textContent = 'X';
        x.style.cssText = 'cursor:pointer; font-weight:bold;';
        x.onclick = () => stergeComparatie(jd);
        chip.appendChild(x);
        lista.appendChild(chip);
    });
}

/* Exporta datele curente ca CSV prin API-ul server-side */
function exportCSV() {
    if (dateCurente.length === 0) return alert("Nu sunt date!");
    const anStart   = document.getElementById('filtru-an-start').value;
    const lunaStart = document.getElementById('filtru-luna-start').value;
    const anStop    = document.getElementById('filtru-an-stop').value;
    const lunaStop  = document.getElementById('filtru-luna-stop').value;
    const judet     = document.getElementById('filtru-judet').value;
    const mediu     = document.getElementById('filtru-mediu').value;
    const sex       = document.getElementById('filtru-sex').value;

    let url = `api/export.php?format=csv&an_start=${anStart}&luna_start=${lunaStart}&an_stop=${anStop}&luna_stop=${lunaStop}`;
    if (judet) url += `&judet=${judet}`;
    if (mediu) url += `&mediu=${mediu}`;
    if (sex) url += `&sex=${sex}`;
    window.location.href = url;
}

/* Exporta graficul vizibil curent ca fisier SVG */
function exportSVG() {
    /* Determina care grafic este vizibil in momentul curent */
    const vizActiva = document.querySelector('.btn-view.active').innerText;
    let idCanvas = 'graficSomaj';
    if (vizActiva.includes('Mediu')) idCanvas = 'graficPie';
    else if (vizActiva.includes('Varsta')) idCanvas = 'graficVarste';
    else if (vizActiva.includes('Educatie')) idCanvas = 'graficEducatie';

    /* Inconjoara imaginea canvas in SVG si declanseza descarcarea */
    const canvas = document.getElementById(idCanvas);
    const imgURI = canvas.toDataURL("image/png");
    const svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="${canvas.width}" height="${canvas.height}">
        <image href="${imgURI}" width="${canvas.width}" height="${canvas.height}" />
    </svg>`;
    const blob = new Blob([svgContent], { type: "image/svg+xml;charset=utf-8" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "grafic_export.svg";
    link.click();
}

/* Exporta toate graficele ca PDF cu mai multe pagini */
function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape', 'mm', 'a4');

    /* Functie ajutatoare: randeaza un grafic pe un canvas temporar si returneaza ca imagine */
    function graficPeCanvas(tip, labels, datasets, titlu, callback) {
        const tmpCanvas = document.createElement('canvas');
        tmpCanvas.width = 1200;
        tmpCanvas.height = 600;
        document.body.appendChild(tmpCanvas);

        /* Fundal alb */
        const ctx = tmpCanvas.getContext('2d');
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, tmpCanvas.width, tmpCanvas.height);

        const chart = new Chart(tmpCanvas, {
            type: tip,
            data: { labels, datasets },
            plugins: [{
                id: 'customBackground',
                beforeDraw: (chart) => {
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.fillStyle = 'white';
                    ctx.fillRect(0, 0, chart.width, chart.height);
                    ctx.restore();
                }
            }],
            options: {
                responsive: false,
                animation: { duration: 0 },
                plugins: { title: { display: true, text: titlu } },
                scales: tip === 'pie' ? {} : { y: { beginAtZero: true } }
            }
        });

        /* Asteapta randarea, capteaza imaginea si curata canvas-ul temporar */
        setTimeout(() => {
            const imgData = tmpCanvas.toDataURL('image/jpeg', 0.98);
            chart.destroy();
            document.body.removeChild(tmpCanvas);
            callback(imgData);
        }, 200);
    }

    /* Extrage datele din graficele curente */
    const labelsBar = grafic.data.labels;
    const datasetsBar = grafic.data.datasets;
    const labelsPie = graficPie.data.labels;
    const datasetsPie = graficPie.data.datasets;
    const labelsVarste = graficVarste.data.labels;
    const datasetsVarste = graficVarste.data.datasets;
    const labelsEducatie = graficEducatie.data.labels;
    const datasetsEducatie = graficEducatie.data.datasets;

    /* Construieste PDF-ul pagina cu pagina (callback-uri inlantuite) */
    graficPeCanvas('bar', labelsBar, datasetsBar, 'Someri pe Judete', (img1) => {
        doc.setFontSize(16);
        doc.text('Someri pe Judete', 10, 15);
        doc.addImage(img1, 'JPEG', 10, 25, 270, 150);

        doc.addPage();
        graficPeCanvas('pie', labelsPie, datasetsPie, 'Distributie Urban vs Rural', (img2) => {
            doc.setFontSize(16);
            doc.text('Distributie Urban vs Rural', 10, 15);
            doc.addImage(img2, 'JPEG', 10, 25, 270, 150);

            doc.addPage();
            graficPeCanvas('bar', labelsVarste, datasetsVarste, 'Distributie pe Grupe de Varsta', (img3) => {
                doc.setFontSize(16);
                doc.text('Distributie pe Grupe de Varsta', 10, 15);
                doc.addImage(img3, 'JPEG', 10, 25, 270, 150);

                doc.addPage();
                graficPeCanvas('bar', labelsEducatie, datasetsEducatie, 'Distributie pe Nivel de Educatie', (img4) => {
                    doc.setFontSize(16);
                    doc.text('Distributie pe Nivel de Educatie', 10, 15);
                    doc.addImage(img4, 'JPEG', 10, 25, 270, 150);

                    doc.save('raport_und.pdf');
                });
            });
        });
    });
}

/* Exporta datele curente ca JSON prin API-ul server-side */
function exportJSON() {
    const anStart   = document.getElementById('filtru-an-start').value;
    const lunaStart = document.getElementById('filtru-luna-start').value;
    const anStop    = document.getElementById('filtru-an-stop').value;
    const lunaStop  = document.getElementById('filtru-luna-stop').value;
    const judet     = document.getElementById('filtru-judet').value;
    const mediu     = document.getElementById('filtru-mediu').value;
    const sex       = document.getElementById('filtru-sex').value;

    let url = `api/export.php?format=json&an_start=${anStart}&luna_start=${lunaStart}&an_stop=${anStop}&luna_stop=${lunaStop}`;
    if (judet) url += `&judet=${judet}`;
    if (mediu) url += `&mediu=${mediu}`;
    if (sex) url += `&sex=${sex}`;
    window.location.href = url;
}