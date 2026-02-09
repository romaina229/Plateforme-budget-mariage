# 🎨 Guide des Icônes et Couleurs des Catégories

## ✨ Nouvelle Fonctionnalité

L'application affiche maintenant des **icônes colorées** pour chaque catégorie, rendant l'interface plus visuelle et intuitive !

## 🎯 Icônes par Catégorie

Voici les icônes et couleurs définies pour chaque catégorie :

| Catégorie | Icône | Couleur | Code |
|-----------|-------|---------|------|
| **Prise de contact avec la belle famille** | 🤝 `fa-handshake` | Bleu | `#3498db` |
| **Dot** | 🎁 `fa-gift` | Violet | `#9b59b6` |
| **Mairie** | 🏛️ `fa-landmark` | Rouge | `#e74c3c` |
| **Célébration à l'église** | ⛪ `fa-church` | Vert | `#2ecc71` |
| **Logistique** | 🚚 `fa-truck` | Turquoise | `#1abc9c` |
| **Réception** | 🥂 `fa-glass-cheers` | Orange | `#f39c12` |
| **Coût indirect et imprévus** | ⚠️ `fa-exclamation-triangle` | Gris | `#95a5a6` |

## 📍 Où Apparaissent les Icônes ?

### 1. **Tableau de Bord - Récapitulatif par Catégorie**
Chaque ligne de catégorie affiche son icône colorée à côté du nom

### 2. **Détails des Dépenses**
Les en-têtes de catégorie affichent l'icône correspondante

### 3. **Formulaire d'Ajout/Modification**
Le select des catégories peut afficher les icônes (optionnel)

## 🔧 Structure Technique

### Base de Données

La table `categories` contient maintenant 3 colonnes supplémentaires :

```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#3498db',      -- Couleur hexadécimale
    icon VARCHAR(50) DEFAULT 'fas fa-folder', -- Classe Font Awesome
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Font Awesome

Les icônes utilisent la bibliothèque **Font Awesome 6.4.0** qui est chargée automatiquement :

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

### Affichage JavaScript

Le code JavaScript récupère les couleurs et icônes depuis `currentCategories` :

```javascript
const categoryInfo = currentCategories.find(c => c.id == cat.id);
const color = categoryInfo?.color || '#8b4f8d';
const icon = categoryInfo?.icon || 'fas fa-folder';

// Affichage
<i class="${icon}" style="color: ${color}; font-size: 1.2rem;"></i>
```

## 🎨 Personnaliser les Icônes et Couleurs

### Méthode 1 : Modifier la Base de Données

```sql
-- Changer la couleur de la catégorie "Dot" en rose
UPDATE categories 
SET color = '#ff69b4' 
WHERE name = 'Dot';

-- Changer l'icône de "Mairie" en balance
UPDATE categories 
SET icon = 'fas fa-balance-scale' 
WHERE name = 'Mairie';
```

### Méthode 2 : Modifier install.php

Éditez le tableau `$categories` dans `install.php` :

```php
$categories = [
    ['name' => 'Dot', 'color' => '#ff69b4', 'icon' => 'fas fa-heart', 'order' => 2],
    // ...
];
```

**⚠️ Note :** Si vous modifiez `install.php`, vous devrez supprimer et recréer la base de données.

### Méthode 3 : Interface d'Administration (Future)

Une interface pour gérer les catégories depuis l'application sera ajoutée dans une future version.

## 📚 Liste d'Icônes Font Awesome Utiles

Quelques suggestions d'icônes pour les mariages :

| Usage | Icône | Code |
|-------|-------|------|
| Anneaux | 💍 | `fas fa-ring` |
| Cœur | ❤️ | `fas fa-heart` |
| Église | ⛪ | `fas fa-church` |
| Cadeau | 🎁 | `fas fa-gift` |
| Champagne | 🍾 | `fas fa-champagne-glasses` |
| Gâteau | 🎂 | `fas fa-cake-candles` |
| Musique | 🎵 | `fas fa-music` |
| Photo | 📷 | `fas fa-camera` |
| Voiture | 🚗 | `fas fa-car` |
| Fleurs | 🌸 | `fas fa-flower` |
| Couverts | 🍽️ | `fas fa-utensils` |
| Verre | 🥂 | `fas fa-glass-cheers` |

**Recherchez plus d'icônes :** [fontawesome.com/icons](https://fontawesome.com/icons)

## 🎨 Palette de Couleurs Recommandées

### Couleurs Mariage
- Rose tendre : `#ffb6c1`
- Rose vif : `#ff69b4`
- Or : `#ffd700`
- Champagne : `#f7e7ce`
- Blanc cassé : `#f5f5dc`

### Couleurs Vives
- Rouge passion : `#e74c3c`
- Orange joyeux : `#f39c12`
- Vert émeraude : `#2ecc71`
- Bleu royal : `#3498db`
- Violet élégant : `#9b59b6`

### Couleurs Neutres
- Gris ardoise : `#95a5a6`
- Brun chocolat : `#8b4513`
- Beige sable : `#d2b48c`

## 🔄 Mise à Jour

Si vous avez déjà installé l'application **sans** les icônes :

### Option 1 : Ajouter les Colonnes Manuellement

```sql
-- Se connecter à la base de données
USE wedding_budget;

-- Ajouter les colonnes si elles n'existent pas
ALTER TABLE categories 
ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT '#3498db',
ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT 'fas fa-folder';

-- Mettre à jour les catégories existantes
UPDATE categories SET color = '#3498db', icon = 'fas fa-handshake' WHERE name LIKE '%Prise de contact%';
UPDATE categories SET color = '#9b59b6', icon = 'fas fa-gift' WHERE name LIKE '%Dot%';
UPDATE categories SET color = '#e74c3c', icon = 'fas fa-landmark' WHERE name LIKE '%Mairie%';
UPDATE categories SET color = '#2ecc71', icon = 'fas fa-church' WHERE name LIKE '%église%';
UPDATE categories SET color = '#1abc9c', icon = 'fas fa-truck' WHERE name LIKE '%Logistique%';
UPDATE categories SET color = '#f39c12', icon = 'fas fa-glass-cheers' WHERE name LIKE '%Réception%';
UPDATE categories SET color = '#95a5a6', icon = 'fas fa-exclamation-triangle' WHERE name LIKE '%indirect%';
```

### Option 2 : Réinstaller

1. **Sauvegarder** vos données (exporter depuis phpMyAdmin)
2. Supprimer la base de données `wedding_budget`
3. Relancer `install.php`
4. **Réimporter** vos données

## ✅ Vérification

Pour vérifier que les icônes fonctionnent :

1. Accédez au **Tableau de Bord**
2. Dans le **"Récapitulatif par Catégorie"**, vous devriez voir :
   - 🤝 Prise de contact (bleu)
   - 🎁 Dot (violet)
   - 🏛️ Mairie (rouge)
   - ⛪ Célébration à l'église (vert)
   - 🚚 Logistique (turquoise)
   - 🥂 Réception (orange)
   - ⚠️ Coût indirect (gris)

## 🎊 Résultat

Votre application est maintenant plus **belle**, plus **intuitive** et plus **facile à naviguer** !

Les icônes colorées permettent d'identifier rapidement chaque catégorie d'un seul coup d'œil. 👀✨
