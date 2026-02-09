# 🔐 Guide du Système d'Authentification

## 📋 Vue d'Ensemble

L'application Budget Mariage dispose maintenant d'un **système d'authentification complet** qui protège les actions sensibles tout en laissant l'accès en lecture libre.

## ✨ Fonctionnalités

### **1. Accès Public (Sans Connexion)**
✅ Consulter le tableau de bord
✅ Voir toutes les dépenses
✅ Voir les statistiques
✅ Utiliser les filtres
✅ Naviguer dans l'application

### **2. Actions Protégées (Connexion Requise)**
🔒 Ajouter une nouvelle dépense
🔒 Modifier une dépense existante
🔒 Supprimer une dépense
🔒 Marquer comme payé/non payé

## 🚀 Utilisation

### **Pour les Visiteurs**
1. Accédez à `index.php`
2. Consultez librement le budget
3. Si vous tentez une action protégée, une popup vous invite à vous connecter

### **Pour les Utilisateurs Enregistrés**
1. Cliquez sur **"Connexion"** en haut à droite
2. Entrez votre nom d'utilisateur et mot de passe
3. Vous pouvez maintenant effectuer toutes les actions

### **Première Utilisation**
1. Cliquez sur **"Connexion"**
2. Cliquez sur **"S'inscrire"**
3. Remplissez le formulaire d'inscription
4. Connectez-vous avec vos identifiants

## 📁 Fichiers du Système

### **Nouveaux Fichiers**

| Fichier | Description |
|---------|-------------|
| `AuthManager.php` | Classe de gestion de l'authentification |
| `auth_api.php` | API REST pour login/register/logout |
| `login.php` | Page de connexion |
| `register.php` | Page d'inscription |

### **Fichiers Modifiés**

| Fichier | Modifications |
|---------|---------------|
| `index.php` | Ajout du bouton connexion/déconnexion |
| `script.js` | Protection des actions sensibles |
| `style.css` | Styles pour les boutons d'authentification |

## 🗄️ Base de Données

### **Nouvelle Table : `users`**

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
```

**Colonnes :**
- `username` : Nom d'utilisateur unique (min 3 caractères)
- `email` : Adresse email unique
- `password` : Mot de passe hashé avec bcrypt
- `full_name` : Nom complet (optionnel)
- `role` : Rôle (admin/user) - réservé pour usage futur
- `created_at` : Date de création du compte
- `last_login` : Date de dernière connexion

## 🔒 Sécurité

### **Mots de Passe**
- ✅ Hashés avec `password_hash()` (bcrypt)
- ✅ Minimum 6 caractères requis
- ✅ Indicateur de force lors de l'inscription
- ✅ Jamais stockés en clair

### **Sessions**
- ✅ Sessions PHP sécurisées
- ✅ Vérification à chaque action protégée
- ✅ Déconnexion propre

### **Validation**
- ✅ Validation côté client (JavaScript)
- ✅ Validation côté serveur (PHP)
- ✅ Protection contre les injections SQL (PDO prepared statements)

## 🎯 Workflow Utilisateur

### **Scénario 1 : Nouveau Visiteur**
```
Visiteur accède à index.php
    ↓
Consulte les dépenses librement
    ↓
Clique sur "Ajouter une dépense"
    ↓
Popup : "Vous devez être connecté"
    ↓
Redirigé vers login.php
    ↓
Clique sur "S'inscrire"
    ↓
Remplit le formulaire
    ↓
Compte créé → Connexion
    ↓
Peut maintenant ajouter/modifier/supprimer
```

### **Scénario 2 : Utilisateur Existant**
```
Utilisateur accède à index.php
    ↓
Clique sur "Connexion"
    ↓
Entre ses identifiants
    ↓
Connecté → Peut tout faire
    ↓
Clique sur "Déconnexion" quand fini
```

## ⚙️ Installation

### **Si Installation Neuve**
1. Exécutez `install.php` normalement
2. La table `users` sera créée automatiquement

### **Si Application Déjà Installée**
1. La table `users` sera créée au premier accès
2. Ou créez-la manuellement :

```sql
USE wedding_budget;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 👥 Gestion des Utilisateurs

### **Créer un Compte Admin**

Par défaut, tous les comptes sont `user`. Pour créer un admin :

```sql
-- Créer un compte normalement via register.php
-- Puis promouvoir en admin :
UPDATE users SET role = 'admin' WHERE username = 'votre_username';
```

### **Voir Tous les Utilisateurs**

```sql
SELECT id, username, email, full_name, role, created_at, last_login 
FROM users 
ORDER BY created_at DESC;
```

### **Supprimer un Utilisateur**

```sql
DELETE FROM users WHERE username = 'username_a_supprimer';
```

## 🔧 Personnalisation

### **Modifier la Longueur Minimale du Mot de Passe**

Dans `AuthManager.php` :
```php
if (strlen($password) < 6) { // Changez 6 par votre valeur
    return ['success' => false, 'message' => '...'];
}
```

### **Modifier le Message de Protection**

Dans `script.js` :
```javascript
function requireAuth() {
    if (!isUserLoggedIn) {
        if (confirm('VOTRE MESSAGE ICI')) {
            window.location.href = 'login.php';
        }
        return false;
    }
    return true;
}
```

### **Ajouter d'Autres Actions Protégées**

Dans `script.js`, ajoutez `if (!requireAuth()) return;` au début de la fonction :

```javascript
function maNouvelleFonction() {
    if (!requireAuth()) return;
    
    // Votre code ici
}
```

## 📱 Pages du Système

### **1. login.php**
- Design élégant avec dégradé violet
- Champs : username/email + password
- Lien vers inscription
- Retour à l'accueil

### **2. register.php**
- Formulaire d'inscription complet
- Indicateur de force du mot de passe
- Validation en temps réel
- Confirmation du mot de passe

### **3. index.php (Modifié)**
- Bouton "Connexion" si non connecté
- Affichage du nom d'utilisateur si connecté
- Bouton "Déconnexion"

## 🎨 Interface

### **Bouton Connexion** (Non connecté)
```
┌─────────────────────────┐
│  🔒 Connexion          │
└─────────────────────────┘
```

### **Section Utilisateur** (Connecté)
```
┌─────────────────────────┐
│  👤 Jean Dupont        │
│  🚪 Déconnexion        │
└─────────────────────────┘
```

## 💡 Conseils

### **Pour les Administrateurs**
1. Créez un compte admin dès le début
2. Partagez le lien d'inscription avec votre équipe
3. Surveillez les comptes créés dans la BDD

### **Pour les Utilisateurs**
1. Utilisez un mot de passe fort (10+ caractères)
2. Ne partagez pas vos identifiants
3. Déconnectez-vous après utilisation

## 🐛 Dépannage

### **Problème : "Session déjà démarrée"**
**Solution :** Vérifiez que `session_start()` n'est pas appelé plusieurs fois

### **Problème : "Table users n'existe pas"**
**Solution :** Exécutez le SQL de création de table ci-dessus

### **Problème : "Impossible de se connecter"**
**Solution :**
1. Vérifiez que le username et password sont corrects
2. Vérifiez que la table `users` existe
3. Vérifiez les logs d'erreur PHP

### **Problème : "Toujours redirigé vers login"**
**Solution :**
1. Vérifiez que les sessions PHP fonctionnent
2. Vérifiez les cookies du navigateur
3. Essayez un autre navigateur

## 🎉 Résultat Final

Votre application a maintenant :
- ✅ Un système de connexion professionnel
- ✅ Une inscription sécurisée
- ✅ Une protection des actions sensibles
- ✅ Un accès en lecture pour tous
- ✅ Une interface utilisateur fluide

**Félicitations ! Votre budget de mariage est maintenant sécurisé ! 🔐💍**
