<?php
/**
 * wedding_date.php — Gestion de la date du mariage
 * Budget Mariage PJPM v2.1
 */
declare(strict_types=1);

// Définir ROOT_PATH avant tout
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}

require_once ROOT_PATH . 'config.php';
require_once ROOT_PATH . 'AuthManager.php';

// DÉMARRER LA SESSION CORRECTEMENT
AuthManager::startSession();

// VÉRIFICATION DE CONNEXION - SIMPLE ET EFFICACE
if (!AuthManager::isLoggedIn()) {
    // Rediriger vers la page de connexion
    header('Location: ' . APP_URL . '/auth/login.php?redirect=' . urlencode('wedding_date.php'));
    exit;
}

// Récupérer l'utilisateur connecté
$currentUser = AuthManager::getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// Inclure ExpenseManager pour accéder aux fonctions
require_once ROOT_PATH . 'ExpenseManager.php';
$expenseManager = new ExpenseManager();

// Récupérer la date actuelle si elle existe
$weddingDate = $expenseManager->getWeddingDate($userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date du Mariage - Jésus Pourvoit au Ménage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Styles du badge (copiez depuis date.html) */
        #wedding-date-banner {
            background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin: 15px auto;
            max-width: 900px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(139, 79, 141, 0.3);
            animation: slideIn 0.5s ease-out;
            border: 2px solid #d4af37;
        }

        .banner-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            position: relative;
            z-index: 2;
        }

        .countdown-icon {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 50%;
            font-size: 1.2rem;
            animation: heartbeat 1.5s infinite;
        }

        .countdown-text {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px 20px;
            font-family: 'Playfair Display', serif;
        }

        .countdown-text .label {
            font-weight: 600;
            color: #ffd700;
            font-size: 1.1rem;
        }

        .countdown-text .date {
            background: rgba(255, 255, 255, 0.15);
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            border: 1px dashed rgba(255, 255, 255, 0.3);
            font-size: 1.2rem;
        }

        .countdown-text .countdown {
            font-weight: bold;
            color: #ffd700;
            font-size: 1.1rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .edit-date-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-date-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(15deg);
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            #wedding-date-banner {
                margin: 10px;
                padding: 10px 15px;
            }
            
            .countdown-text {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .countdown-text .date {
                font-size: 1rem;
            }
        }

        /* Boutons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(139, 79, 141, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <!-- Badge défilant -->
    <div id="wedding-date-banner">
        <div class="banner-content">
            <div class="countdown-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="countdown-text">
                <span class="label">🎉 Date du Mariage :</span>
                <span class="date" id="wedding-date-display">--/--/----</span>
                <span class="countdown" id="wedding-countdown">Chargement...</span>
            </div>
            <button class="edit-date-btn" onclick="openDateModal()" title="Modifier la date">
                <i class="fas fa-edit"></i>
            </button>
        </div>
    </div>

    <!-- Modal pour définir la date -->
    <div id="date-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 10px; padding: 20px; max-width: 500px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #8b4f8d;">
                    <i class="fas fa-calendar-alt"></i> Définir la date du mariage
                </h2>
                <button onclick="closeDateModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    <i class="fas fa-heart"></i> Sélectionnez la date
                </label>
                <input type="date" id="wedding-date-input" style="width: 100%; padding: 10px; border: 2px solid #e8e3dd; border-radius: 5px; font-size: 1rem;">
                <small style="color: #666; display: block; margin-top: 5px;">Choisissez le grand jour !</small>
            </div>
            
            <div style="margin-bottom: 20px; padding: 15px; background: #faf8f5; border-radius: 8px;">
                <h4 style="margin-top: 0;">Aperçu :</h4>
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 5px;">
                    <div style="background: #8b4f8d; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fas fa-church"></i>
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: bold;" id="date-preview-text">Samedi 15 juin 2024</p>
                        <p style="margin: 5px 0 0 0; color: #8b4f8d; font-weight: bold;" id="countdown-preview-text">365 jours restants</p>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button class="btn btn-secondary" onclick="closeDateModal()">Annuler</button>
                <button class="btn btn-primary" onclick="saveWeddingDate()">
                    <i class="fas fa-save"></i> Enregistrer la date
                </button>
            </div>
        </div>
    </div>
    <div>
        <img src="assets/images/toanmda-couple.jpg" alt="image d'un couple heureux" style="width: 100%; max-width: 900px; display: block; margin: 20px auto; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
    </div>
    <div style="display: flex; justify-content: center; margin: 20px auto; max-width: 800px;">
        <h4 style="text-align: center; justify-content: center; font-size: 1.1rem; color: #555; max-width: 800px; margin: 20px auto;">
            "Le mariage est une alliance sacrée, un engagement d'amour et de fidélité. En planifiant votre mariage avec soin, vous posez les fondations d'une vie commune épanouissante. Que votre union soit bénie et remplie de bonheur !"
        </h4>
    </div>
    <div style="display: flex; justify-content: center; margin-bottom: 30px;">
        <button>
            <a href="index.php" style="text-decoration: none; color: white; text-align: center; display: block; justify-content: center; font-size: 1rem; padding: 10px 20px; background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%); border-radius: 5px; transition: all 0.3s;">
                 <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </button>
    </div>
    
    <script>
    // ============================================================
    // FIX: Définir toutes les constantes nécessaires
    // ============================================================
    
    //  1. DÉFINIR API_BASE - C'EST CE QUI MANQUAIT !
    const API_BASE = '<?= APP_URL ?>/api/';
    
    //  2. Définir APP_URL pour les redirections
    const APP_URL = '<?= APP_URL ?>';
    
    //  3. Variables globales
    let weddingDate = <?= $weddingDate ? json_encode($weddingDate) : 'null' ?>;
    let countdownInterval = null;

    // ============================================================
    // Initialisation
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        
        loadWeddingDate();
        
        // Écouter le changement de date
        document.getElementById('wedding-date-input')?.addEventListener('change', function() {
            updateDatePreview();
        });
    });

    // ============================================================
    // Charger la date du mariage
    // ============================================================
    async function loadWeddingDate() {
        try {
            // UTILISER API_BASE + api.php
            const response = await fetch(`${API_BASE}api.php?action=get_wedding_date`);
            const result = await response.json();            
            if (result.success && result.data && result.data.date) {
                weddingDate = new Date(result.data.date);
                updateBannerDisplay();
                startCountdown();
            } else {
                // Pas de date définie
                document.getElementById('wedding-countdown').textContent = 'Définissez votre date !';
                if (document.getElementById('countdown-preview-text')) {
                    document.getElementById('countdown-preview-text').textContent = 'Aucune date définie';
                }
                showSetDatePrompt();
            }
        } catch (error) {
            console.error('❌ Erreur chargement date:', error);
            document.getElementById('wedding-countdown').textContent = 'Erreur de chargement';
        }
    }

    // ============================================================
    // Mettre à jour l'affichage du badge
    // ============================================================
    function updateBannerDisplay() {
        if (!weddingDate) return;
        
        const dateElement = document.getElementById('wedding-date-display');
        if (!dateElement) return;
        
        // Formater la date
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        const formattedDate = weddingDate.toLocaleDateString('fr-FR', options);
        
        dateElement.textContent = formattedDate;
        updateCountdown();
    }

    // ============================================================
    // Mettre à jour le compte à rebours
    // ============================================================
    function updateCountdown() {
        if (!weddingDate) return;
        
        const now = new Date();
        const timeDiff = weddingDate.getTime() - now.getTime();
        
        if (timeDiff <= 0) {
            document.getElementById('wedding-countdown').innerHTML = 
                '<span style="color:#4caf50">🎉 Jour J ! Félicitations !</span>';
            clearInterval(countdownInterval);
            return;
        }
        
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        
        let countdownText = '';
        
        if (days > 30) {
            const months = Math.floor(days / 30);
            const remainingDays = days % 30;
            countdownText = `${months} mois ${remainingDays} jours`;
        } else if (days > 0) {
            countdownText = `${days}j ${hours}h ${minutes}m`;
        } else {
            countdownText = `${hours}h ${minutes}m`;
        }
        
        document.getElementById('wedding-countdown').textContent = countdownText;
        
        // Changer la couleur selon la proximité
        const countdownEl = document.getElementById('wedding-countdown');
        if (days < 7) {
            countdownEl.style.color = '#ff6b6b';
        } else if (days < 30) {
            countdownEl.style.color = '#ffa726';
        } else {
            countdownEl.style.color = '#4caf50';
        }
    }

    // ============================================================
    // Démarrer le compte à rebours
    // ============================================================
    function startCountdown() {
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(updateCountdown, 60000);
        updateCountdown();
    }

    // ============================================================
    // Ouvrir le modal
    // ============================================================
    function openDateModal() {
        const modal = document.getElementById('date-modal');
        if (modal) {
            modal.style.display = 'flex';
            
            // Pré-remplir avec la date actuelle si existante
            if (weddingDate) {
                try {
                    // VÉRIFIER QUE weddingDate est bien un objet Date
                    const dateObj = weddingDate instanceof Date ? weddingDate : new Date(weddingDate);
                    const dateInput = document.getElementById('wedding-date-input');
                    if (dateInput && !isNaN(dateObj.getTime())) {
                        dateInput.value = dateObj.toISOString().split('T')[0];
                    }
                } catch (e) {
                    console.error('❌ Erreur lors du pré-remplissage de la date:', e);
                }
            }
            
            updateDatePreview();
        }
    }

    // ============================================================
    // Fermer le modal
    // ============================================================
    function closeDateModal() {
        const modal = document.getElementById('date-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // ============================================================
    // Mettre à jour l'aperçu de la date
    // ============================================================
    function updateDatePreview() {
        const dateInput = document.getElementById('wedding-date-input');
        if (!dateInput || !dateInput.value) return;
        
        const date = new Date(dateInput.value + 'T00:00:00');
        if (isNaN(date.getTime())) return;
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = date.toLocaleDateString('fr-FR', options);
        
        const previewText = document.getElementById('date-preview-text');
        if (previewText) {
            previewText.textContent = formattedDate;
        }
        
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        const timeDiff = date.getTime() - now.getTime();
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        
        const countdownPreview = document.getElementById('countdown-preview-text');
        if (countdownPreview) {
            let countdownText = '';
            if (days > 0) {
                countdownText = `${days} jour${days > 1 ? 's' : ''} restant${days > 1 ? 's' : ''}`;
            } else if (days === 0) {
                countdownText = '🎉 C\'est aujourd\'hui !';
            } else {
                countdownText = '⚠️ Date passée';
            }
            countdownPreview.textContent = countdownText;
            
            if (days < 7) countdownPreview.style.color = '#ff6b6b';
            else if (days < 30) countdownPreview.style.color = '#ffa726';
            else countdownPreview.style.color = '#4caf50';
        }
    }

    // ============================================================
    // Sauvegarder la date
    // ============================================================
    async function saveWeddingDate() {
        const dateInput = document.getElementById('wedding-date-input');
        if (!dateInput || !dateInput.value) {
            alert('Veuillez sélectionner une date');
            return;
        }
        
        const selectedDate = new Date(dateInput.value + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert('La date doit être aujourd\'hui ou dans le futur');
            return;
        }
        
        try {
            // UTILISER API_BASE CORRECTEMENT
            const response = await fetch(`${API_BASE}api.php?action=save_wedding_date`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ date: dateInput.value })
            });
            
            const result = await response.json();
            
            if (result.success) {
                weddingDate = selectedDate;
                updateBannerDisplay();
                startCountdown();
                closeDateModal();
                alert('Date de mariage enregistrée avec succès !');
                
                // Recharger la page après 1 seconde
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(result.message || '❌ Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            alert('Erreur de connexion au serveur');
        }
    }

    // ============================================================
    // Inviter à définir la date
    // ============================================================
    function showSetDatePrompt() {
        setTimeout(() => {
            if (!weddingDate && confirm('🎉 Souhaitez-vous définir la date de votre mariage ?\n\nVous pourrez suivre le compte à rebours directement sur le tableau de bord.')) {
                openDateModal();
            }
        }, 2000);
    }

    // ============================================================
    // Exposer les fonctions globalement
    // ============================================================
    window.openDateModal = openDateModal;
    window.closeDateModal = closeDateModal;
    window.saveWeddingDate = saveWeddingDate;
    window.updateDatePreview = updateDatePreview;
</script>
</body>
</html>