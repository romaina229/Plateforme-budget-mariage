<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../auth/AuthManager.php';
require_once __DIR__ . '/../ExpenseManager.php';

// Vérifier l'authentification et les droits admin
if (!AuthManager::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$currentUser = AuthManager::getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

$action = $_GET['action'] ?? '';
$manager = new ExpenseManager();
$auth = new AuthManager();

try {
    switch($action) {
        case 'get_users':
            $users = $auth->getAllUsers();
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'add_user':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Vérifier les données
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                break;
            }
            
            // Vérifier si l'utilisateur existe déjà
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ce nom d\'utilisateur ou email existe déjà']);
                break;
            }
            
            // Hasher le mot de passe
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = $data['role'] ?? 'user';
            
            // Insérer l'utilisateur
            $sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['full_name'] ?? null,
                $role
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Utilisateur créé avec succès']);
            break;
            
        case 'edit_user':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['user_id']) || empty($data['role'])) {
                echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                break;
            }
            
            // Empêcher l'auto-modification du dernier admin
            if ($data['user_id'] == $currentUser['id'] && $data['role'] == 'user') {
                $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND id != ?");
                $stmt->execute([$currentUser['id']]);
                $result = $stmt->fetch();
                if ($result['admin_count'] == 0) {
                    echo json_encode(['success' => false, 'message' => 'Impossible : vous êtes le seul administrateur']);
                    break;
                }
            }
            
            $conn = getDBConnection();
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$data['role'], $data['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
            break;
            
        case 'delete_user':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
                break;
            }
            
            // Empêcher l'auto-suppression
            if ($data['user_id'] == $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
                break;
            }
            
            // Empêcher la suppression du dernier admin
            if ($data['user_id'] != $currentUser['id']) {
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$data['user_id']]);
                $user = $stmt->fetch();
                
                if ($user && $user['role'] == 'admin') {
                    $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND id != ?");
                    $stmt->execute([$data['user_id']]);
                    $result = $stmt->fetch();
                    if ($result['admin_count'] == 0) {
                        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le dernier administrateur']);
                        break;
                    }
                }
            }
            
            // Supprimer l'utilisateur et ses données (CASCADE)
            $conn = getDBConnection();
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$data['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
            break;
            
        case 'export_users':
            $users = $auth->getAllUsers();
            $filename = 'export_utilisateurs_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = 'exports/' . $filename;
            
            // Créer le répertoire exports s'il n'existe pas
            if (!is_dir('exports')) {
                mkdir('exports', 0777, true);
            }
            
            // Créer le fichier CSV
            $fp = fopen($filepath, 'w');
            fputcsv($fp, ['ID', 'Username', 'Email', 'Full Name', 'Role', 'Created At', 'Last Login']);
            
            foreach ($users as $user) {
                fputcsv($fp, [
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $user['full_name'],
                    $user['role'],
                    $user['created_at'],
                    $user['last_login']
                ]);
            }
            
            fclose($fp);
            
            echo json_encode([
                'success' => true,
                'file_url' => $filepath,
                'file_name' => $filename,
                'message' => 'Export terminé'
            ]);
            break;
            
        case 'create_backup':
            $conn = getDBConnection();
            $backupFile = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Récupérer toutes les tables
            $tables = [];
            $stmt = $conn->query("SHOW TABLES");
            while ($row = $stmt->fetch()) {
                $tables[] = $row[0];
            }
            
            $backupContent = "-- Backup de la base " . DB_NAME . "\n";
            $backupContent .= "-- Date : " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                // Structure de la table
                $backupContent .= "--\n-- Structure de la table `$table`\n--\n\n";
                $createTable = $conn->query("SHOW CREATE TABLE `$table`")->fetch();
                $backupContent .= $createTable['Create Table'] . ";\n\n";
                
                // Données de la table
                $backupContent .= "--\n-- Données de la table `$table`\n--\n\n";
                $data = $conn->query("SELECT * FROM `$table`")->fetchAll();
                
                if (count($data) > 0) {
                    $columns = array_keys($data[0]);
                    foreach ($data as $row) {
                        $values = array_map(function($value) use ($conn) {
                            if ($value === null) return 'NULL';
                            return $conn->quote($value);
                        }, $row);
                        
                        $backupContent .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $backupContent .= "\n";
                }
            }
            
            // Sauvegarder le fichier
            file_put_contents('backups/' . $backupFile, $backupContent);
            
            echo json_encode([
                'success' => true,
                'file_url' => 'backups/' . $backupFile,
                'file_name' => $backupFile,
                'message' => 'Sauvegarde créée avec succès'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>