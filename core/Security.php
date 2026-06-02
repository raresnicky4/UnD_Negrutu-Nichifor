<?php
class Security {
    public static function sanitize(string $input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public static function validateFilters(array $params): array {
        $allowed_judete = [
            'ALBA', 'ARAD', 'ARGES', 'BACAU', 'BIHOR', 'BISTRITA-NASAUD', 'BOTOSANI',
            'BRAILA', 'BRASOV', 'BUZAU', 'CALARASI', 'CARAS-SEVERIN', 'CLUJ', 'CONSTANTA',
            'COVASNA', 'DAMBOVITA', 'DOLJ', 'GALATI', 'GIURGIU', 'GORJ', 'HARGHITA',
            'HUNEDOARA', 'IALOMITA', 'IASI', 'ILFOV', 'MARAMURES', 'MEHEDINTI', 'MURES',
            'NEAMT', 'OLT', 'PRAHOVA', 'SALAJ', 'SATU MARE', 'SIBIU', 'SUCEAVA',
            'TELEORMAN', 'TIMIS', 'TULCEA', 'VALCEA', 'VASLUI', 'VRANCEA',
            'MUNICIPIUL BUCURESTI', 'MUN. BUC.'
        ];
        $allowed_mediu = ['urban', 'rural'];
        $allowed_sex = ['masculin', 'feminin'];

        $judet = strtoupper(trim($params['judet'] ?? ''));

        return [
            'judet'      => in_array($judet, $allowed_judete) ? $judet : null,
            'mediu'      => in_array(strtolower($params['mediu'] ?? ''), $allowed_mediu) ? strtolower($params['mediu']) : null,
            'sex'        => in_array(strtolower($params['sex'] ?? ''), $allowed_sex) ? strtolower($params['sex']) : null,
            'an_start'   => filter_var($params['an_start'] ?? date('Y') - 1, FILTER_VALIDATE_INT),
            'luna_start' => filter_var($params['luna_start'] ?? 1, FILTER_VALIDATE_INT),
            'an_stop'    => filter_var($params['an_stop'] ?? date('Y'), FILTER_VALIDATE_INT),
            'luna_stop'  => filter_var($params['luna_stop'] ?? 12, FILTER_VALIDATE_INT),
        ];
    }
}
?>