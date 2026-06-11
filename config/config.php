<?php
// Date de conectare la baza de date MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'somaj_romania');
define('DB_USER', 'root');
define('DB_PASS', 'admin');
define('DB_CHARSET', 'utf8mb4');

// Configurare cache - folderul unde se salveaza fisierele si durata de viata in secunde (1 ora)
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_TTL', 3600);

// URL-ul de baza al aplicatiei
define('BASE_URL', 'http://localhost/UnD_Negrutu-Nichifor');
?>