<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../ExpenseManager.php';
require_once __DIR__ . '/../auth/AuthManager.php';

$manager = new ExpenseManager();
$action = $_GET['action'] ?? '';

// Récupérer l'utilisateur connecté
$currentUser = AuthManager::getCurrentUser();
$userId = $currentUser['id'] ?? null;

try {
    switch($action) {
        case 'get_all':
            echo json_encode([
                'success' => true,
                'data' => $manager->getAllExpenses($userId)
            ]);
            break;
            
        case 'get_categories':
            echo json_encode([
                'success' => true,
                'data' => $manager->getAllCategories()
            ]);
            break;
            
        case 'get_stats':
            $stats = $manager->getStats($userId);
            echo json_encode([
                'success' => true,
                'data' => [
                    'grand_total' => $manager->getGrandTotal($userId),
                    'paid_total' => $manager->getPaidTotal($userId),
                    'unpaid_total' => $manager->getUnpaidTotal($userId),
                    'payment_percentage' => $manager->getPaymentPercentage($userId),
                    'total_items' => $stats['total_items'],
                    'paid_items' => $stats['paid_items'],
                    'unpaid_items' => $stats['unpaid_items']
                ]
            ]);
            break;
            
        case 'add':
            if (!AuthManager::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non authentifié']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Gérer la nouvelle catégorie
            if (isset($data['new_category']) && !empty($data['new_category'])) {
                $maxOrder = count($manager->getAllCategories()) + 1;
                $manager->addCategory($data['new_category'], $maxOrder);
                $data['category_id'] = $manager->getLastCategoryId();
                unset($data['new_category']);
            }
            
            $result = $manager->addExpense($userId, $data);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Dépense ajoutée avec succès' : 'Erreur lors de l\'ajout'
            ]);
            break;
            
        case 'update':
            if (!AuthManager::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non authentifié']);
                break;
            }
            
            $id = $_GET['id'] ?? 0;
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $manager->updateExpense($id, $data);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Dépense mise à jour avec succès' : 'Erreur lors de la mise à jour'
            ]);
            break;
            
        case 'delete':
            if (!AuthManager::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non authentifié']);
                break;
            }
            
            $id = $_GET['id'] ?? 0;
            $result = $manager->deleteExpense($id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Dépense supprimée avec succès' : 'Erreur lors de la suppression'
            ]);
            break;
            
        case 'toggle_paid':
            if (!AuthManager::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non authentifié']);
                break;
            }
            
            $id = $_GET['id'] ?? 0;
            $result = $manager->togglePaid($id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Statut mis à jour avec succès' : 'Erreur lors de la mise à jour'
            ]);
            break;
            
        case 'get_by_id':
            $id = $_GET['id'] ?? 0;
            $expense = $manager->getExpenseById($id);
            echo json_encode([
                'success' => $expense !== false,
                'data' => $expense
            ]);
            break;
// api.php - Ajouter ces endpoints
// Dans la section des actions
case 'save_wedding_date':
    if (!AuthManager::isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $date = $data['date'] ?? '';
    
    // Validation de la date
    if (empty($date) || !strtotime($date)) {
        echo json_encode(['success' => false, 'message' => 'Date invalide']);
        exit;
    }
    
    // Sauvegarder dans la base de données
    try {
        $userId = $_SESSION['user_id'];
        
        // Créer la table si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS wedding_dates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            wedding_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        
        // Insérer ou mettre à jour
        $stmt = $conn->prepare("
            INSERT INTO wedding_dates (user_id, wedding_date) 
            VALUES (:user_id, :wedding_date)
            ON DUPLICATE KEY UPDATE wedding_date = :wedding_date2
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':wedding_date' => $date,
            ':wedding_date2' => $date
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Date sauvegardée']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
    }
    break;
// Endpoint pour récupérer la date de mariage fin 
case 'get_wedding_date':
    if (!AuthManager::isLoggedIn()) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT wedding_date FROM wedding_dates WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo json_encode(['success' => true, 'date' => $result['wedding_date']]);
        } else {
            echo json_encode(['success' => false]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false]);
    }
    break;
            
        case 'category_stats':
            $categories = $manager->getAllCategories();
            $stats = [];
            foreach ($categories as $cat) {
                $total = $manager->getCategoryTotal($cat['id'], $userId);
                $paid = $manager->getCategoryPaidTotal($cat['id'], $userId);
                $stats[] = [
                    'id' => $cat['id'],
                    'name' => $cat['name'],
                    'total' => $total,
                    'paid' => $paid,
                    'remaining' => $total - $paid,
                    'percentage' => $total > 0 ? ($paid / $total) * 100 : 0
                ];
            }
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
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
