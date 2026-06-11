\# UnD - Unemployment Data Visualizer



\*\*Platformă Web pentru vizualizarea și analiza datelor despre șomajul din România\*\*



Dezvoltat de: \*\*Nichifor Rareș\*\* și \*\*Negruțu Robert\*\*  

Facultatea de Informatică, Universitatea "Alexandru Ioan Cuza" Iași  

Disciplina: Tehnologii Web



\---



\## Descriere



UnD este un instrument Web de vizualizare și comparare multi-criterială a datelor publice despre șomajul din România, pe o perioadă de peste 16 luni. Datele sunt importate automat de pe \[data.gov.ro](https://data.gov.ro/dataset?q=somaj).



\---



\## Funcționalități



\- Vizualizare date pe județe, nivel de educație, grupe de vârstă, mediu, perioadă de timp și sex

\- 5 tipuri de grafice: bare județe, tort urban/rural, bare vârste, bare educație, comparație județe

\- Hartă interactivă cu markeri pe județe (Leaflet + OpenStreetMap)

\- Export în formatele \*\*CSV\*\*, \*\*JSON\*\*, \*\*SVG\*\* și \*\*PDF\*\*

\- Filtre multiple: județ, interval de timp, mediu, grupă vârstă, nivel educație, sex

\- Modul de administrare cu management cache și reimport date

\- Mecanism propriu de caching pentru performanță



\---



\## Tehnologii folosite



\- \*\*Backend\*\*: PHP 8, MySQL, PDO

\- \*\*Frontend\*\*: HTML5, CSS3, JavaScript (ES6+)

\- \*\*Hărți\*\*: Leaflet.js + OpenStreetMap

\- \*\*Grafice\*\*: Chart.js

\- \*\*Export PDF\*\*: jsPDF

\- \*\*Date\*\*: data.gov.ro API public



\---



\## Instalare



\### Cerințe

\- XAMPP (Apache + PHP 8+)

\- MySQL 8.0+



\### Pași



1\. Clonează repo-ul în folderul `htdocs`:

```bash

git clone https://github.com/raresnicky4/UnD\_Negrutu-Nichifor.git

```



2\. Creează baza de date în MySQL:

```sql

CREATE DATABASE somaj\_romania CHARACTER SET utf8mb4;

```



3\. Importă datele:

```bash

mysql -u root -p somaj\_romania < Baza\_Date.sql

```



4\. Verifică configurarea în `config/config.php`:

```php

define('DB\_HOST', 'localhost');

define('DB\_NAME', 'somaj\_romania');

define('DB\_USER', 'root');

define('DB\_PASS', 'admin');

```



5\. Accesează aplicația la `http://localhost/UnD\_Negrutu-Nichifor/`



\---



\## Structura proiectului
UnD\_Negrutu-Nichifor/

├── index.php              # Pagina principala

├── Baza\_Date.sql          # Dump baza de date

├── admin/

│   └── index.php          # Modul administrare

├── api/

│   ├── statistici.php     # API date statistici

│   └── export.php         # API export CSV/JSON

├── core/

│   ├── Cache.php          # Mecanism caching

│   ├── Database.php       # Conexiune baza de date

│   ├── DataImporter.php   # Import date data.gov.ro

│   ├── Response.php       # Raspunsuri JSON

│   └── Security.php       # Validare si sanitizare

├── models/

│   └── StatisticiModel.php # Model date statistici

├── config/

│   └── config.php         # Configurare aplicatie

└── public/

├── css/style.css      # Stiluri CSS

└── js/app.js          # Logica client

---



\## Modul Admin



Accesibil la `http://localhost/UnD\_Negrutu-Nichifor/admin/`  

Parola implicită: `admin123`



Funcționalități admin:

\- Statistici generale bază de date

\- Ștergere cache

\- Reimport date pentru o lună specifică

\- Vizualizare status luni importate



\---



\## Sursa datelor



Datele sunt importate automat de pe portalul open data al României:  

\[https://data.gov.ro/dataset?q=somaj](https://data.gov.ro/dataset?q=somaj)



Perioada acoperită: \*\*ianuarie 2023 - mai 2025\*\* (29 luni)



\---



\## Licență



Acest proiect este licențiat sub \[MIT License](LICENSE).

