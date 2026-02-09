<?php
session_start();
require_once 'auth/AuthManager.php';

$isLoggedIn = AuthManager::isLoggedIn();
$currentUser = $isLoggedIn ? AuthManager::getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide du Mariage - les étapes indispensable pour la réussite du mariage</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .guide-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .guide-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 40px;
        }

        .guide-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }

        .timeline-item {
            margin-bottom: 50px;
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: auto;
            text-align: left;
        }

        .timeline-item:nth-child(even) .timeline-content {
            margin-right: auto;
            text-align: right;
        }

        .timeline-content {
            width: 45%;
            background: var(--bg-card);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--shadow);
            position: relative;
        }

        .timeline-marker {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px var(--shadow);
            z-index: 10;
        }

        .timeline-item:nth-child(odd) .timeline-marker {
            top: 30px;
        }

        .timeline-item:nth-child(even) .timeline-marker {
            top: 30px;
        }

        .step-number {
            display: inline-block;
            background: var(--secondary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .step-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .step-description {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .step-checklist {
            list-style: none;
            padding: 0;
        }

        .step-checklist li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }

        .step-checklist li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success);
            font-weight: bold;
            font-size: 1.2rem;
        }

        .duration {
            display: inline-block;
            background: var(--bg-main);
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 15px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: var(--bg-card);
            color: var(--primary);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px var(--shadow);
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--shadow);
        }

        @media (max-width: 768px) {
            .timeline::before {
                left: 30px;
            }

            .timeline-content {
                width: calc(100% - 80px);
                margin-left: 80px !important;
                text-align: left !important;
            }

            .timeline-marker {
                left: 30px !important;
            }

            .guide-hero h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
    <?php include './includes/header.php'; ?>

<body>
    <div class="guide-container">

        <div class="guide-hero">
            <h1>💍 Guide Complet du Mariage</h1>
            <p>De la demande en mariage à la cérémonie : Toutes les étapes pour un mariage réussi</p>
        </div>


            <div class="timeline">
            <!-- Nouvelle section avant le comité d'église -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-church"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape importante</span>
                    <h2 class="step-title">Préparatifs avant le comité d'église</h2>
                    <p class="step-description">
                        <strong>6 mois avant le mariage civil</strong> - Les démarches importantes à effectuer avant de se présenter au comité d'église.
                    </p>
                    <ul class="step-checklist">
                        <li><strong>Informez le président de la JAD</strong> (Jeunesse de l'Assemblée de Dieu)</li>
                        <li><strong>Prévenez les responsables de département</strong> dans lequel vous militez :
                            <ul style="margin-left: 20px; margin-top: 10px;">
                                <li>Responsable de classe d'école de dimanche (EDL), etc.</li>
                                <li>Président du département (groupe musical, chorale etc.)</li>
                                <li>Prévénir les pasteurs avant de se présenter au comité d'église</li>
                            </ul>
                        </li>
                        <li><strong>Soumettez votre demande écrite</strong> au comité d'église</li>
                        <li><strong>Participez aux séances de préparation</strong> au mariage organisées par l'église</li>
                        <li><strong>Obtenez les certificats nécessaires</strong> :
                            <ul style="margin-left: 20px; margin-top: 10px;">
                                <li>Certificat de baptême si néccéssaire</li>
                                <li>Attestation de célibat</li>
                                <li>Attestation de bonne conduite</li>
                                <li>Attestation de non-antécédents judiciaires</li>
                            </ul>
                        </li>
                        <li><strong>Planifiez les rencontres</strong> avec le pasteur ou le conseiller conjugal</li>
                        <li><strong>Préparez votre témoignage</strong> de conversion et d'engagement</li>
                    </ul>
                    <div class="step-tip" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #2196f3;">
                        <strong>💡 Conseil important :</strong> Ces démarches doivent être faites au moins 6 mois avant la date prévue du mariage civil. Le comité d'église se réunit généralement une fois par mois, prévoyez donc suffisamment de temps pour que votre dossier soit examiné.
                    </div>
                    <span class="duration"><i class="fas fa-clock"></i> 6 mois minimum avant le mariage civil</span>
                </div>
            </div>
            <!-- Étape 1 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-ring"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape 1</span>
                    <h2 class="step-title">La Demande en Mariage</h2>
                    <p class="step-description">
                        Première étape officielle : demander la main de votre bien-aimée. Cette étape est cruciale et doit être préparée avec soin.
                    </p>
                    <ul class="step-checklist">
                        <li>Préparer une bague de fiançailles</li>
                        <li>Choisir le moment et le lieu parfaits</li>
                        <li>Obtenir la bénédiction des familles</li>
                        <li>Faire la demande officielle</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> 1-2 mois avant</span>
                </div>
            </div>

            <!-- Étape 2 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape 2</span>
                    <h2 class="step-title">Prise de Contact avec la Belle-Famille</h2>
                    <p class="step-description">
                        Rencontre formelle avec la famille de la future épouse pour demander officiellement sa main et discuter des arrangements.
                    </p>
                    <ul class="step-checklist">
                        <li>Préparer une enveloppe symbolique</li>
                        <li>Apporter des présents (boissons, etc.)</li>
                        <li>Prévoir les frais de déplacement</li>
                        <li>Se faire accompagner par des membres de sa famille</li>
                        <li>Fixer la date de la dot</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> 1 mois avant la dot</span>
                </div>
            </div>

            <!-- Étape 3 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape 3</span>
                    <h2 class="step-title">La Dot (Cérémonie Traditionnelle)</h2>
                    <p class="step-description">
                        Cérémonie traditionnelle où le futur marié présente la dot à la famille de la mariée selon les coutumes.
                    </p>
                    <ul class="step-checklist">
                        <li>Rassembler tous les éléments de la dot</li>
                        <li>Préparer la valise et les pagnes</li>
                        <li>Les ustensiles de cuisine complets</li>
                        <li>Les enveloppes (fille, famille, frères et sœurs)</li>
                        <li>Les boissons et collations</li>
                        <li>Organiser le cortège</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> 2-3 mois avant le mariage</span>
                </div>
            </div>

            <!-- Étape 4 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-landmark"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape 4</span>
                    <h2 class="step-title">Mariage Civil à la Mairie</h2>
                    <p class="step-description">
                        Légalisation de votre union devant l'officier d'état civil. Cette étape est obligatoire légalement.
                    </p>
                    <ul class="step-checklist">
                        <li>Constituer le dossier de mariage</li>
                        <li>Publier les bans</li>
                        <li>Réunir les témoins (2 minimum)</li>
                        <li>Réserver la salle de célébration</li>
                        <li>Préparer la petite réception</li>
                        <li>Prévoir les tenues civiles</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> 1-2 semaines avant la bénédictions</span>
                </div>
            </div>

            <!-- Étape 5 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-church"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape 5</span>
                    <h2 class="step-title">Célébration religieuse à l'Église (bénédiction nuptiale)</h2>
                    <p class="step-description">
                        Bénédiction de votre union devant Dieu, en présence de la communauté religieuse et de vos proches.
                    </p>
                    <ul class="step-checklist">
                        <li>Suivre les séances de préparation au mariage</li>
                        <li>Louer ou acheter la robe de mariée</li>
                        <li> Acheter le costume</li>
                        <li>Choisir les témoins et cortège</li>
                        <li>Préparer les tenues pour le cortège</li>
                        <li>Commander les alliances</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> Le jour J</span>
                </div>
            </div>

            <!-- Étape 6 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-glass-cheers"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape 6</span>
                    <h2 class="step-title">Réception et Fête</h2>
                    <p class="step-description">
                        Célébration avec vos invités : repas, animations, et moments de joie partagée avec famille et amis.
                    </p>
                    <ul class="step-checklist">
                        <li>Réserver la salle de réception (si possible)</li>
                        <li>Prévoir le traiteur et les boissons</li>
                        <li>Organiser la décoration</li>
                        <li>Réserver les animations (DJ, orchestre) si néccessaire</li>
                        <li>Préparer le gâteau de mariage</li>
                        <li>Planifier le menu</li>
                        <li>Gérer la liste des invités</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> Le jour J (après l'église)</span>
                </div>
            </div>

            <!-- Étape 7 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape 7</span>
                    <h2 class="step-title">Logistique et Organisation</h2>
                    <p class="step-description">
                        Coordination de tous les aspects pratiques pour assurer le bon déroulement de la journée.
                    </p>
                    <ul class="step-checklist">
                        <li>Louer les véhicules de transport</li>
                        <li>Engager un photographe/vidéaste</li>
                        <li>Prévoir la sonorisation</li>
                        <li>Imprimer les faire-part et programmes</li>
                        <li>Organiser les répétitions</li>
                        <li>Coordonner les horaires</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> Tout au long de la préparation</span>
                </div>
            </div>

            <!-- Étape 8 -->
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="timeline-content">
                    <span class="step-number">Étape Finale</span>
                    <h2 class="step-title">Après le Mariage</h2>
                    <p class="step-description">
                        Les formalités et moments qui suivent la célébration.
                    </p>
                    <ul class="step-checklist">
                        <li>Récupérer les photos et vidéos</li>
                        <li>Envoyer les remerciements</li>
                        <li>Retirer le livret de famille</li>
                        <li>Installer le foyer</li>
                    </ul>
                    <span class="duration"><i class="fas fa-clock"></i> Après le mariage</span>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin: 60px 0;">
            <a href="index.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
                <i class="fas fa-calculator"></i> Gérer mon Budget
            </a>
        </div>
    </div>
    <?php include './includes/footer.php'; ?>
</body>
</html>
