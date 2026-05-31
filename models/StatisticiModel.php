<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Cache.php';

class StatisticiModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function filtreaza(array $filters): array {
        $cacheKey = 'statistici_' . md5(json_encode($filters));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;

        $selectMediu = "numar_someri";
        if (!empty($filters['mediu'])) {
            if ($filters['mediu'] === 'urban') $selectMediu = "urban";
            if ($filters['mediu'] === 'rural') $selectMediu = "rural";
        }

        $sql = "SELECT *, {$selectMediu} as numar_someri_filtrat FROM statistici WHERE 1=1";
        $params = [];

        if (!empty($filters['judet'])) {
            $sql .= " AND judet = :judet";
            $params[':judet'] = $filters['judet'];
        }
        if (!empty($filters['an_start'])) {
            $sql .= " AND (anul > :an_start OR (anul = :an_start2 AND luna >= :luna_start))";
            $params[':an_start']   = $filters['an_start'];
            $params[':an_start2']  = $filters['an_start'];
            $params[':luna_start'] = $filters['luna_start'] ?? 1;
        }
        if (!empty($filters['an_stop'])) {
            $sql .= " AND (anul < :an_stop OR (anul = :an_stop2 AND luna <= :luna_stop))";
            $params[':an_stop']   = $filters['an_stop'];
            $params[':an_stop2']  = $filters['an_stop'];
            $params[':luna_stop'] = $filters['luna_stop'] ?? 12;
        }

        $sql .= " ORDER BY anul, luna, judet";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        Cache::set($cacheKey, $result);
        return $result;
    }

    public function totiJudetii(): array {
        $stmt = $this->db->query("SELECT DISTINCT judet FROM statistici ORDER BY judet");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function toateAnii(): array {
        $stmt = $this->db->query("SELECT DISTINCT anul FROM statistici ORDER BY anul DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>