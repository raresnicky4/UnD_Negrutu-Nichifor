<?php
// Clasa care standardizeaza raspunsurile JSON trimise catre browser
class Response {

    // Trimite datele ca JSON cu codul de status HTTP specificat
    public static function json($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Trimite un mesaj de eroare ca JSON
    public static function error(string $message, int $status = 400): void {
        self::json(['error' => $message], $status);
    }
}
?>