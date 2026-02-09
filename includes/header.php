<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../auth/AuthManager.php';

if (AuthManager::isLoggedIn() && !AuthManager::checkSessionTimeout()) {
    header('Location: auth/login.php?expired=1');
    exit;
}
$isLoggedIn = AuthManager::isLoggedIn();
$currentUser = $isLoggedIn ? AuthManager::getCurrentUser() : null;

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
    <meta property="og:title" content="Budget Mariage">
    <meta property="og:description" content="Gérez facilement le budget de votre mariage : dépenses, paiements et organisation financière en un seul outil.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://tonsite.com">
    <meta property="og:image" content="https://tonsite.com/assets/images/toanmda-couple.jpg">
    <meta property="og:locale" content="fr_FR">
    <meta name="description" content="Budget Mariage est une plateforme simple et efficace pour planifier votre mariage, suivre vos dépenses, gérer les paiements et organiser votre budget en toute sérénité.">
    <link rel="shortcut icon" href="assets/images/wedding.jpg" type="image/jpg">
    <title>Budget Mariage - Réussir l'organisation de son mariage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        /* Styles spécifiques pour le header responsive */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
        }

        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .main-nav {
                position: fixed;
                top: 0;
                right: -100%;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                flex-direction: column;
                padding: 20px;
                transition: right 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
                box-shadow: -5px 0 20px rgba(0,0,0,0.2);
            }

            .main-nav.active {
                right: 0;
            }

            .main-nav .nav-link {
                width: 100%;
                justify-content: flex-start;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 10px;
            }

            .user-dropdown {
                width: 100%;
                margin-top: 20px;
            }

            .user-menu-btn {
                width: 100%;
                justify-content: space-between;
            }

            .dropdown-menu {
                position: static;
                display: block !important;
                width: 100%;
                margin-top: 10px;
                box-shadow: none;
                background: rgba(255,255,255,0.1);
            }

            .dropdown-item {
                color: white;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }

            .dropdown-item:hover {
                background: rgba(255,255,255,0.2);
                color: white;
            }

            .header-content {
                padding: 0 15px;
            }

            .logo h1 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .logo h1 {
                font-size: 1rem;
            }

            .main-nav {
                width: 85%;
            }
        }
    .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    }

    .logo-icon {
        width: 72px;
        height: 72px;
        object-fit: cover;
        border-radius: 50%;
    }

    </style>
</head>
<body>
    <header class="fixed-header">
        <div class="header-content">
            <div class="logo">
                <img src="assets/images/wedding.jpg" alt="Budget Mariage" class="logo-icon">
                <h1>Budget Mariage</h1>
            </div>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
            
            <nav class="main-nav" id="mainNav">
                <?php if ($isLoggedIn): ?>
                    <a href="./index.php" class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> <span>Accueil</span>
                    </a>
                    <a href="#" onclick="switchTab('dashboard'); return false;" class="nav-link">
                        <i class="fas fa-chart-bar"></i> <span>Tableau de bord</span>
                    </a>
                    <a href="#" onclick="switchTab('details'); return false;" class="nav-link">
                        <i class="fas fa-list-alt"></i> <span>Dépenses</span>
                    </a>
                    <a href="#" onclick="switchTab('payments'); return false;" class="nav-link">
                        <i class="fas fa-money-check-alt"></i> <span>Paiements</span>
                    </a>
                    <a href="./guide.php" class="nav-link <?php echo $currentPage == 'guide.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> <span>Guide</span>
                    </a>
                    
                    <!-- Dans le menu utilisateur -->
                    <div class="user-dropdown">
                        <button class="user-menu-btn" onclick="toggleUserMenu()">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <i class="fas fa-chevron-down" id="user-menu-chevron"></i>
                        </button>
                        <div class="dropdown-menu" id="dropdown-menu">
                            <a href="./admin/profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Mon Profil
                            </a>
                            <a href="./admin/settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i> Paramètres
                            </a>
                            <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                            <a href="./admin/admin.php" class="dropdown-item">
                                <i class="fas fa-shield-alt"></i> Administration
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="#" onclick="logout(); return false;" class="dropdown-item logout-item">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </div>
                    </div>
                    <script>
                    // Gestion du menu utilisateur au clic
                    function toggleUserMenu() {
                        const menu = document.getElementById('dropdown-menu');
                        const chevron = document.getElementById('user-menu-chevron');
                        
                        // Fermer tous les autres menus ouverts
                        document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
                            if (otherMenu !== menu) {
                                otherMenu.classList.remove('show');
                                const otherChevron = otherMenu.closest('.user-dropdown')?.querySelector('.fa-chevron-down');
                                if (otherChevron) {
                                    otherChevron.classList.remove('rotated');
                                }
                            }
                        });
                        
                        // Basculer l'état du menu actuel
                        menu.classList.toggle('show');
                        chevron.classList.toggle('rotated');
                        
                        // Fermer le menu en cliquant à l'extérieur
                        if (menu.classList.contains('show')) {
                            document.addEventListener('click', closeMenuOnClickOutside);
                        } else {
                            document.removeEventListener('click', closeMenuOnClickOutside);
                        }
                    }

                    function closeMenuOnClickOutside(event) {
                        const menu = document.getElementById('dropdown-menu');
                        const button = document.querySelector('.user-menu-btn');
                        
                        if (!menu.contains(event.target) && !button.contains(event.target)) {
                            menu.classList.remove('show');
                            document.getElementById('user-menu-chevron').classList.remove('rotated');
                            document.removeEventListener('click', closeMenuOnClickOutside);
                        }
                    }

                    // Animation de la flèche
                    document.querySelectorAll('.user-menu-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                        });
                    });

                    // Gestion de la déconnexion
                    function logout() {
                        if (!confirm('Voulez-vous vraiment vous déconnecter ?')) {
                            return;
                        }
                        
                        fetch('api/auth_api.php?action=logout')
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                window.location.href = 'auth/login.php';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la déconnexion:', error);
                        });
                    }
                    </script>
                <?php else: ?>
                    <a href="./index.php" class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> <span>Accueil</span>
                    </a>
                    <a href="./guide.php" class="nav-link <?php echo $currentPage == 'guide.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> <span>Guide</span>
                    </a>
                    <a href="./auth/login.php" class="nav-link <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-in-alt"></i> <span>Connexion</span>
                    </a>
                    <a href="./auth/register.php" class="nav-link <?php echo $currentPage == 'register.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> <span>Inscription</span>
                    </a>
                <?php endif; ?>
                
                <button class="mobile-close-btn" onclick="closeMobileMenu()" style="display: none; margin-top: 20px; padding: 10px; background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 8px; width: 100%;">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </nav>
        </div>
</header>