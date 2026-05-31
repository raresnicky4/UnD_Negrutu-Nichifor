<?php
require_once __DIR__ . '/../config/config.php';

class Cache {
    public static function get(string $key) {
        $file = CACHE_DIR . md5($key) . '.cache';
        if (!file_exists($file)) return null;
        if (time() - filemtime($file) > CACHE_TTL) {
            unlink($file);
            return null;
        }
        return unserialize(file_get_contents($file));
    }

    public static function set(string $key, $data): void {
        $file = CACHE_DIR . md5($key) . '.cache';
        file_put_contents($file, serialize($data));
    }

    public static function clear(): void {
        foreach (glob(CACHE_DIR . '*.cache') as $file) {
            unlink($file);
        }
    }
}
?>