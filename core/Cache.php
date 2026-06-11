<?php
// Incarca configurarea aplicatiei pentru a avea acces la CACHE_DIR si CACHE_TTL
require_once __DIR__ . '/../config/config.php';

// Clasa care gestioneaza salvarea si citirea rezultatelor din fisiere cache
class Cache {

    // Cauta datele in cache dupa cheie
    // Returneaza null daca fisierul nu exista sau a expirat
    public static function get(string $key) {
        $file = CACHE_DIR . md5($key) . '.cache';
        if (!file_exists($file)) return null;
        if (time() - filemtime($file) > CACHE_TTL) {
            unlink($file);
            return null;
        }
        return unserialize(file_get_contents($file));
    }

    // Salveaza datele in cache intr-un fisier identificat prin cheie
    public static function set(string $key, $data): void {
        $file = CACHE_DIR . md5($key) . '.cache';
        file_put_contents($file, serialize($data));
    }

    // Sterge toate fisierele cache din folder
    public static function clear(): void {
        foreach (glob(CACHE_DIR . '*.cache') as $file) {
            unlink($file);
        }
    }
}
?>