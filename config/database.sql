-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `wedding` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wedding`;


-- Supprimer la table si elle existe
DROP TABLE IF EXISTS `users`;

-- Créer la table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE,
  `email` VARCHAR(100) UNIQUE,
  `password` VARCHAR(255),
  `full_name` VARCHAR(100),
  `role` ENUM('admin', 'user'),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer l'utilisateur admin
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('Administrateur', 'liferopro@gmail.com', '$2y$10$YourHashedPasswordHere', 'Administrateur Principal', 'admin');
-- Note: Remplacez le hash par: password_hash('Admin13', PASSWORD_DEFAULT)

-- Supprimer la table si elle existe
DROP TABLE IF EXISTS `categories`;

-- Créer la table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `color` VARCHAR(7) DEFAULT '#3498db',
  `icon` VARCHAR(50) DEFAULT 'fas fa-folder',
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les catégories
INSERT INTO `categories` (`name`, `color`, `icon`, `display_order`) VALUES
('Connaissance', '#3498db', 'fas fa-handshake', 1),
('Dot', '#9b59b6', 'fas fa-gift', 2),
('Mariage civil', '#e74c3c', 'fas fa-landmark', 3),
('Bénédiction nuptiale', '#2ecc71', 'fas fa-church', 4),
('Logistique', '#1abc9c', 'fas fa-truck', 5),
('Réception', '#f39c12', 'fas fa-glass-cheers', 6),
('Coût indirect et imprévus', '#95a5a6', 'fas fa-exclamation-triangle', 7);

-- Supprimer la table si elle existe
DROP TABLE IF EXISTS `expenses`;

-- Créer la table
CREATE TABLE `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `frequency` INT NOT NULL DEFAULT 1,
  `paid` BOOLEAN DEFAULT FALSE,
  `payment_date` DATE NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_category` (`category_id`),
  INDEX `idx_paid` (`paid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--Créer la table si elle n'existe pas
CREATE TABLE IF NOT EXISTS wedding_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        wedding_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les dépenses (user_id = 1, category_id = 1-7)
INSERT INTO `expenses` (`user_id`, `category_id`, `name`, `quantity`, `unit_price`, `frequency`, `paid`) VALUES
-- Catégorie 1: Connaissance
(1, 1, 'Enveloppe', 2, 2000, 1, 1),
(1, 1, 'Bouteille de jus de risins', 2, 5000, 1, 1),
(1, 1, 'Deplacement', 1, 5000, 1, 1),

-- Catégorie 2: Dot
(1, 2, 'Bible', 1, 6000, 1, 0),
(1, 2, 'Valise', 1, 10000, 1, 0),
(1, 2, 'Pagne vlisco démi pièce', 2, 27000, 1, 0),
(1, 2, 'Pagne côte d''ivoire démi pièce', 5, 6500, 1, 0),
(1, 2, 'Pagne Ghana démi pièce', 4, 6500, 1, 0),
(1, 2, 'Ensemble de chaine', 3, 3000, 1, 0),
(1, 2, 'Chaussures', 3, 3000, 1, 0),
(1, 2, 'Sac à main', 2, 3500, 1, 0),
(1, 2, 'Montre et Bracelet', 2, 3000, 1, 0),
(1, 2, 'Série de bol', 3, 5500, 1, 0),
(1, 2, 'Demi-douzaine Assiettes en verre', 2, 4800, 1, 0),
(1, 2, 'Douzaine Assiettes en plastique', 2, 3000, 1, 0),
(1, 2, 'Série de casseroles', 1, 7000, 1, 0),
(1, 2, 'Marmites de 1kg, 1,5kg, 2kg et 3kg', 1, 11000, 1, 0),
(1, 2, 'Gobelets', 1, 2000, 1, 0),
(1, 2, 'Bols', 12, 1500, 1, 0),
(1, 2, 'Fourchettes', 1, 1500, 1, 0),
(1, 2, 'Cuillères', 1, 1500, 1, 0),
(1, 2, 'Couteaux', 1, 1500, 1, 0),
(1, 2, 'Pavie', 1, 1500, 1, 0),
(1, 2, 'Bassine aluminium grande', 1, 10000, 1, 0),
(1, 2, 'Bassine aluminium moyen', 2, 4000, 1, 0),
(1, 2, 'Bassine aluminium petite', 2, 2500, 1, 0),
(1, 2, 'Palette', 1, 500, 1, 0),
(1, 2, 'Raclette', 1, 1000, 1, 0),
(1, 2, 'Cuillères à sauce', 2, 800, 1, 0),
(1, 2, 'Gaz et accessoire complet', 1, 25000, 1, 0),
(1, 2, 'Seau de soin corporelle', 1, 10000, 1, 0),
(1, 2, 'Enveloppe fille', 1, 100000, 1, 0),
(1, 2, 'Enveloppe Famille', 1, 25000, 1, 0),
(1, 2, 'Enveloppe frères et soeurs', 1, 10000, 1, 0),
(1, 2, 'Sac de sel de cuisine', 1, 12000, 1, 0),
(1, 2, 'Allumettes paquets', 5, 125, 1, 0),
(1, 2, 'Liqueurs Assédekon', 2, 10000, 1, 0),
(1, 2, 'Jus de raisins', 10, 2500, 1, 0),
(1, 2, 'Sucrerie sobebra', 2, 5500, 1, 0),
(1, 2, 'Collation Echoc spirtuel', 1, 45000, 1, 0),

-- Catégorie 3: Mariage civile
(1, 3, 'Frais de dossier et réservation de la chambre de célébration à la mairie', 1, 50000, 1, 0),
(1, 3, 'Petite réception', 1, 50000, 1, 0),

-- Catégorie 4: Bénédiction nuptiale
(1, 4, 'Robe', 1, 20000, 1, 0),
(1, 4, 'Coustum', 1, 25000, 1, 0),
(1, 4, 'Chaussures', 2, 25000, 1, 0),
(1, 4, 'Bague de l''alliance', 1, 15000, 1, 0),
(1, 4, 'Tenues complet', 3, 15000, 1, 0),
(1, 4, 'Tenues complet fille', 4, 15000, 1, 0),

-- Catégorie 5: Logistique
(1, 5, 'Location de salle', 1, 150000, 1, 0),
(1, 5, 'Location de véhicule', 2, 35000, 1, 0),
(1, 5, 'Caburant', 20, 680, 1, 0),
(1, 5, 'Prise de vue', 1, 30000, 1, 0),
(1, 5, 'Sonorisation', 1, 20000, 1, 0),
(1, 5, 'Conception flyers', 1, 2000, 1, 0),

-- Catégorie 6: Réception
(1, 6, 'Boissons', 200, 600, 1, 0),
(1, 6, 'Poulets', 30, 2500, 1, 0),
(1, 6, 'Porcs', 1, 30000, 1, 0),
(1, 6, 'Poissons', 2, 35000, 1, 0),
(1, 6, 'Sac de riz', 1, 32000, 1, 0),
(1, 6, 'Farine de cossette d''igname', 20, 500, 1, 0),
(1, 6, 'Maïs pour akassa', 20, 200, 1, 0),
(1, 6, 'Bois de chauffe/Charbon', 20, 200, 1, 0),
(1, 6, 'Ensemble des ingrédients pour la cuisine', 1, 30000, 1, 0),

-- Catégorie 7: Coût indirect et imprévus
(1, 7, 'Ensemble des non prévus', 1, 73996, 1, 0);
