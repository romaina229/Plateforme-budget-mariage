<?php
// wedding_date.php - UNIQUEMENT le badge, sans <html>, <head>, <body>
// À inclure dans index.php avec <?php include 'wedding_date.php';

// Vérifier si la session est démarrée sans la redémarrer
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
?>

<!-- Badge uniquement -->
<div id="wedding-date-banner">
    <div class="banner-content">
        <div class="countdown-icon">
            <i class="fas fa-heart"></i>
        </div>
        <div class="countdown-text">
            <span class="label">🎊 Date du Mariage :</span>
            <span class="date" id="wedding-date-display">--/--/----</span>
            <span class="countdown" id="wedding-countdown">Définir la date</span>
        </div>
        <button class="edit-date-btn" onclick="openWeddingDateModal()" title="Modifier la date">
            <i class="fas fa-edit"></i>
        </button>
    </div>
</div>

<!-- Modal (sera affiché par JavaScript) -->
<div id="wedding-date-modal" style="display: none;"></div>

<script>
// Fonctions JavaScript pour le badge
// Fonction pour ouvrir le modal avec le style spécifique
function openDateModal() {
    // Vérifier si le modal existe déjà
    let modal = document.getElementById('wedding-date-modal');
    
    if (!modal) {
        // Créer le modal
        modal = document.createElement('div');
        modal.id = 'wedding-date-modal';
        modal.style.cssText = 'display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;';
        
        // Contenu du modal avec VOTRE style exact
        modal.innerHTML = `
            <div style="background: white; border-radius: 10px; padding: 20px; max-width: 500px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; color: #8b4f8d;">
                        <i class="fas fa-calendar-alt"></i> Définir la date du mariage
                    </h2>
                    <button onclick="closeDateModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #8b4f8d;">&times;</button>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5d2f5f;">
                        <i class="fas fa-heart" style="color: #8b4f8d; margin-right: 5px;"></i> Sélectionnez la date
                    </label>
                    <input type="date" id="wedding-date-input" style="width: 100%; padding: 10px; border: 2px solid #e8e3dd; border-radius: 5px; font-size: 1rem; color: #5d2f5f;">
                    <small style="color: #666; display: block; margin-top: 5px;">Choisissez le grand jour !</small>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background: #faf8f5; border-radius: 8px;">
                    <h4 style="margin-top: 0; color: #5d2f5f;">Aperçu :</h4>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 5px; border: 1px solid #e8e3dd;">
                        <div style="background: #8b4f8d; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="fas fa-church"></i>
                        </div>
                        <div>
                            <p style="margin: 0; font-weight: bold; color: #5d2f5f;" id="date-preview-text">Sélectionnez une date</p>
                            <p style="margin: 5px 0 0 0; color: #8b4f8d; font-weight: bold;" id="countdown-preview-text">--</p>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button onclick="closeDateModal()" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">Annuler</button>
                    <button onclick="saveWeddingDate()" style="padding: 10px 20px; background: #8b4f8d; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-save" style="margin-right: 5px;"></i> Enregistrer la date
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Ajouter l'événement pour la prévisualisation
        document.getElementById('wedding-date-input').addEventListener('change', updateDatePreview);
    }
    
    // Afficher le modal
    modal.style.display = 'flex';
    
    // Pré-remplir avec la date existante si disponible
    if (window.weddingDate) {
        document.getElementById('wedding-date-input').value = window.weddingDate.toISOString().split('T')[0];
    } else {
        // Date par défaut (6 mois plus tard)
        const defaultDate = new Date();
        defaultDate.setMonth(defaultDate.getMonth() + 6);
        document.getElementById('wedding-date-input').value = defaultDate.toISOString().split('T')[0];
    }
    
    // Mettre à jour l'aperçu
    updateDatePreview();
}

// Fonction pour fermer le modal
function closeDateModal() {
    const modal = document.getElementById('wedding-date-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Fonction pour mettre à jour l'aperçu
function updateDatePreview() {
    const dateInput = document.getElementById('wedding-date-input');
    const date = new Date(dateInput.value);
    
    if (date && !isNaN(date.getTime())) {
        // Formater la date
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = date.toLocaleDateString('fr-FR', options);
        
        // Calculer le compte à rebours
        const now = new Date();
        const timeDiff = date.getTime() - now.getTime();
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        
        // Mettre à jour l'aperçu
        document.getElementById('date-preview-text').textContent = formattedDate;
        
        let countdownText = '';
        if (days > 0) {
            countdownText = `${days} jour${days > 1 ? 's' : ''} restant${days > 1 ? 's' : ''}`;
        } else if (days === 0) {
            countdownText = '🎉 C\'est aujourd\'hui !';
        } else {
            countdownText = '⚠️ Date passée';
        }
        
        document.getElementById('countdown-preview-text').textContent = countdownText;
        
        // Changer la couleur
        const preview = document.getElementById('countdown-preview-text');
        if (days < 7) {
            preview.style.color = '#ff6b6b';
        } else if (days < 30) {
            preview.style.color = '#ffa726';
        } else {
            preview.style.color = '#4caf50';
        }
    }
}

// Fonction pour sauvegarder la date
async function saveWeddingDate() {
    const dateInput = document.getElementById('wedding-date-input').value;
    
    if (!dateInput) {
        alert('Veuillez sélectionner une date');
        return;
    }
    
    const selectedDate = new Date(dateInput);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Validation
    if (selectedDate <= today) {
        alert('La date doit être dans le futur');
        return;
    }
    
    try {
        const response = await fetch('api/api.php?action=save_wedding_date', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ date: dateInput })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Mettre à jour la date globale
            window.weddingDate = selectedDate;
            
            // Mettre à jour l'affichage du badge
            updateWeddingBadgeDisplay();
            
            // Fermer le modal
            closeDateModal();
            
            // Afficher un message de succès
            showSuccessMessage('Date de mariage enregistrée avec succès !');
        } else {
            alert(result.message || 'Erreur lors de l\'enregistrement');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
    }
}

// Fonction pour mettre à jour l'affichage du badge
function updateWeddingBadgeDisplay() {
    if (!window.weddingDate) return;
    
    const badge = document.getElementById('wedding-date-badge');
    if (!badge) return;
    
    // Formater la date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = window.weddingDate.toLocaleDateString('fr-FR', options);
    
    // Calculer le compte à rebours
    const now = new Date();
    const timeDiff = window.weddingDate.getTime() - now.getTime();
    const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
    
    let countdownText = '';
    if (days > 30) {
        const months = Math.floor(days / 30);
        const remainingDays = days % 30;
        countdownText = `${months} mois ${remainingDays} jours`;
    } else if (days > 0) {
        countdownText = `${days} jours`;
    } else if (days === 0) {
        countdownText = '🎉 Aujourd\'hui !';
    }
    
    // Mettre à jour le badge
    badge.innerHTML = `
        <div style="background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%); color: white; padding: 12px 20px; border-radius: 10px; margin: 15px auto; max-width: 900px; border: 2px solid #d4af37;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 50%;">
                    <i class="fas fa-heart"></i>
                </div>
                <div style="flex: 1; display: flex; flex-wrap: wrap; align-items: center; gap: 10px 20px;">
                    <span style="font-weight: 600; color: #ffd700;">🎊 Date du Mariage :</span>
                    <span style="background: rgba(255, 255, 255, 0.15); padding: 5px 12px; border-radius: 20px; font-weight: bold; border: 1px dashed rgba(255, 255, 255, 0.3);">
                        ${formattedDate}
                    </span>
                    <span style="font-weight: bold; color: #ffd700;">
                        ${countdownText}
                    </span>
                </div>
                <button onclick="openDateModal()" style="background: rgba(255, 255, 255, 0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer;">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        </div>
    `;
}

// Fonction pour afficher un message de succès
function showSuccessMessage(message) {
    // Créer un toast temporaire
    const toast = document.createElement('div');
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 15px 20px; border-radius: 5px; z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
    toast.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 8px;"></i> ${message}`;
    
    document.body.appendChild(toast);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s';
        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 500);
    }, 3000);
}

// Fonction pour charger et initialiser le badge
async function loadWeddingDate() {
    try {
        const response = await fetch('api/api.php?action=get_wedding_date');
        const result = await response.json();
        
        if (result.success && result.date) {
            window.weddingDate = new Date(result.date);
            createWeddingBadge();
        } else {
            createEmptyWeddingBadge();
        }
    } catch (error) {
        console.error('Erreur:', error);
        createEmptyWeddingBadge();
    }
}

// Fonction pour créer le badge quand il y a une date
function createWeddingBadge() {
    const container = document.getElementById('wedding-date-container');
    if (!container) return;
    
    container.innerHTML = `
        <div id="wedding-date-badge">
            <!-- Le badge sera rempli par updateWeddingBadgeDisplay() -->
        </div>
    `;
    
    updateWeddingBadgeDisplay();
}

// Fonction pour créer le badge vide
function createEmptyWeddingBadge() {
    const container = document.getElementById('wedding-date-container');
    if (!container) return;
    
    container.innerHTML = `
        <div id="wedding-date-badge">
            <div style="background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%); color: white; padding: 12px 20px; border-radius: 10px; margin: 15px auto; max-width: 900px; border: 2px solid #d4af37; text-align: center; cursor: pointer;" onclick="openDateModal()">
                <i class="fas fa-calendar-plus" style="margin-right: 10px;"></i>
                <strong>Cliquez pour définir la date de votre mariage</strong>
            </div>
        </div>
    `;
}

// Initialiser quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si Font Awesome est chargé, sinon le charger
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(faLink);
    }
    
    // Charger la date
    loadWeddingDate();
    
    // Fermer le modal en cliquant à l'extérieur
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('wedding-date-modal');
        if (modal && modal.style.display === 'flex') {
            if (event.target === modal) {
                closeDateModal();
            }
        }
    });
    
    // Fermer avec la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDateModal();
        }
    });
});
</script>

<style>
#wedding-date-banner {
    background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    margin: 15px auto;
    max-width: 900px;
    border: 2px solid #d4af37;
}

.banner-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.countdown-icon {
    background: rgba(255, 255, 255, 0.2);
    padding: 10px;
    border-radius: 50%;
    animation: heartbeat 1.5s infinite;
}

.countdown-text {
    flex: 1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 20px;
    font-family: 'Georgia', serif;
}

.countdown-text .label {
    font-weight: 600;
    color: #ffd700;
}

.countdown-text .date {
    background: rgba(255, 255, 255, 0.15);
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: bold;
    border: 1px dashed rgba(255, 255, 255, 0.3);
}

.countdown-text .countdown {
    font-weight: bold;
    color: #ffd700;
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
}

.edit-date-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(15deg);
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>