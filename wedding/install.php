<?php
declare(strict_types=1);
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=UTF-8');
// FIX: Vérifier avant de définir
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}
/**
 * install.php — Installation de la base de données
 * Exécutez ce fichier UNE SEULE FOIS pour initialiser l'application.
 */

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'wedding';

$steps  = [];
$errors = [];

function step(string $msg, array &$steps): void {
    $steps[] = '✓ ' . $msg;
    echo "✓ $msg<br>\n";
    flush();
}
function fail(string $msg, array &$errors): void {
    $errors[] = '✗ ' . $msg;
    echo "<span style='color:red'>✗ $msg</span><br>\n";
    flush();
}

try {
    // 1. Connexion MySQL
    $conn = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
    );
    step("Connexion MySQL établie", $steps);

    // 2. Création base de données
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE `$dbname`");
    step("Base de données '$dbname' prête", $steps);

    // 3. Table users
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        username   VARCHAR(50)  NOT NULL UNIQUE,
        email      VARCHAR(100) NOT NULL UNIQUE,
        password   VARCHAR(255) NOT NULL,
        full_name  VARCHAR(100) NULL,
        role       ENUM('admin','user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX idx_username (username),
        INDEX idx_email    (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'users' créée", $steps);

    // 4. Table categories
    $conn->exec("CREATE TABLE IF NOT EXISTS categories (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(255) NOT NULL UNIQUE,
        color         VARCHAR(7)   DEFAULT '#3498db',
        icon          VARCHAR(50)  DEFAULT 'fas fa-folder',
        display_order INT          DEFAULT 0,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'categories' créée", $steps);

    // 5. Table expenses
    $conn->exec("CREATE TABLE IF NOT EXISTS expenses (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        user_id      INT           NOT NULL,
        category_id  INT           NOT NULL,
        name         VARCHAR(255)  NOT NULL,
        quantity     INT           NOT NULL DEFAULT 1,
        unit_price   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        frequency    INT           NOT NULL DEFAULT 1,
        paid         BOOLEAN       DEFAULT FALSE,
        payment_date DATE          NULL,
        notes        TEXT          NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_user     (user_id),
        INDEX idx_category (category_id),
        INDEX idx_paid     (paid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'expenses' créée", $steps);

    // 6. Table wedding_dates
    $conn->exec("CREATE TABLE IF NOT EXISTS wedding_dates (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        user_id      INT  NOT NULL UNIQUE,
        wedding_date DATE NOT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'wedding_dates' créée", $steps);

    // 7. Catégories par défaut
    $cats = [
        ['Connaissance',         '#3498db', 'fas fa-handshake',           1],
        ['Dot',                  '#9b59b6', 'fas fa-gift',                 2],
        ['Mariage civil',        '#e74c3c', 'fas fa-landmark',             3],
        ['Bénédiction nuptiale', '#2ecc71', 'fas fa-church',               4],
        ['Logistique',           '#1abc9c', 'fas fa-truck',                5],
        ['Réception',            '#f39c12', 'fas fa-glass-cheers',         6],
        ['Coût indirect',        '#95a5a6', 'fas fa-exclamation-triangle', 7],
    ];
    $stmtCat = $conn->prepare("INSERT IGNORE INTO categories (name,color,icon,display_order) VALUES (?,?,?,?)");
    foreach ($cats as $c) $stmtCat->execute($c);
    step("Catégories de base insérées (" . count($cats) . ")", $steps);

    // 8. Utilisateur admin par défaut
    $adminPass = password_hash('Admin@1312', PASSWORD_DEFAULT);
    $stmtUser  = $conn->prepare("INSERT IGNORE INTO users (username,email,password,full_name,role) VALUES (?,?,?,?,?)");
    $stmtUser->execute(['Administrateur', 'liferopro@gmail.com', $adminPass, 'Administrateur Principal', 'admin']);
    step("Compte admin créé (login: Administrateur / pass: Admin@1312)", $steps);

    // 9. Données de démonstration
    $uid = (int)$conn->query("SELECT id FROM users WHERE username='Administrateur' LIMIT 1")->fetchColumn();
    $catIds = [];
    foreach ($conn->query("SELECT id,display_order FROM categories ORDER BY display_order") as $row) {
        $catIds[$row['display_order']] = $row['id'];
    }

    $demo = [
        // Connaissance (1)
        [$uid,$catIds[1],'Enveloppe symbolique',        2,  2000,1,1],
        [$uid,$catIds[1],'Boissons (jus de raisins)',   2,  5000,1,1],
        [$uid,$catIds[1],'Déplacement',                 1,  5000,1,1],
        // Dot (2)
        [$uid,$catIds[2],'Bible',                       1,  6000,1,0],
        [$uid,$catIds[2],'Valise',                      1, 10000,1,0],
        [$uid,$catIds[2],'Pagne vlisco demi-pièce',     2, 27000,1,0],
        [$uid,$catIds[2],'Pagne côte d\'ivoire',        5,  6500,1,0],
        [$uid,$catIds[2],'Pagne Ghana demi-pièce',      4,  6500,1,0],
        [$uid,$catIds[2],'Ensemble chaînes',            3,  3000,1,0],
        [$uid,$catIds[2],'Chaussures',                  3,  3000,1,0],
        [$uid,$catIds[2],'Sac à main',                  2,  3500,1,0],
        [$uid,$catIds[2],'Montre et bracelet',          2,  3000,1,0],
        [$uid,$catIds[2],'Série de bols',               3,  5500,1,0],
        [$uid,$catIds[2],'Assiettes verre (demi-doz.)', 2,  4800,1,0],
        [$uid,$catIds[2],'Assiettes plastique (doz.)',  2,  3000,1,0],
        [$uid,$catIds[2],'Série de casseroles',         1,  7000,1,0],
        [$uid,$catIds[2],'Marmites (1-3 kg)',           1, 11000,1,0],
        [$uid,$catIds[2],'Ustensiles de cuisine',       1,  8000,1,0],
        [$uid,$catIds[2],'Gaz + accessoires',           1, 25000,1,0],
        [$uid,$catIds[2],'Seau soins corporels',        1, 10000,1,0],
        [$uid,$catIds[2],'Enveloppe fille',             1,100000,1,0],
        [$uid,$catIds[2],'Enveloppe famille',           1, 25000,1,0],
        [$uid,$catIds[2],'Enveloppe frères/sœurs',      1, 10000,1,0],
        [$uid,$catIds[2],'Liqueurs',                    2, 10000,1,0],
        [$uid,$catIds[2],'Jus de raisins',             10,  2500,1,0],
        [$uid,$catIds[2],'Collation spirituelle',       1, 45000,1,0],
        // Mairie (3)
        [$uid,$catIds[3],'Frais dossier mairie',        1, 50000,1,0],
        [$uid,$catIds[3],'Petite réception mairie',     1, 50000,1,0],
        // Église (4)
        [$uid,$catIds[4],'Robe de mariée',              1, 20000,1,0],
        [$uid,$catIds[4],'Costume marié',               1, 25000,1,0],
        [$uid,$catIds[4],'Chaussures mariés',           2, 25000,1,0],
        [$uid,$catIds[4],'Alliances',                   1, 15000,1,0],
        [$uid,$catIds[4],'Tenues cortège (homme)',      3, 15000,1,0],
        [$uid,$catIds[4],'Tenues cortège (femme)',      4, 15000,1,0],
        // Logistique (5)
        [$uid,$catIds[5],'Location de salle',           1,150000,1,0],
        [$uid,$catIds[5],'Location de véhicule',        2, 35000,1,0],
        [$uid,$catIds[5],'Carburant',                  20,   680,1,0],
        [$uid,$catIds[5],'Prise de vue (photo/vidéo)', 1, 30000,1,0],
        [$uid,$catIds[5],'Sonorisation',                1, 20000,1,0],
        [$uid,$catIds[5],'Conception flyers/programmes',1,  2000,1,0],
        // Réception (6)
        [$uid,$catIds[6],'Boissons (200 personnes)',  200,   600,1,0],
        [$uid,$catIds[6],'Poulets',                    30,  2500,1,0],
        [$uid,$catIds[6],'Porc',                        1, 30000,1,0],
        [$uid,$catIds[6],'Poissons',                    2, 35000,1,0],
        [$uid,$catIds[6],'Sacs de riz',                 1, 32000,1,0],
        [$uid,$catIds[6],'Farine d\'igname',           20,   500,1,0],
        [$uid,$catIds[6],'Maïs pour akassa',           20,   200,1,0],
        [$uid,$catIds[6],'Ingrédients cuisine',         1, 30000,1,0],
        [$uid,$catIds[6],'Gâteau de mariage',           1, 25000,1,0],
        // Coût indirect (7)
        [$uid,$catIds[7],'Imprévus divers',             1, 75000,1,0],
    ];

    $stmtExp = $conn->prepare("INSERT IGNORE INTO expenses
        (user_id,category_id,name,quantity,unit_price,frequency,paid) VALUES (?,?,?,?,?,?,?)");
    foreach ($demo as $d) $stmtExp->execute($d);
    step("Données de démonstration insérées (" . count($demo) . " dépenses)", $steps);

    echo "<br><strong style='color:green;font-size:1.2rem'>✅ Installation terminée avec succès !</strong><br><br>";
    echo "<a href='index.php' style='display:inline-block;margin-top:15px;padding:12px 30px;background:#8b4f8d;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>🚀 Accéder à l'application</a>";
    echo " &nbsp; ";
    echo "<a href='auth/login.php' style='display:inline-block;margin-top:15px;padding:12px 30px;background:#5d2f5f;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>🔑 Se connecter</a>";

} catch(PDOException $e) {
    fail("Erreur MySQL : " . $e->getMessage(), $errors);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Installation — Budget Mariage PJPM</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#faf8f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;max-width:600px;width:100%;border-radius:16px;box-shadow:0 8px 30px rgba(139,79,141,.15);overflow:hidden}
.box-header{background:linear-gradient(135deg,#8b4f8d,#5d2f5f);color:white;padding:30px;text-align:center}
.box-header h1{font-size:1.8rem;margin-bottom:8px}
.box-header p{opacity:.9}
.box-body{padding:30px;line-height:1.8;font-size:.95rem}
</style>
</head>
<body>
<div class="box">
<div class="box-header">
  <h1>💍 Budget Mariage PJPM</h1>
  <p>Installation de la base de données</p>
</div>
<div class="box-body">
</div>
</div>
</body>
</html>
