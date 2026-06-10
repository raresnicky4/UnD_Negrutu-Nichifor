<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Cache.php';

// Clasa care gestioneaza interogarile bazei de date pentru statisticile de somaj
class StatisticiModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Returneaza statisticile filtrate din baza de date
    // Suporta filtrare dupa judet, interval de timp, sex si mediu (urban/rural)
    // Rezultatele sunt stocate in cache pentru performanta
    public function filtreaza(array $filters): array {
        // Genereaza o cheie unica de cache bazata pe filtrele aplicate
        $cacheKey = 'statistici_' . md5(json_encode($filters));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;

        // Selecteaza coloana corecta in functie de filtrul de sex sau mediu
        $coloana = "numar_someri";
        if (!empty($filters['sex'])) {
            if ($filters['sex'] === 'masculin') $coloana = "someri_barbati";
            if ($filters['sex'] === 'feminin')  $coloana = "someri_femei";
        } elseif (!empty($filters['mediu'])) {
            if ($filters['mediu'] === 'urban') $coloana = "urban";
            if ($filters['mediu'] === 'rural') $coloana = "rural";
        }

        // Interogarea de baza - coloana filtrata este redenumita ca numar_someri_filtrat
        $sql = "SELECT *, {$coloana} as numar_someri_filtrat FROM statistici WHERE 1=1";
        $params = [];

        // Filtru dupa judet
        if (!empty($filters['judet'])) {
            $sql .= " AND judet = :judet";
            $params[':judet'] = $filters['judet'];
        }

        // Filtru dupa data de inceput (an + luna)
        if (!empty($filters['an_start'])) {
            $sql .= " AND (anul > :an_start OR (anul = :an_start2 AND luna >= :luna_start))";
            $params[':an_start']   = $filters['an_start'];
            $params[':an_start2']  = $filters['an_start'];
            $params[':luna_start'] = $filters['luna_start'] ?? 1;
        }

        // Filtru dupa data de sfarsit (an + luna)
        if (!empty($filters['an_stop'])) {
            $sql .= " AND (anul < :an_stop OR (anul = :an_stop2 AND luna <= :luna_stop))";
            $params[':an_stop']   = $filters['an_stop'];
            $params[':an_stop2']  = $filters['an_stop'];
            $params[':luna_stop'] = $filters['luna_stop'] ?? 12;
        }

        $sql .= " ORDER BY anul, luna, judet";

        // Executa interogarea cu prepared statement (protectie SQL injection)
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        // Salveaza rezultatul in cache pentru cererile viitoare
        Cache::set($cacheKey, $result);
        return $result;
    }

    // Returneaza lista tuturor judetelor distincte din baza de date
    public function totiJudetii(): array {
        $stmt = $this->db->query("SELECT DISTINCT judet FROM statistici ORDER BY judet");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Returneaza lista tuturor anilor disponibili in baza de date
    public function toateAnii(): array {
        $stmt = $this->db->query("SELECT DISTINCT anul FROM statistici ORDER BY anul DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>