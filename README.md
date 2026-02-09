# 💍 Application Budget Mariage - PJPM

Application web complète en PHP/MySQL pour gérer le budget de votre mariage.

## 🎯 Fonctionnalités

### ✨ Principales fonctionnalités :
- **Tableau de bord** avec statistiques et progression des paiements
- **CRUD complet** : Créer, Lire, Modifier, Supprimer des dépenses
- **Gestion par catégories** avec sous-totaux automatiques
- **Suivi des paiements** : Marquer les dépenses comme payées/non payées
- **Statistiques en temps réel** : Budget total, montant payé, reste à payer
- **Interface responsive** : Compatible mobile, tablette et desktop
- **Design moderne** : Interface élégante aux couleurs du mariage

## 📋 Prérequis

- **Serveur Web** : Apache, Nginx, ou tout serveur supportant PHP
- **PHP** : Version 7.4 ou supérieure
- **MySQL** : Version 5.7 ou supérieure (ou MariaDB 10.2+)
- **Extensions PHP** : PDO, pdo_mysql

## 🚀 Installation

### 1. Configuration du serveur

#### Option A : XAMPP / WAMP / MAMP
1. Téléchargez et installez XAMPP, WAMP ou MAMP
2. Démarrez Apache et MySQL
3. Copiez tous les fichiers dans le dossier `htdocs` (XAMPP) ou `www` (WAMP)

#### Option B : Serveur Linux
```bash
# Installer Apache, PHP et MySQL
sudo apt update
sudo apt install apache2 php php-mysql mysql-server

# Copier les fichiers dans le répertoire web
sudo cp -r wedding-budget-php /var/www/html/
```

### 2. Configuration de la base de données

1. **Ouvrez phpMyAdmin** ou connectez-vous à MySQL :
   ```bash
   mysql -u root -p
   ```

2. **Modifiez le fichier `config.php`** si nécessaire :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');          // Votre utilisateur MySQL
   define('DB_PASS', '');              // Votre mot de passe MySQL
   define('DB_NAME', 'wedding_budget');
   ```

### 3. Installation de la base de données

1. Ouvrez votre navigateur
2. Accédez à : `http://localhost/wedding-budget-php/install.php`
3. Le script créera automatiquement :
   - La base de données `wedding_budget`
   - Les tables `categories` et `expenses`
   - Les données initiales (catégories et dépenses)

4. Une fois l'installation terminée, cliquez sur "Accéder à l'application"

### 4. Utilisation

Accédez à l'application : `http://localhost/wedding-budget-php/`

## 📁 Structure du projet

```
wedding-budget-php/
│
├── config.php              # Configuration de la base de données
├── install.php             # Script d'installation (à exécuter une seule fois)
├── ExpenseManager.php      # Classe de gestion des dépenses (CRUD)
├── api.php                 # API REST pour les opérations AJAX
├── index.php               # Page principale de l'application
├── style.css               # Styles CSS
├── script.js               # JavaScript (interactivité et appels API)
└── README.md               # Ce fichier
```

## 🗄️ Structure de la base de données

### Table `categories`
| Colonne        | Type         | Description                    |
|----------------|--------------|--------------------------------|
| id             | INT          | Identifiant unique (PK)        |
| name           | VARCHAR(255) | Nom de la catégorie            |
| display_order  | INT          | Ordre d'affichage              |
| created_at     | TIMESTAMP    | Date de création               |

### Table `expenses`
| Colonne        | Type          | Description                    |
|----------------|---------------|--------------------------------|
| id             | INT           | Identifiant unique (PK)        |
| category_id    | INT           | ID de la catégorie (FK)        |
| name           | VARCHAR(255)  | Nom de la dépense              |
| quantity       | INT           | Quantité                       |
| unit_price     | DECIMAL(10,2) | Prix unitaire                  |
| frequency      | INT           | Fréquence                      |
| paid           | BOOLEAN       | Statut de paiement             |
| payment_date   | DATE          | Date de paiement               |
| notes          | TEXT          | Notes optionnelles             |
| created_at     | TIMESTAMP     | Date de création               |
| updated_at     | TIMESTAMP     | Date de modification           |

## 🎨 Personnalisation

### Modifier les couleurs
Éditez le fichier `style.css` et modifiez les variables CSS :

```css
:root {
    --primary: #8b4f8d;        /* Couleur principale */
    --primary-light: #b87bb8;  /* Couleur claire */
    --primary-dark: #5d2f5f;   /* Couleur foncée */
    --secondary: #d4af37;      /* Couleur secondaire (or) */
    --success: #4caf50;        /* Vert (succès) */
    --warning: #ff9800;        /* Orange (avertissement) */
    --danger: #f44336;         /* Rouge (danger) */
}
```

### Modifier les données initiales
Éditez le fichier `install.php` dans la section `$expenses_data` pour ajouter ou modifier les dépenses de départ.

## 🔧 API Endpoints

L'application utilise une API REST accessible via `api.php` :

- `GET api.php?action=get_all` - Récupérer toutes les dépenses
- `GET api.php?action=get_categories` - Récupérer toutes les catégories
- `GET api.php?action=get_stats` - Récupérer les statistiques
- `GET api.php?action=get_by_id&id={id}` - Récupérer une dépense par ID
- `POST api.php?action=add` - Ajouter une nouvelle dépense
- `POST api.php?action=update&id={id}` - Mettre à jour une dépense
- `GET api.php?action=delete&id={id}` - Supprimer une dépense
- `GET api.php?action=toggle_paid&id={id}` - Basculer le statut de paiement
- `GET api.php?action=category_stats` - Statistiques par catégorie

## 🛡️ Sécurité

- Les requêtes SQL utilisent des **requêtes préparées PDO** pour éviter les injections SQL
- Validation des données côté serveur
- Protection CSRF recommandée pour la production
- Utilisez HTTPS en production

## 📱 Responsive Design

L'application est entièrement responsive et s'adapte à tous les écrans :
- 📱 Mobile : Interface optimisée pour petit écran
- 📱 Tablette : Affichage adapté
- 💻 Desktop : Expérience complète

## 🐛 Dépannage

### Erreur de connexion à la base de données
- Vérifiez les paramètres dans `config.php`
- Assurez-vous que MySQL est démarré
- Vérifiez les permissions de l'utilisateur MySQL

### Page blanche
- Activez l'affichage des erreurs PHP :
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Vérifiez les logs Apache/PHP

### Problème avec les caractères accentués
- Vérifiez que la base de données utilise UTF-8 :
  ```sql
  ALTER DATABASE wedding_budget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

## 📝 Licence

Projet personnel pour la gestion du budget de mariage PJPM.

## 🤝 Support

Pour toute question ou problème, contactez l'administrateur du projet.

## 🎉 Bon mariage ! 💑

---

**Développé avec ❤️ pour le Projet Jésus Pourvoir Ménage**
