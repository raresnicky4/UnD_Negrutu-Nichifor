let harta;
let grafic;
let graficPie;
let graficVarste;
let dateCurente = [];

document.addEventListener("DOMContentLoaded", function() {
    const limiteRomania = [[43.5, 20.0], [48.5, 30.0]];

    harta = L.map('harta', {
        maxBounds: limiteRomania,
        maxBoundsViscosity: 1.0,
        minZoom: 6
    }).setView([45.9, 25.0], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(harta);

    const ctx = document.getElementById('graficSomaj').getContext('2d');
    grafic = new Chart(ctx, {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Șomeri pe Județe' } },
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

    const ctxPie = document.getElementById('graficPie').getContext('2d');
    graficPie = new Chart(ctxPie, {
        type: 'pie',
        data: { labels: ['Urban', 'Rural'], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Distribuție Urban vs Rural' } }
        }
    });

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
            plugins: { title: { display: true, text: 'Distribuție pe Grupe de Vârstă' } },
            scales: { y: { beginAtZero: true } }
        }
    });
});

function schimbaGrafic(tip, btn) {
    document.getElementById('container-bar').classList.add('hidden');
    document.getElementById('container-pie').classList.add('hidden');
    document.getElementById('container-varste').classList.add('hidden');
    document.querySelectorAll('.btn-view').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    if (tip === 'bar') document.getElementById('container-bar').classList.remove('hidden');
    else if (tip === 'pie') document.getElementById('container-pie').classList.remove('hidden');
    else if (tip === 'varste') document.getElementById('container-varste').classList.remove('hidden');
}

function aplicaFiltre() {
    const judet     = document.getElementById('filtru-judet').value.toUpperCase().trim();
    const lunaStart = document.getElementById('filtru-luna-start').value;
    const anStart   = document.getElementById('filtru-an-start').value;
    const lunaStop  = document.getElementById('filtru-luna-stop').value;
    const anStop    = document.getElementById('filtru-an-stop').value;
    const mediu     = document.getElementById('filtru-mediu').value;
    const varsta    = document.getElementById('filtru-varsta').value;
    const sex       = document.getElementById('filtru-sex').value;

    let url = `api/statistici.php?an_start=${anStart}&luna_start=${lunaStart}&an_stop=${anStop}&luna_stop=${lunaStop}`;
    if (judet) url += `&judet=${judet}`;
    if (mediu) url += `&mediu=${mediu}`;
    if (sex) url += `&sex=${sex}`;

    document.querySelector('.btn-aplica').textContent = 'Se încarcă...';
    document.querySelector('.btn-aplica').disabled = true;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            dateCurente = data;
            actualizeazaInterfata(data, varsta);
        })
        .catch(() => alert("Eroare la aducerea datelor!"))
        .finally(() => {
            document.querySelector('.btn-aplica').textContent = 'Aplică Filtrele';
            document.querySelector('.btn-aplica').disabled = false;
        });
}

function actualizeazaInterfata(date, varstaFiltru = '') {
    let someriPeJudet = {};
    let urban = 0, rural = 0;
    let varste = [0, 0, 0, 0, 0, 0];

    date.forEach(rand => {
        let j = rand.judet.toUpperCase();
        if (j === 'MUN. BUCURESTI') j = 'MUNICIPIUL BUCURESTI';
        if (j === 'TOTAL') return;
        if (!someriPeJudet[j]) someriPeJudet[j] = 0;

        if (varstaFiltru && rand[varstaFiltru] !== undefined) {
            someriPeJudet[j] += parseInt(rand[varstaFiltru]) || 0;
        } else {
            someriPeJudet[j] += parseInt(rand.numar_someri_filtrat ?? rand.numar_someri) || 0;
        }

        urban += parseInt(rand.urban) || 0;
        rural += parseInt(rand.rural) || 0;

        varste[0] += parseInt(rand.varsta_sub25) || 0;
        varste[1] += parseInt(rand.varsta_25_29) || 0;
        varste[2] += parseInt(rand.varsta_30_39) || 0;
        varste[3] += parseInt(rand.varsta_40_49) || 0;
        varste[4] += parseInt(rand.varsta_50_55) || 0;
        varste[5] += parseInt(rand.varsta_peste55) || 0;
    });

    const judeteOrdonate = [
        'ALBA', 'ARAD', 'ARGES', 'BACAU', 'BIHOR', 'BISTRITA NASAUD', 'BOTOSANI',
        'BRAILA', 'BRASOV', 'BUZAU', 'CALARASI', 'CARAS SEVERIN', 'CLUJ', 'CONSTANTA',
        'COVASNA', 'DAMBOVITA', 'DOLJ', 'GALATI', 'GIURGIU', 'GORJ', 'HARGHITA',
        'HUNEDOARA', 'IALOMITA', 'IASI', 'ILFOV', 'MARAMURES', 'MEHEDINTI', 'MURES',
        'NEAMT', 'OLT', 'PRAHOVA', 'SALAJ', 'SATU MARE', 'SIBIU', 'SUCEAVA',
        'TELEORMAN', 'TIMIS', 'TULCEA', 'VALCEA', 'VASLUI', 'VRANCEA', 'MUNICIPIUL BUCURESTI'
    ];

    const judete = judeteOrdonate.filter(j => someriPeJudet[j] !== undefined);
    const valori = judete.map(j => someriPeJudet[j]);

    const labelGrafic = varstaFiltru ? `Șomeri - ${document.getElementById('filtru-varsta').options[document.getElementById('filtru-varsta').selectedIndex].text}` : 'Număr Total Șomeri';

    grafic.data.labels = judete;
    grafic.data.datasets = [{
        label: labelGrafic,
        data: valori,
        backgroundColor: 'rgba(59, 130, 246, 0.7)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
    }];
    grafic.update();

    graficPie.data.datasets = [{
        data: [urban, rural],
        backgroundColor: ['#0ea5e9', '#ef4444'],
        hoverOffset: 4
    }];
    graficPie.update();

    graficVarste.data.datasets = [{
        label: 'Număr Șomeri',
        data: varste,
        backgroundColor: [
            'rgba(59,130,246,0.7)', 'rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)',
            'rgba(239,68,68,0.7)', 'rgba(139,92,246,0.7)', 'rgba(236,72,153,0.7)'
        ],
        borderWidth: 1
    }];
    graficVarste.update();

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

    harta.eachLayer(layer => { if (layer instanceof L.Marker) harta.removeLayer(layer); });

    judete.forEach(j => {
        if (coordonate[j]) {
            L.marker(coordonate[j])
                .addTo(harta)
                .bindPopup(`<b>${j}</b><br>Șomeri: ${someriPeJudet[j].toLocaleString()}`);
        }
    });
}

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

function exportSVG() {
    const vizActiva = document.querySelector('.btn-view.active').innerText;
    let idCanvas = 'graficSomaj';
    if (vizActiva.includes('Mediu')) idCanvas = 'graficPie';
    else if (vizActiva.includes('Vârstă')) idCanvas = 'graficVarste';

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

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape', 'mm', 'a4');

    function graficPeCanvas(tip, labels, datasets, titlu, callback) {
        const tmpCanvas = document.createElement('canvas');
        tmpCanvas.width = 1200;
        tmpCanvas.height = 600;
        document.body.appendChild(tmpCanvas);

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

        setTimeout(() => {
            const imgData = tmpCanvas.toDataURL('image/jpeg', 0.98);
            chart.destroy();
            document.body.removeChild(tmpCanvas);
            callback(imgData);
        }, 200);
    }

    const labelsBar = grafic.data.labels;
    const datasetsBar = grafic.data.datasets;
    const labelsPie = graficPie.data.labels;
    const datasetsPie = graficPie.data.datasets;
    const labelsVarste = graficVarste.data.labels;
    const datasetsVarste = graficVarste.data.datasets;

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

                doc.save('raport_und.pdf');
            });
        });
    });
}

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