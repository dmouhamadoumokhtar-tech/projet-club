<?php
/**
 * Connexion MySQL partagée (Railway MySQL, MySQL local ou TiDB Cloud sur Vercel).
 */

require_once __DIR__ . '/stockage.php';

if (!function_exists('cslmdb_connexion_mysql')) {

    function cslmdb_parametres_mysql() {
        $mysql_url = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: getenv('DATABASE_URL');
        if ($mysql_url && ($parts = parse_url($mysql_url)) && isset($parts['host'])) {
            return [
                'hote' => $parts['host'],
                'utilisateur' => isset($parts['user']) ? urldecode($parts['user']) : 'root',
                'motdepasse' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
                'base' => isset($parts['path']) ? ltrim($parts['path'], '/') : 'railway',
                'port' => isset($parts['port']) ? (int) $parts['port'] : 3306,
            ];
        }
        return [
            'hote' => getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost',
            'base' => getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'club_scientifique',
            'utilisateur' => getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'club_sicentifique',
            'motdepasse' => getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'A1956'),
            'port' => (int) (getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306),
        ];
    }

    /** Ouvre (une seule fois par requête) la connexion MySQL et la retourne. */
    function cslmdb_connexion_mysql() {
        static $connexion = null;
        if ($connexion !== null) {
            return $connexion;
        }
        $p = cslmdb_parametres_mysql();
        mysqli_report(MYSQLI_REPORT_OFF);
        $mysqli = mysqli_init();
        $flags = 0;
        // TiDB Cloud (Vercel) exige une connexion chiffrée TLS
        if (getenv('DB_SSL') || strpos($p['hote'], 'tidbcloud.com') !== false) {
            $ca = null;
            foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt', '/etc/ssl/cert.pem'] as $chemin) {
                if (file_exists($chemin)) {
                    $ca = $chemin;
                    break;
                }
            }
            $mysqli->ssl_set(null, null, $ca, null, null);
            $flags = MYSQLI_CLIENT_SSL;
        }
        if (!@$mysqli->real_connect($p['hote'], $p['utilisateur'], $p['motdepasse'], $p['base'], $p['port'], null, $flags)) {
            throw new Exception('Erreur connexion  : ' . mysqli_connect_error());
        }
        $mysqli->set_charset('utf8mb4');
        $connexion = $mysqli;
        return $connexion;
    }
}
