# 👥 Guide du Système Multi-Utilisateurs

## 🎯 Qu'est-ce que c'est ?

Le système multi-utilisateurs permet à **plusieurs personnes** d'utiliser la même application pour gérer leur budget de mariage **séparément**.

### **Avant :** Une seule base de données commune
```
Application
    └── Toutes les dépenses de tout le monde mélangées
```

### **Maintenant :** Chacun son budget privé
```
Application
    ├── User 1 (Jean) → Ses dépenses uniquement
    ├── User 2 (Marie) → Ses dépenses uniquement
    └── User 3 (Paul) → Ses dépenses uniquement
```

## ✨ Fonctionnement

### **Scénario 1 : Jean se connecte**
1. Jean se connecte avec son compte
2. Il voit **UNIQUEMENT** ses dépenses
3. Il peut ajouter/modifier/supprimer **UNIQUEMENT** ses dépenses
4. Il **NE VOIT PAS** les dépenses de Marie ou Paul

### **Scénario 2 : Marie se connecte**
1. Marie se connecte avec son compte
2. Elle voit **UNIQUEMENT** ses dépenses
3. Elle **NE VOIT PAS** les dépenses de Jean ou Paul
4. Chacun travaille sur son propre budget

## 🔄 Migration depuis l'Ancien Système

Si vous aviez déjà des données dans l'application :

### **Étape 1 : Exécuter la Migration**
1. Accédez à `http://localhost/wedding-budget-php/migrate.php`
2. Le script va automatiquement :
   - Créer la table `users`
   - Ajouter la colonne `user_id` à `expenses`
   - Créer un compte admin
   - Assigner toutes vos anciennes dépenses à l'admin

### **Étape 2 : Connexion Admin**
```
Username: admin
Password: admin123
```

**⚠️ IMPORTANT :** Changez ce mot de passe immédiatement après la première connexion !

### **Étape 3 : Créer d'Autres Comptes**
1. Cliquez sur "Déconnexion"
2. Cliquez sur "S'inscrire"
3. Créez les comptes pour les autres utilisateurs

## 🆕 Nouvelle Installation

Si vous installez l'application pour la première fois :

### **Option A : Avec Installation Standard**
1. Exécutez `install.php` normalement
2. **Aucune donnée initiale** ne sera créée
3. Créez votre premier compte via "S'inscrire"
4. Commencez à ajouter vos dépenses

### **Option B : Avec Données de Démonstration + Admin**
1. Exécutez `install.php`
2. Exécutez `migrate.php` pour créer le compte admin
3. Connectez-vous avec admin/admin123
4. Les données de démonstration seront visibles par l'admin

## 📊 Structure de la Base de Données

### **Table `users`**
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(100),
    role ENUM('admin', 'user'),
    created_at TIMESTAMP,
    last_login TIMESTAMP
);
```

### **Table `expenses` (Modifiée)**
```sql
CREATE TABLE expenses (
    id INT PRIMARY KEY,
    user_id INT NOT NULL,  -- NOUVEAU !
    category_id INT,
    name VARCHAR(255),
    quantity INT,
    unit_price DECIMAL(10,2),
    frequency INT,
    paid BOOLEAN,
    payment_date DATE,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🔐 Sécurité et Isolation

### **Isolation Complète**
- ✅ Chaque utilisateur ne voit QUE ses propres données
- ✅ Impossible de voir les dépenses des autres
- ✅ Impossible de modifier les dépenses des autres
- ✅ Impossible de supprimer les dépenses des autres

### **Comment ça marche ?**
```php
// Dans l'API
$userId = AuthManager::getCurrentUser()['id'];
$expenses = $manager->getAllExpenses($userId);

// SQL généré
SELECT * FROM expenses WHERE user_id = 5;
// → Ne retourne QUE les dépenses de l'utilisateur 5
```

## 👨‍👩‍👧‍👦 Cas d'Usage

### **Cas 1 : Couple qui Prépare son Mariage**
```
Jean (Futur Marié)
├── Gère les dépenses côté homme
├── Voit son budget total
└── Suit ses paiements

Marie (Future Mariée)
├── Gère les dépenses côté femme
├── Voit son budget total
└── Suit ses paiements
```

### **Cas 2 : Plusieurs Couples**
```
Couple 1 (Jean & Sophie)
├── Compte Jean
└── Compte Sophie

Couple 2 (Paul & Emma)
├── Compte Paul
└── Compte Emma

Couple 3 (Luc & Anna)
├── Compte Luc
└── Compte Anna
```

### **Cas 3 : Organisateur d'Événements**
```
Organisateur (Compte Pro)
├── Mariage Client 1
├── Mariage Client 2
└── Mariage Client 3
```

## 🎓 Guide du Mariage

### **Nouvelle Page : guide.php**

Une page complète avec toutes les étapes du mariage :

1. **La Demande en Mariage**
2. **Prise de Contact Belle-Famille**
3. **La Dot**
4. **Mariage Civil**
5. **Célébration Religieuse**
6. **Réception**
7. **Logistique**
8. **Après le Mariage**

**Accès :** Cliquez sur l'onglet "📖 Guide du Mariage" dans l'application

## 📝 Checklist de Mise en Place

### **Pour Migration (Données Existantes)**
- [ ] Sauvegarder la base de données actuelle
- [ ] Accéder à `migrate.php`
- [ ] Vérifier que tout s'est bien passé
- [ ] Se connecter avec admin/admin123
- [ ] Changer le mot de passe admin
- [ ] Créer les autres comptes utilisateurs
- [ ] Tester l'isolation des données

### **Pour Nouvelle Installation**
- [ ] Exécuter `install.php`
- [ ] Créer votre premier compte via "S'inscrire"
- [ ] Ajouter vos dépenses
- [ ] Inviter d'autres utilisateurs à s'inscrire

## 🛠️ Fichiers Modifiés

| Fichier | Modifications |
|---------|---------------|
| `ExpenseManager.php` | Filtrage par user_id |
| `api.php` | Récupération du user_id |
| `index.php` | Ajout lien Guide |
| `guide.php` | **NOUVEAU** - Page guide |
| `migrate.php` | **NOUVEAU** - Script de migration |

## ❓ FAQ

### **Q : Puis-je partager mon budget avec quelqu'un ?**
**R :** Non, chaque budget est privé et isolé. C'est par design pour la sécurité.

### **Q : Comment voir les budgets de plusieurs mariages ?**
**R :** Créez un compte séparé pour chaque mariage.

### **Q : Que se passe-t-il si je supprime mon compte ?**
**R :** Toutes vos dépenses seront supprimées (CASCADE).

### **Q : Puis-je exporter mes données ?**
**R :** Vous pouvez faire un export SQL de votre base de données.

### **Q : L'admin voit-il tout ?**
**R :** Non, même l'admin ne voit que SES propres dépenses.

## 🎉 Avantages

✅ **Confidentialité** - Vos données restent privées
✅ **Multi-usage** - Une seule installation, plusieurs mariages
✅ **Isolation** - Aucun risque de mélanger les budgets
✅ **Simplicité** - Interface identique pour tous
✅ **Sécurité** - Authentification obligatoire pour les modifications

## 🚀 Prochaines Étapes

1. Exécutez la migration si nécessaire
2. Connectez-vous ou créez votre compte
3. Consultez le Guide du Mariage
4. Commencez à planifier votre budget !

**Bon mariage à tous ! 💑💍**
