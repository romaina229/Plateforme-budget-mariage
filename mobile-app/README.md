# Application mobile PJPM (Expo React Native)

Cette application mobile réutilise votre backend PHP existant (`/api/api.php` et `/api/auth_api.php`) pour gérer :
- planification mariage/évènement,
- dépenses,
- budget,
- paiements.

## 1) Installation

```bash
cd mobile-app
npm install
```

## 2) Lancer en développement

```bash
npm run start
```

Puis ouvrir via Expo Go (Android/iOS) ou simulateur.

## 3) Configuration API

Dans l'écran **settings** de l'application, configurez l'URL vers votre site web existant.

Exemples :
- Émulateur Android : `http://10.0.2.2/Plateforme-budget-mariage`
- iOS simulateur : `http://localhost/Plateforme-budget-mariage`
- Téléphone réel : `http://IP_LOCALE_DU_SERVEUR/Plateforme-budget-mariage`

## 4) Build et déploiement propre

```bash
npm install -g eas-cli
npx eas login
npx eas build:configure
npx eas build --platform android
npx eas build --platform ios
```

Vous obtenez des builds professionnelles sans modifier votre backend.

## 5) Notes importantes

- Le backend actuel est basé sur session PHP : l'app utilise `credentials: 'include'`.
- Activez CORS/cookies correctement si API et app ne sont pas sur le même domaine.
- Pour un déploiement à grande échelle, il est recommandé d'ajouter une authentification par token (JWT).
