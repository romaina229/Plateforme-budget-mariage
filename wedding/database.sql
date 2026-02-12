-- ================================================================
-- database.sql — Schéma BDD — Budget Mariage PJPM v2.1
-- Exécuter dans phpMyAdmin ou : mysql -u root -p wedding < database.sql
-- ================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = 'Africa/Porto-Novo';

-- Créer la base si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `wedding`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `wedding`;

-- ── Table : users ─────────────────────────────────────────────
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(80)    NOT NULL,
    `email`         VARCHAR(180)   NOT NULL,
    `password_hash` VARCHAR(255)   NOT NULL,
    `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login`    DATETIME       NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email`    (`email`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Table : categories ────────────────────────────────────────
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(100) NOT NULL,
    `icon`         VARCHAR(60)  NOT NULL DEFAULT 'fas fa-folder',
    `color`        VARCHAR(20)  NOT NULL DEFAULT '#8b4f8d',
    `sort_order`   SMALLINT     NOT NULL DEFAULT 0,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Table : expenses ──────────────────────────────────────────
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `category_id`   INT UNSIGNED    NOT NULL,
    `name`          VARCHAR(255)    NOT NULL,
    `quantity`      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price`    DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `frequency`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `paid`          TINYINT(1)      NOT NULL DEFAULT 0,
    `payment_date`  DATE            NULL,
    `notes`         TEXT            NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user`     (`user_id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_paid`     (`paid`),
    CONSTRAINT `fk_expense_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Table : wedding_dates ─────────────────────────────────────
DROP TABLE IF EXISTS `wedding_dates`;
CREATE TABLE `wedding_dates` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `wedding_date` DATE         NOT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_date` (`user_id`),
    CONSTRAINT `fk_date_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Table : admin_logs ────────────────────────────────────────
DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE `admin_logs` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED  NULL,
    `action`     VARCHAR(120)  NOT NULL,
    `details`    TEXT          NULL,
    `ip_address` VARCHAR(45)   NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user`   (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_date`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Catégories par défaut ─────────────────────────────────────
INSERT INTO `categories` (`name`, `icon`, `color`, `sort_order`) VALUES
('Cérémonie',      'fas fa-church',         '#8b4f8d', 1),
('Réception',      'fas fa-glass-cheers',   '#b87bb8', 2),
('Robe & Tenue',   'fas fa-tshirt',         '#d4af37', 3),
('Traiteur',       'fas fa-utensils',       '#e67e22', 4),
('Photographie',   'fas fa-camera',         '#3498db', 5),
('Décoration',     'fas fa-star',           '#2ecc71', 6),
('Musique & DJ',   'fas fa-music',          '#9b59b6', 7),
('Invitations',    'fas fa-envelope',       '#1abc9c', 8),
('Transport',      'fas fa-car',            '#e74c3c', 9),
('Lune de miel',   'fas fa-plane',          '#16a085', 10),
('Alliance',       'fas fa-ring',           '#d4af37', 11),
('Divers',         'fas fa-ellipsis-h',     '#95a5a6', 12);

-- ── Compte administrateur par défaut ─────────────────────────
-- Mot de passe : Admin@2025 (à changer après installation)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@wedding.local',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin');
-- NOTE: Le hash ci-dessus correspond au mot de passe 'password'
-- Utilisez install.php pour créer votre propre compte

