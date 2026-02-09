<?php
    declare(strict_types=1);
    ini_set('default_charset', 'UTF-8');
    header('Content-Type: text/html; charset=UTF-8');

    /**
     * Script d'installation de la base de données
     * Exécutez ce fichier une seule fois pour créer la base de données et les tables
     */

    // Connexion sans base de données pour la création
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'wedding';

    try {
        // Connexion au serveur MySQL
     $conn = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    // Création de la base de données
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Base de données '$dbname' créée avec succès<br>";
    
    // Sélection de la base de données
    $conn->exec("USE $dbname");

    //Création de la table users
    $sql_users = "
    CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(100),
    role ENUM('admin', 'user'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql_users);
    echo "✓ Table 'users' créée avec succès<br>";

    // Création de la table des catégories
    $sql_categories = "
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        color VARCHAR(7) DEFAULT '#3498db',
        icon VARCHAR(50) DEFAULT 'fas fa-folder',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql_categories);
    echo "✓ Table 'categories' créée avec succès<br>";

    
    // Création de la table des dépenses
$sql_expenses = "
    CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,  -- NOUVEAU !
        category_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        frequency INT NOT NULL DEFAULT 1,
        paid BOOLEAN DEFAULT FALSE,
        payment_date DATE NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_category (category_id),
        INDEX idx_paid (paid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql_expenses);
        echo "✓ Table 'expenses' créée avec succès<br>";

    // Créer la table si elle n'existe pas
    $sql_wedding_dates = "
    CREATE TABLE IF NOT EXISTS wedding_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        wedding_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql_wedding_dates);
    echo "✓ Table 'wedding_dates' créée avec succès<br>";
    
    // Insertion des catégories
    $categories = [
        ['name' => 'Connaissance', 'color' => '#3498db','icon' => 'fas fa-handshake', 'order' => 1],
        ['name' => 'Dot', 'color' => '#9b59b6','icon' => 'fas fa-gift', 'order' => 2],
        ['name' => 'Mariage civil', 'color' => '#e74c3c', 'icon' => 'fas fa-landmark', 'order' => 3],
        ['name' => 'Bénédiction nuptiale', 'color' => '#2ecc71','icon' => 'fas fa-church', 'order' => 4],
        ['name' => 'Logistique', 'color' => '#1abc9c', 'icon' => 'fas fa-truck', 'order' => 5],
        ['name' => 'Réception', 'color' => '#f39c12','icon' => 'fas fa-glass-cheers', 'order' => 6],
        ['name' => 'Coût indirect et imprévus','color' => '#95a5a6', 'icon' => 'fas fa-exclamation-triangle', 'order' => 7]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, color, icon, display_order) VALUES (:name, :color, :icon, :order)");
    foreach ($categories as $cat) {
        $stmt->execute([
            'name' => $cat['name'], 
            'color' => $cat['color'],
            'icon' => $cat['icon'],
            'order' => $cat['order']
        ]);
    }
    echo "✓ Catégories insérées avec succès<br>";
    
    // Insertion des données défaut users admin 
    // Note: Dans un environnement réel, il faudrait hacher le mot de passe
    $hashed_password = password_hash('Admin@1312', PASSWORD_DEFAULT);
    
    $stmt_user = $conn->prepare("INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES (:username, :email, :password, :full_name, :role)");
    
    $users = [
        [
            'username' => 'Administrateur', 
            'email' => 'liferopro@gmail.com', 
            'password' => $hashed_password, 
            'full_name' => 'Administrateur Principal', 
            'role' => 'admin'
        ]
    ];
    
    foreach ($users as $user) { 
        $stmt_user->execute($user); 
    }
    echo "✓ Utilisateur admin inséré avec succès<br>";
    
    // Insertion des données initiales
    $expenses_data = [
        // Catégorie 1: Prise de contact
        [1, 1, 'Enveloppe', 2, 2000, 1, true],
        [1, 1, 'Bouteille de jus de risins', 2, 5000, 1, true],
        [1, 1, 'Deplacement', 1, 5000, 1, true],
        
        // Catégorie 2: La dot
        [1, 2, 'Bible', 1, 6000, 1, false],
        [1, 2, 'Valise', 1, 10000, 1, false],
        [1, 2, 'Pagne vlisco démi pièce', 2, 27000, 1, false],
        [1, 2, 'Pagne côte d\'ivoire démi pièce', 5, 6500, 1, false],
        [1, 2, 'Pagne Ghana démi pièce', 4, 6500, 1, false],
        [1, 2, 'Ensemble de chaine', 3, 3000, 1, false],
        [1, 2, 'Chaussures', 3, 3000, 1, false],
        [1, 2, 'Sac à main', 2, 3500, 1, false],
        [1, 2, 'Montre et Bracelet', 2, 3000, 1, false],
        [1, 2, 'Série de bol', 3, 5500, 1, false],
        [1, 2, 'Demi-douzaine Assiettes en verre', 2, 4800, 1, false],
        [1, 2, 'Douzaine Assiettes en plastique', 2, 3000, 1, false],
        [1, 2, 'Série de casseroles', 1, 7000, 1, false],
        [1, 2, 'Marmites de 1kg, 1,5kg, 2kg et 3kg', 1, 11000, 1, false],
        [1, 2, 'Gobelets', 1, 2000, 1, false],
        [1, 2, 'Bols', 12, 1500, 1, false],
        [1, 2, 'Fourchettes', 1, 1500, 1, false],
        [1, 2, 'Cuillères', 1, 1500, 1, false],
        [1, 2, 'Couteaux', 1, 1500, 1, false],
        [1, 2, 'Pavie', 1, 1500, 1, false],
        [1, 2, 'Bassine aluminium grande', 1, 10000, 1, false],
        [1, 2, 'Bassine aluminium moyen', 2, 4000, 1, false],
        [1, 2, 'Bassine aluminium petite', 2, 2500, 1, false],
        [1, 2, 'Palette', 1, 500, 1, false],
        [1, 2, 'Raclette', 1, 1000, 1, false],
        [1, 2, 'Cuillères à sauce', 2, 800, 1, false],
        [1, 2, 'Gaz et accessoire complet', 1, 25000, 1, false],
        [1, 2, 'Seau de soin corporelle', 1, 10000, 1, false],
        [1, 2, 'Enveloppe fille', 1, 100000, 1, false],
        [1, 2, 'Enveloppe Famille', 1, 25000, 1, false],
        [1, 2, 'Enveloppe frères et soeurs', 1, 10000, 1, false],
        [1, 2, 'Sac de sel de cuisine', 1, 12000, 1, false],
        [1, 2, 'Allumettes paquets', 5, 125, 1, false],
        [1, 2, 'Liqueurs Assédekon', 2, 10000, 1, false],
        [1, 2, 'Jus de raisins', 10, 2500, 1, false],
        [1, 2, 'Sucrerie sobebra', 2, 5500, 1, false],
        [1, 2, 'Collation Echoc spirtuel', 1, 45000, 1, false],
        
        // Catégorie 3: Mairie
        [1, 3, 'Frais de dossier et réservation de la chambre de célébration à la mairie', 1, 50000, 1, false],
        [1, 3, 'Petite réception', 1, 50000, 1, false],
        
        // Catégorie 4: Célébration à l'église
        [1, 4, 'Robe', 1, 20000, 1, false],
        [1, 4, 'Coustum', 1, 25000, 1, false],
        [1, 4, 'Chaussures', 2, 25000, 1, false],
        [1, 4, 'Bague de l\'alliance', 1, 15000, 1, false],
        [1, 4, 'Tenues complet', 3, 15000, 1, false],
        [1, 4, 'Tenues complet fille', 4, 15000, 1, false],
        
        // Catégorie 5: Logistique
        [1, 5, 'Location de salle', 1, 150000, 1, false],
        [1, 5, 'Location de véhicule', 2, 35000, 1, false],
        [1, 5, 'Caburant', 20, 680, 1, false],
        [1, 5, 'Prise de vue', 1, 30000, 1, false],
        [1, 5, 'Sonorisation', 1, 20000, 1, false],
        [1, 5, 'Conception flyers', 1, 2000, 1, false],
        
        // Catégorie 6: Réception
        [1, 6, 'Boissons', 200, 600, 1, false],
        [1, 6, 'Poulets', 30, 2500, 1, false],
        [1, 6, 'Porcs', 1, 30000, 1, false],
        [1, 6, 'Poissons', 2, 35000, 1, false],
        [1, 6, 'Sac de riz', 1, 32000, 1, false],
        [1, 6, 'Farine de cossette d\'igname', 20, 500, 1, false],
        [1, 6, 'Maïs pour akassa', 20, 200, 1, false],
        [1, 6, 'Bois de chauffe/Charbon', 20, 200, 1, false],
        [1, 6, 'Ensemble des ingrédients pour la cuisine', 1, 30000, 1, false],
        

        // Catégorie 7: Coût indirect
        [1, 7, 'Ensemble des non prévus', 1, 73996, 1, false]
        
    ];
    
    $stmt_expense = $conn->prepare("
        INSERT INTO expenses (user_id, category_id, name, quantity, unit_price, frequency, paid) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($expenses_data as $expense) {
        $stmt_expense->execute($expense);
    }
    
    echo "✓ Données initiales insérées avec succès<br>";
    echo "<br><strong>Installation terminée !</strong><br>";
    echo "<a href='index.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#8b4f8d; color:white; text-decoration:none; border-radius:5px;'>Accéder à l'application</a>";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Budget Mariage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #8b4f8d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Installation de la Base de Données</h1>
        <hr>
        <div style="margin-top: 20px;">
