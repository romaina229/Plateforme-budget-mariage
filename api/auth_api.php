<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../auth/AuthManager.php';

$auth = new AuthManager();
$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'register':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $auth->register(
                $data['username'],
                $data['email'],
                $data['password'],
                $data['fullname'] ?? null
            );
            echo json_encode($result);
            break;
            
        case 'login':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $auth->login($data['username'], $data['password']);
            echo json_encode($result);
            break;
            
        case 'logout':
            $result = $auth->logout();
            echo json_encode($result);
            break;
            
        case 'check':
            if (AuthManager::isLoggedIn()) {
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user' => AuthManager::getCurrentUser()
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'logged_in' => false
                ]);
            }
            break;
            
        case 'change_password':
            if (!AuthManager::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non authentifié']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $user = AuthManager::getCurrentUser();
            $result = $auth->changePassword(
                $user['id'],
                $data['old_password'],
                $data['new_password']
            );
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action non reconnue'
            ]);
    }
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>