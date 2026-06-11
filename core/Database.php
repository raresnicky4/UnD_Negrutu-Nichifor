<?php
// Incarca configurarea bazei de date
require_once __DIR__ . '/../config/config.php';

// Singleton - garanteaza o singura conexiune la MySQL in toata aplicatia
class Database {
    private static $instance = null;
    private $conn;

    // Deschide conexiunea la MySQL cu setari de securitate
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    // Prima oara creeaza conexiunea, apoi o reutilizeaza
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Returneaza conexiunea PDO pentru interogari SQL
    public function getConnection() {
        return $this->conn;
    }
}
?>