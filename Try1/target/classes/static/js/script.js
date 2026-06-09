let harta;
let grafic;
let graficPie;
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
        options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Șomeri pe Județe' } }, scales: { y: { beginAtZero: true } } }
    });

    const ctxPie = document.getElementById('graficPie').getContext('2d');
    graficPie = new Chart(ctxPie, {
        type: 'pie',
        data: { labels: ['Urban', 'Rural'], datasets: [] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Distribuție Mediu (Urban vs Rural)' } } }
    });
});

function schimbaGrafic(tip, btn) {
    document.getElementById('container-bar').classList.add('hidden');
    document.getElementById('container-pie').classList.add('hidden');
    document.querySelectorAll('.btn-view').forEach(el => el.classList.remove('active'));

    btn.classList.add('active');

    if (tip === 'bar') {
        document.getElementById('container-bar').classList.remove('hidden');
    } else if (tip === 'pie') {
        document.getElementById('container-pie').classList.remove('hidden');
    }
}

function aplicaFiltre() {
    const judet = document.getElementById('filtru-judet').value.toUpperCase();
    const an = document.getElementById('filtru-an').value;
    const mediu = document.getElementById('filtru-mediu').value;
    const educatie = document.getElementById('filtru-educatie').value;

    let url = 'http://localhost:8081/api/statistici/filtreaza?';
    if (judet) url += `judet=${judet}&`;
    if (an) url += `an=${an}&`;
    if (mediu) url += `mediu=${mediu}&`;
    if (educatie) url += `educatie=${educatie}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            proceseazaDateLuna(data);
        })
        .catch(error => {
            alert("Eroare la aducerea datelor!");
        });
}

function proceseazaDateLuna(toateDatele) {
    const lunaStart = parseInt(document.getElementById('filtru-luna-start').value) || 1;
    const lunaEnd = parseInt(document.getElementById('filtru-luna-end').value) || 12;

    dateCurente = toateDatele.filter(r => r.luna >= lunaStart && r.luna <= lunaEnd);
    actualizeazaInterfata(dateCurente);
}

function actualizeazaInterfata(date) {
    let someriPeJudet = {};
    let urban = 0;
    let rural = 0;

    date.forEach(rand => {
        let j = rand.judet ? rand.judet.toUpperCase() : "NECUNOSCUT";
        if (!someriPeJudet[j]) someriPeJudet[j] = 0;
        someriPeJudet[j] += rand.numarSomeri;

        if (rand.mediu === 'Urban') urban += rand.numarSomeri;
        if (rand.mediu === 'Rural') rural += rand.numarSomeri;
    });

    const judete = Object.keys(someriPeJudet);
    const valori = Object.values(someriPeJudet);

    grafic.data.labels = judete;
    grafic.data.datasets = [{
        label: 'Număr Total Șomeri',
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

    const coordonateGenerice = {
        "IASI": [47.15, 27.58], "BUCURESTI": [44.42, 26.10], "CLUJ": [46.77, 23.60], "TIMIS": [45.75, 21.22],
        "BRASOV": [45.64, 25.59], "CONSTANTA": [44.17, 28.63], "DOLJ": [44.33, 23.79], "ALBA": [46.07, 23.57],
        "ARAD": [46.18, 21.31], "ARGES": [44.85, 24.87], "BACAU": [46.56, 26.91], "BIHOR": [47.04, 21.91],
        "BISTRITA-NASAUD": [47.13, 24.48], "BOTOSANI": [47.74, 26.66], "BRAILA": [45.26, 27.95], "BUZAU": [45.15, 26.81],
        "CALARASI": [44.19, 27.33], "CARAS-SEVERIN": [45.29, 21.89], "COVASNA": [45.86, 25.78], "DAMBOVITA": [44.93, 25.45],
        "GALATI": [45.43, 28.05], "GIURGIU": [43.90, 25.96], "GORJ": [45.03, 23.27], "HARGHITA": [46.36, 25.80],
        "HUNEDOARA": [45.87, 22.90], "IALOMITA": [44.56, 27.36], "MARAMURES": [47.65, 23.58], "MEHEDINTI": [44.62, 22.65],
        "MURES": [46.54, 24.56], "NEAMT": [46.92, 26.37], "OLT": [44.42, 24.36], "PRAHOVA": [44.93, 26.02],
        "SALAJ": [47.19, 23.05], "SATU MARE": [47.79, 22.88], "SIBIU": [45.79, 24.14], "SUCEAVA": [47.65, 26.25],
        "TELEORMAN": [43.96, 25.33], "TULCEA": [45.17, 28.80], "VASLUI": [46.64, 27.73], "VALCEA": [45.09, 24.36], "VRANCEA": [45.70, 27.18]
    };

    harta.eachLayer((layer) => { if (layer instanceof L.Marker) { harta.removeLayer(layer); } });

    judete.forEach(j => {
        let judetClean = j.trim();
        if(coordonateGenerice[judetClean]) {
            L.marker(coordonateGenerice[judetClean])
             .addTo(harta)
             .bindPopup(`<b>${judetClean}</b><br>Șomeri: ${someriPeJudet[j]}`);
        }
    });
}

function exportCSV() {
    if(dateCurente.length === 0) return alert("Nu sunt date!");
    let csvContent = "data:text/csv;charset=utf-8,Judet,An,Luna,GrupaVarsta,Mediu,NumarSomeri\n";
    dateCurente.forEach(rand => {
        let row = `${rand.judet},${rand.anul},${rand.luna},${rand.grupaVarsta},${rand.mediu},${rand.numarSomeri}`;
        csvContent += row + "\r\n";
    });
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "raport_somaj.csv");
    document.body.appendChild(link);
    link.click();
}

function exportPDF() {
    const element = document.getElementById('continut-pdf');
    document.querySelector('.view-toggles').style.display = 'none';

    html2pdf().set({
        margin: 10,
        filename: 'raport_und.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    }).from(element).save().then(() => {
        document.querySelector('.view-toggles').style.display = 'flex';
    });
}

function exportSVG() {
    const vizAcativa = document.querySelector('.btn-view.active').innerText;
    let idCanvas = 'graficSomaj';
    if(vizAcativa.includes('Distribuție')) idCanvas = 'graficPie';

    const canvas = document.getElementById(idCanvas);
    const imgURI = canvas.toDataURL("image/png");

    const svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="${canvas.width}" height="${canvas.height}">
        <image href="${imgURI}" width="${canvas.width}" height="${canvas.height}" />
    </svg>`;

    const blob = new Blob([svgContent], {type: "image/svg+xml;charset=utf-8"});
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `grafic_export.svg`;
    link.click();
}