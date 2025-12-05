# 🔐 Configuration de la Connexion Google OAuth

## 📋 Fonctionnalités Ajoutées

### ✅ Formulaire de Contact / Support
- **Page**: `/contact`
- **Fonctionnalités**:
  - Formulaire avec nom, email, sujet, message
  - Envoi d'email au support via Mailtrap
  - Interface moderne avec dégradés
  - Validation des champs
  - Messages flash de confirmation
  - Informations de contact affichées (adresse, téléphone, horaires)
  - Liens sociaux

### ✅ Connexion Sociale avec Google
- **Fonctionnalité**: Bouton "Continuer avec Google" sur la page de connexion
- **Avantages**:
  - Inscription/connexion en un clic
  - Pas besoin de mémoriser un mot de passe
  - Utilise l'authentification sécurisée de Google
  - Création automatique de compte si nouvel utilisateur

## 🚀 Installation Effectuée

Le package OAuth Google a été installé:
```bash
composer require league/oauth2-google
```

**Note**: Nous utilisons une implémentation personnalisée (pas de bundle HWIOAuth) pour plus de simplicité et de contrôle.

## 🔧 Configuration Google OAuth

### Étape 1: Google Cloud Console

1. **Créer un projet**:
   - Allez sur https://console.cloud.google.com/
   - Cliquez sur "Select a project" > "NEW PROJECT"
   - Nom: "Bibliothèque App"
   - Cliquez sur "CREATE"

2. **Activer Google+ API**:
   - Menu: "APIs & Services" > "Library"
   - Recherchez "Google+ API"
   - Cliquez sur "ENABLE"

3. **Configurer l'écran de consentement OAuth**:
   - Menu: "APIs & Services" > "OAuth consent screen"
   - Sélectionnez "External"
   - Remplissez:
     - App name: **Bibliothèque**
     - User support email: **votre@email.com**
     - Developer contact: **votre@email.com**
   - Scopes: ajoutez `email` et `profile`
   - Test users: ajoutez votre email

4. **Créer les identifiants OAuth 2.0**:
   - Menu: "APIs & Services" > "Credentials"
   - "CREATE CREDENTIALS" > "OAuth client ID"
   - Application type: **Web application**
   - Name: **Bibliothèque Web Client**
   
   **Authorized JavaScript origins:**
   ```
   http://127.0.0.1:8000
   http://localhost:8000
   ```
   
   **Authorized redirect URIs:**
   ```
   http://127.0.0.1:8000/login/google/check
   http://localhost:8000/login/google/check
   ```
   
   - Cliquez sur "CREATE"

5. **Copier les identifiants**:
   - Une popup affiche votre `Client ID` et `Client Secret`
   - **Copiez-les!**

### Étape 2: Configuration dans le Projet

Ouvrez le fichier `.env.local` et remplacez:

```env
GOOGLE_CLIENT_ID=VOTRE_CLIENT_ID_ICI.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=VOTRE_CLIENT_SECRET_ICI
```

**Exemple:**
```env
GOOGLE_CLIENT_ID=123456789-abc123def456.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-aBc123DeF456GhI789JkL
```

### Étape 3: Redémarrer le Serveur

```powershell
symfony server:stop
symfony serve
```

## 📂 Fichiers Créés/Modifiés

### Nouveaux Fichiers:
- `src/Controller/ContactController.php` - Gestion du formulaire de contact
- `src/Controller/GoogleOAuthController.php` - Gestion OAuth Google personnalisée
- `src/Controller/OAuthHelpController.php` - Page d'aide OAuth
- `templates/contact/index.html.twig` - Page de contact
- `templates/emails/contact_support.html.twig` - Email de support
- `templates/security/oauth_config_help.html.twig` - Guide OAuth
- `OAUTH_CONTACT_README.md` - Documentation complète

### Fichiers Modifiés:
- `templates/piedpage.html.twig` - Liens vers page contact
- `templates/security/login.html.twig` - Bouton Google OAuth
- `.env.local` - Variables d'environnement Google

## 🧪 Tester les Fonctionnalités

### Test du Formulaire de Contact:

1. Accédez à: http://127.0.0.1:8000/contact
2. Remplissez le formulaire
3. Cliquez sur "Envoyer le message"
4. Vérifiez l'email reçu dans Mailtrap: https://mailtrap.io/inboxes

### Test de la Connexion Google:

**⚠️ Avant de tester, assurez-vous d'avoir:**
- ✅ Configuré les identifiants dans `.env.local`
- ✅ Redémarré le serveur Symfony
- ✅ Ajouté votre email dans les "Test users" de Google Cloud Console

**Pour tester:**
1. Déconnectez-vous si connecté
2. Allez sur: http://127.0.0.1:8000/login
3. Cliquez sur "Continuer avec Google"
4. Sélectionnez votre compte Google
5. Acceptez les permissions
6. Vous serez redirigé et connecté automatiquement!

## 🔍 Vérification

### Routes disponibles:
```powershell
symfony console debug:router | Select-String "contact|oauth|google"
```

Vous devriez voir:
- `app_contact` - /contact
- `app_contact_send` - /contact/send (POST)
- `hwi_oauth_service_redirect` - /connect/{service}
- `google_login` - /login/check-google
- `app_oauth_config_help` - /oauth/config-help

## 📱 Accès aux Pages

- **Page de contact**: http://127.0.0.1:8000/contact
- **Page de connexion (avec Google)**: http://127.0.0.1:8000/login
- **Guide de configuration OAuth**: http://127.0.0.1:8000/oauth/config-help

## 🐛 Dépannage

### Erreur "redirect_uri_mismatch"
- Vérifiez que l'URI de redirection dans Google Cloud Console est exactement: `http://127.0.0.1:8000/login/google/check`

### Erreur "Access blocked: This app's request is invalid"
- Ajoutez votre email dans les "Test users" de l'écran de consentement OAuth
- Vérifiez que les scopes `email` et `profile` sont activés

### Le bouton Google ne fait rien
```powershell
# Vider le cache
symfony console cache:clear

# Vérifier les variables d'environnement
echo $env:GOOGLE_CLIENT_ID

# Redémarrer le serveur
symfony server:stop
symfony serve
```

### Erreur "Invalid client"
- Vérifiez que GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET sont corrects dans `.env.local`
- Pas d'espaces avant/après les valeurs
- Client ID doit finir par `.apps.googleusercontent.com`

## 📧 Email de Support

L'email de support est configuré pour: `support@bibliotheque.local`

Pour changer:
1. Ouvrez `src/Controller/ContactController.php`
2. Ligne 32, modifiez: `->to('votre-email@domaine.com')`

## 🎨 Design

Les deux fonctionnalités utilisent le même style moderne que le reste de l'application:
- Dégradés de couleurs
- Border-radius 20px
- Ombres et effets hover
- Responsive design
- Icônes Bootstrap Icons

## ✨ Avantages

### Formulaire de Contact:
- ✅ Facile à utiliser
- ✅ Validation des champs
- ✅ Confirmation par email
- ✅ Design moderne
- ✅ Accessible depuis le footer

### Connexion Google:
- ✅ Inscription rapide (1 clic)
- ✅ Pas de mot de passe à mémoriser
- ✅ Sécurité renforcée
- ✅ Récupération automatique des infos (nom, email)
- ✅ Expérience utilisateur améliorée

## 📝 Notes Importantes

1. **Google OAuth en développement**: 
   - Fonctionne uniquement avec les "Test users" configurés
   - Pour mettre en production, il faut vérifier l'application auprès de Google

2. **Emails en développement**:
   - Utilisent Mailtrap (pas d'envoi réel)
   - Pour production: configurer un vrai serveur SMTP dans `.env`

3. **Sécurité**:
   - `.env.local` est déjà dans `.gitignore`
   - Ne commitez JAMAIS vos identifiants Google
   - En production, utilisez des variables d'environnement serveur

## 🎯 Prochaines Étapes (Optionnelles)

- [ ] Ajouter d'autres providers OAuth (Facebook, GitHub, etc.)
- [ ] Système de tickets pour le support
- [ ] Base de connaissances / FAQ
- [ ] Chat en direct avec le support
- [ ] Notifications email pour les réponses du support
