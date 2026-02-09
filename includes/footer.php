<?php
// Calculer les statistiques globales pour le footer
$totalStats = [
    'users' => 0,
    'expenses' => 0,
    'total_budget' => 0
];

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once __DIR__ . '/../auth/AuthManager.php';
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../ExpenseManager.php';
    
    if (AuthManager::isLoggedIn()) {
        $auth = new AuthManager();
        $manager = new ExpenseManager();
        
        // Compter les utilisateurs
        $users = $auth->getAllUsers();
        $totalStats['users'] = count($users);
        
        // Calculer les stats globales
        foreach ($users as $user) {
            $stats = $manager->getStats($user['id']);
            $totalStats['expenses'] += $stats['total_items'];
            $totalStats['total_budget'] += $manager->getGrandTotal($user['id']);
        }
    }
} catch (Exception $e) {
    // Silencieux en cas d'erreur
    error_log("Erreur footer: " . $e->getMessage());
}

// Versets bibliques pour le mariage
$bibleVerses = [
    [
        'verse' => "Ce que Dieu a uni, que l'homme ne le sépare point.",
        'reference' => "Matthieu 19:6"
    ],
    [
        'verse' => "Dieu créa l'homme à son image, il le créa à l'image de Dieu, il créa l'homme et la femme. Dieu les bénit, et Dieu leur dit: «Soyez féconds, multipliez, remplissez la terre.»",
        'reference' => "Genèse 1:27-28"
    ],
    [
        'verse' => "L'Éternel Dieu dit: Il n'est pas bon que l'homme soit seul; je lui ferai une aide semblable à lui.",
        'reference' => "Genèse 2:18"
    ],
    [
        'verse' => "C'est pourquoi l'homme quittera son père et sa mère, et s'attachera à sa femme, et ils deviendront une seule chair.",
        'reference' => "Genèse 2:24"
    ],
    [
        'verse' => "Celui qui trouve une femme trouve le bonheur; c'est une grâce qu'il obtient de l'Éternel.",
        'reference' => "Proverbes 18:22"
    ],
    [
        'verse' => "On peut hériter de ses pères une maison et des richesses, mais une femme intelligente est un don du Seigneur.",
        'reference' => "Proverbes 19:14"
    ],
    [
        'verse' => "L'amour est patient, il est plein de bonté; l'amour n'est point envieux.",
        'reference' => "1 Corinthiens 13:4"
    ],
    [
        'verse' => "Mieux vaut habiter à l'angle d'un toit, Que de partager la demeure d'une femme querelleuse.",
        'reference' => "Proverbes 21:9"
    ],
    [
        'verse' => "Que chacun de vous, dans ses relations avec sa femme, sache garder la mesure qui convient par égard pour le Seigneur.",
        'reference' => "Colossiens 3:19"
    ],
    [
        'verse' => "Que le mariage soit honoré de tous, et le lit conjugal exempt de souillure.",
        'reference' => "Hébreux 13:4"
    ]
];

// Sélectionner un verset aléatoire
$randomVerse = $bibleVerses[array_rand($bibleVerses)];
?>
<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <!-- Colonne 1 : Logo et verset -->
        <div class="footer-column">
            <div class="footer-section">
                <h3>💍 Budget Mariage</h3>
                <p>Projet Jésus Pourvoir Ménage - Gestion complète de votre budget de mariage</p>
                
                <!-- Verset biblique -->
                <div class="bible-verse">
                    <div class="verse-icon">
                        <i class="fas fa-bible"></i>
                    </div>
                    <div class="verse-content">
                        <p class="verse-text">"<?php echo $randomVerse['verse']; ?>"</p>
                        <p class="verse-reference"><?php echo $randomVerse['reference']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne 2 : Liens et contact -->
        <div class="footer-column">
            <div class="footer-section">
                <h4>Liens Rapides</h4>
                <ul>
                    <li><a href="./index.php"><i class="fas fa-chart-bar"></i> Tableau de Bord</a></li>
                    <li><a href="./guide.php"><i class="fas fa-book"></i> Guide du Mariage</a></li>
                    <!--<li><a href="./auth/login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>-->
                   <!-- <li><a href="./auth/register.php"><i class="fas fa-user-plus"></i> Inscription</a></li>-->
                   <li><a href="wedding_date.php"><i class="fas fa-calendar-alt"></i> fixé date du Mariage</a></li>
                    
                </ul>
            </div>

            <div class="footer-section">
                <h4>Contact & Support</h4>
                <div class="contact-info">
                    <p><i class="fas fa-envelope"></i> Email: life@gmail.com</p>
                    <p><i class="fas fa-phone"></i> Téléphone: +229 01 94 59 25 67</p>
                    <p><i class="fas fa-map-marker-alt"></i> Abomey-Calavi, Bénin</p>
                </div>
                 <div class="social-links">
                <a href="https://facebook.com/Romain.AKPO" class="social-link facebook" target="_blank" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://instagram.com/rabbi_son229" class="social-link instagram" target="_blank" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://wa.me/22994592567" class="social-link whatsapp" target="_blank" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="https://twitter.com" class="social-link twitter" target="_blank" title="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="http://www.youtube.com/@lifero5180" class="social-link youtube" target="_blank" title="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
            </div>
            </div>
        </div>

        <!-- Colonne 3 : Statistiques -->
        <div class="footer-column">
            <div class="footer-section">
                <h4>Statistiques</h4>
                <div class="footer-stats-large">
                    <div class="footer-stat-large">
                        <div class="stat-value"><?php echo $totalStats['users']; ?></div>
                        <div class="stat-label">Utilisateurs</div>
                    </div>
                    <div class="footer-stat-large">
                        <div class="stat-value"><?php echo $totalStats['expenses']; ?></div>
                        <div class="stat-label">Dépenses</div>
                    </div>
                </div>
            </div>

            <div class="footer-section">
                <h4>Pages Légales</h4>
                <div class="legal-links">
                    <a href="./termes/privacy.php"><i class="fas fa-shield-alt"></i> Confidentialité</a>
                    <a href="./termes/terms.php"><i class="fas fa-file-contract"></i> Conditions</a>
                    <a href="./termes/legal.php"><i class="fas fa-balance-scale"></i> Mentions</a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Budget Mariage - Tous droits réservés</p>
        <p class="footer-motto"><i class="fas fa-heart"></i> Construire des foyers solides sur le roc de Jésus-Christ</p>
    </div>
</footer>
    <script>
    // Gestion du menu mobile
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mainNav = document.getElementById('mainNav');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

    function toggleMobileMenu() {
        mainNav.classList.toggle('active');
        mobileMenuOverlay.style.display = mainNav.classList.contains('active') ? 'block' : 'none';
        document.body.style.overflow = mainNav.classList.contains('active') ? 'hidden' : '';
    }

    function closeMobileMenu() {
        mainNav.classList.remove('active');
        mobileMenuOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    mobileMenuOverlay.addEventListener('click', closeMobileMenu);

    // Fermer le menu lors du clic sur un lien
    document.querySelectorAll('.main-nav a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                closeMobileMenu();
            }
        });
    });

    // Ajuster la hauteur du header fixe
    function updateHeaderHeight() {
        const header = document.querySelector('.fixed-header');
        const headerHeight = header.offsetHeight;
        document.querySelectorAll('.app-container, .profile-container, .settings-container, .admin-container, .guide-container').forEach(container => {
            container.style.marginTop = headerHeight + 'px';
        });
    }

    // Initialiser et mettre à jour lors du redimensionnement
    window.addEventListener('load', updateHeaderHeight);
    window.addEventListener('resize', updateHeaderHeight);

    // Gestion de la déconnexion
    function logout() {
        if (!confirm('Voulez-vous vraiment vous déconnecter ?')) {
            return;
        }
        
        fetch('auth_api.php?action=logout')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.location.href = 'login.php';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la déconnexion:', error);
        });
    }
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>