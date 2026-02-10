# Application mobile PJPM (Expo React Native)

Cette application mobile réutilise votre backend PHP existant (`/api/api.php` et `/api/auth_api.php`) pour gérer :
- planification mariage/évènement,
- dépenses,
- budget,
- paiements,
- date de mariage.

## Démarrage rapide (30 min)

### 1) Installer les dépendances
```bash
cd mobile-app
npm install
```

### 2) Lancer Expo
```bash
npm run start
```

### Expo Go SDK 54 (important)
Si votre téléphone a Expo Go **SDK 54** (cas actuel), il faut **mettre à jour le projet mobile** avant de lancer l'app :

```bash
cd mobile-app
npx expo upgrade 54
npx expo install --fix
npm install
npm run start
```

Ensuite, ouvrez à nouveau Expo Go et scannez le QR code.

## 3) Ouvrir l'app
- soit via **Expo Go** (Android/iOS),
- soit via un simulateur local.

### 4) Configurer l'URL API dans l'écran **Paramètres**
Exemples :
- Émulateur Android : `http://10.0.2.2/Plateforme-budget-mariage`
- iOS simulateur : `http://localhost/Plateforme-budget-mariage`
- Téléphone réel : `http://IP_LOCALE_DU_SERVEUR/Plateforme-budget-mariage`

---

## Fonctionnalités incluses
- Authentification (login/logout + vérification de session)
- Tableau de bord budget/paiements
- Gestion des dépenses (ajouter, marquer payé, supprimer)
- Catégories de dépenses
- Date de mariage (chargement/sauvegarde)
- Checklist planning avec stockage local (AsyncStorage)

## Build de production (Android/iOS)
```bash
npm install -g eas-cli
npx eas login
npx eas build:configure
npx eas build --platform android
npx eas build --platform ios
```

## Dépannage

### Échec de connexion API
1. Vérifier l'URL API dans l'écran Paramètres.
2. Vérifier que le backend PHP est joignable depuis le téléphone/émulateur.
3. Si domaine différent, configurer correctement CORS + cookies de session.

### Session non conservée
Le backend actuel utilise des sessions PHP. L'app envoie les requêtes avec `credentials: 'include'`.
Assurez-vous que vos cookies et domaine sont compatibles avec le mode d'accès mobile.
