<?php
/**
 * Système d'authentification
 * Gestion des utilisateurs et des sessions
 */

require_once __DIR__ . '/../config/config.php';

class AuthManager {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
        $this->createUsersTable();
    }
    
    // Créer la table users si elle n'existe pas
    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
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
        
        try {
            $this->conn->exec($sql);
        } catch(PDOException $e) {
            // Table existe déjà
        }
    }
    
    // Inscription d'un nouvel utilisateur
    public function register($username, $email, $password, $fullName = null) {
        // Validation
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Le nom d\'utilisateur doit contenir au moins 3 caractères'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email invalide'];
        }
        
        // Vérifier si l'utilisateur existe déjà
        if ($this->userExists($username, $email)) {
            return ['success' => false, 'message' => 'Ce nom d\'utilisateur ou email existe déjà'];
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insérer l'utilisateur
        try {
            $sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username, $email, $hashedPassword, $fullName]);
            
            return ['success' => true, 'message' => 'Inscription réussie ! Vous pouvez maintenant vous connecter.'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
        }
    }
    
    // Connexion
    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Mettre à jour last_login
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->execute([$user['id']]);
            
            // Démarrer la session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            return ['success' => true, 'message' => 'Connexion réussie', 'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]];
        }
        
        return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect'];
    }
    
    // Déconnexion
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Déconnexion réussie'];
    }
    
    // Vérifier si l'utilisateur est connecté
    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Obtenir l'utilisateur connecté
    public static function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ];
        }
        
        return null;
    }
    
    // Vérifier si un utilisateur existe
    private function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username, $email]);
        return $stmt->fetch() !== false;
    }
    
    // Obtenir tous les utilisateurs
    public function getAllUsers() {
        $sql = "SELECT id, username, email, full_name, role, created_at, last_login FROM users ORDER BY created_at DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
    
    // Changer le mot de passe
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Vérifier l'ancien mot de passe
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Ancien mot de passe incorrect'];
        }
        
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'];
        }
        
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSql = "UPDATE users SET password = ? WHERE id = ?";
        $updateStmt = $this->conn->prepare($updateSql);
        $updateStmt->execute([$hashedPassword, $userId]);
        
        return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
    }
}
?>
