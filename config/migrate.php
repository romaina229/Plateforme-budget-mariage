<?php
/**
 * Script de migration pour le système multi-utilisateurs
 * Exécutez ce fichier UNE SEULE FOIS si vous avez déjà des données
 */

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    echo "<h2>Migration vers le système multi-utilisateurs</h2>";
    echo "<p>Ce script va :</p>";
    echo "<ul>";
    echo "<li>1. Créer la table 'users' si elle n'existe pas</li>";
    echo "<li>2. Ajouter la colonne 'user_id' à la table 'expenses'</li>";
    echo "<li>3. Créer un compte admin par défaut</li>";
    echo "<li>4. Assigner toutes les dépenses existantes à l'admin</li>";
    echo "</ul>";
    echo "<hr>";
    
    // 1. Créer la table users
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX idx_username (username),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql_users);
    echo "✓ Table 'users' créée/vérifiée<br>";
    
    // 2. Vérifier si la colonne user_id existe déjà
    $check = $conn->query("SHOW COLUMNS FROM expenses LIKE 'user_id'");
    if ($check->rowCount() == 0) {
        // Ajouter la colonne user_id
        $conn->exec("ALTER TABLE expenses ADD COLUMN user_id INT NULL AFTER id");
        echo "✓ Colonne 'user_id' ajoutée à la table 'expenses'<br>";
    } else {
        echo "⚠ Colonne 'user_id' existe déjà<br>";
    }
    
    // 3. Créer un compte admin par défaut
    $adminUsername = 'admin';
    $adminEmail = 'admin@pjpm.local';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute([$adminUsername, $adminEmail, $adminPassword, 'Administrateur PJPM']);
        echo "✓ Compte admin créé (username: admin, password: admin123)<br>";
        
        // Récupérer l'ID de l'admin
        $adminId = $conn->lastInsertId();
    } catch(PDOException $e) {
        // L'admin existe déjà
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$adminUsername]);
        $admin = $stmt->fetch();
        $adminId = $admin['id'];
        echo "⚠ Compte admin existe déjà (ID: $adminId)<br>";
    }
    
    // 4. Assigner toutes les dépenses existantes à l'admin
    $count = $conn->exec("UPDATE expenses SET user_id = $adminId WHERE user_id IS NULL");
    echo "✓ $count dépense(s) assignée(s) à l'admin<br>";
    
    // 5. Rendre user_id obligatoire maintenant
    $conn->exec("ALTER TABLE expenses MODIFY user_id INT NOT NULL");
    echo "✓ Colonne 'user_id' rendue obligatoire<br>";
    
    // 6. Ajouter la clé étrangère si elle n'existe pas
    try {
        $conn->exec("ALTER TABLE expenses ADD CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        echo "✓ Clé étrangère 'fk_expenses_user' ajoutée<br>";
    } catch(PDOException $e) {
        echo "⚠ Clé étrangère existe déjà ou erreur<br>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ Migration terminée avec succès !</h3>";
    echo "<p><strong>Informations importantes :</strong></p>";
    echo "<ul>";
    echo "<li><strong>Compte Admin :</strong> username = admin, password = admin123</li>";
    echo "<li><strong>Action requise :</strong> Changez le mot de passe admin après la première connexion</li>";
    echo "<li>Toutes vos dépenses existantes sont maintenant liées au compte admin</li>";
    echo "<li>Vous pouvez créer de nouveaux comptes via la page d'inscription</li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#8b4f8d; color:white; text-decoration:none; border-radius:5px;'>Accéder à l'application</a></p>";
    echo "<p><strong style='color: red;'>⚠ NE PAS réexécuter ce script !</strong></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Multi-Utilisateurs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #8b4f8d;
        }
        ul {
            line-height: 1.8;
        }
    </style>
</head>
<body>
</body>
</html>
