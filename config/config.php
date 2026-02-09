<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wedding'); // Changé de wedding_budget à wedding
// Durée de session en secondes (30 minutes)
define('SESSION_TIMEOUT', 1800);
// Connexion à la base de données
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Fonction pour formater les montants en FCFA
function formatCurrency($amount) {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

// Timezone
date_default_timezone_set('Africa/Porto-Novo');
?>