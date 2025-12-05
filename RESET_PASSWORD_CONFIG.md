# 🔐 Configuration de la réinitialisation de mot de passe

## Fonctionnalités

✅ Lien "Mot de passe oublié" sur la page de connexion  
✅ Formulaire de demande de réinitialisation  
✅ Génération de token sécurisé (64 caractères hex)  
✅ Expiration automatique après 1 heure  
✅ Email de réinitialisation avec lien sécurisé  
✅ Validation du nouveau mot de passe (majuscule, minuscule, chiffre, 6+ caractères)  
✅ Protection contre la réutilisation des tokens  
✅ Interface moderne et responsive  

## 📧 Configuration Mailtrap

### Étape 1 : Créer un compte Mailtrap

1. Allez sur [https://mailtrap.io](https://mailtrap.io)
2. Créez un compte gratuit (pas de carte bancaire requise)
3. Confirmez votre email

### Étape 2 : Obtenir les identifiants SMTP

1. Connectez-vous à votre compte Mailtrap
2. Dans le menu de gauche, cliquez sur **"Email Testing"**
3. Sélectionnez votre inbox (ou créez-en une nouvelle)
4. Cliquez sur **"Show Credentials"** ou **"SMTP Settings"**
5. Vous verrez vos identifiants :
   - **Host**: sandbox.smtp.mailtrap.io
   - **Port**: 2525 (ou 465, 587)
   - **Username**: votre username (ex: 1a2b3c4d5e6f7g)
   - **Password**: votre password (ex: 9h8i7j6k5l4m3n)

### Étape 3 : Configurer Symfony

1. Créez un fichier `.env.local` à la racine du projet (s'il n'existe pas déjà)
2. Ajoutez la configuration Mailtrap :

```env
###> symfony/mailer ###
MAILER_DSN=smtp://VOTRE_USERNAME:VOTRE_PASSWORD@sandbox.smtp.mailtrap.io:2525
###< symfony/mailer ###
```

**Exemple concret :**
```env
MAILER_DSN=smtp://1a2b3c4d5e6f7g:9h8i7j6k5l4m3n@sandbox.smtp.mailtrap.io:2525
```

3. Sauvegardez le fichier

> **Note :** Le fichier `.env.local` est déjà dans `.gitignore` et ne sera pas commité (sécurité)

### Étape 4 : Appliquer la migration

Exécutez la migration pour créer la table `reset_password_request` :

```bash
symfony console doctrine:migrations:migrate
```

Ou :

```bash
php bin/console doctrine:migrations:migrate
```

### Étape 5 : Tester

1. Allez sur la page de connexion : `/login`
2. Cliquez sur **"Mot de passe oublié ?"**
3. Entrez une adresse email d'un utilisateur existant
4. Allez sur Mailtrap pour voir l'email reçu
5. Cliquez sur le lien dans l'email
6. Définissez un nouveau mot de passe

## 🔍 Vérification dans Mailtrap

Une fois la configuration effectuée, vous verrez tous les emails envoyés dans votre inbox Mailtrap :

- Sujet de l'email : **"Réinitialisation de votre mot de passe"**
- Expéditeur : noreply@bibliotheque.com
- Design moderne avec bouton d'action
- Lien de réinitialisation inclus
- Durée de validité affichée (1 heure)

## 🚀 Routes disponibles

| Route | Méthode | Description |
|-------|---------|-------------|
| `/reset-password/request` | GET/POST | Formulaire de demande |
| `/reset-password/reset/{token}` | GET/POST | Formulaire de réinitialisation |
| `/login` | GET | Page de connexion (avec lien) |

## 🔒 Sécurité

- **Token unique** : 64 caractères hexadécimaux
- **Expiration** : 1 heure
- **Usage unique** : Le token devient invalide après utilisation
- **Invalidation** : Les anciennes demandes sont automatiquement invalidées
- **Pas d'énumération** : Message identique que l'email existe ou non
- **Validation forte** : Majuscule + minuscule + chiffre + 6 caractères minimum

## 📊 Base de données

Table créée : `reset_password_request`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INT | Identifiant unique |
| user_id | INT | Référence à l'utilisateur |
| token | VARCHAR(100) | Token de réinitialisation |
| requested_at | DATETIME | Date de la demande |
| expires_at | DATETIME | Date d'expiration |
| is_used | TINYINT(1) | Déjà utilisé ? |

## 🎨 Templates créés

- `templates/reset_password/request.html.twig` - Formulaire de demande
- `templates/reset_password/reset.html.twig` - Formulaire de réinitialisation
- `templates/emails/reset_password.html.twig` - Email HTML

## 📝 Commandes utiles

```bash
# Vérifier la configuration mailer
symfony console debug:config symfony/mailer

# Tester l'envoi d'email (optionnel)
symfony console mailer:test votre@email.com

# Voir les migrations
symfony console doctrine:migrations:list

# Appliquer les migrations
symfony console doctrine:migrations:migrate

# Vider le cache
symfony console cache:clear
```

## ⚠️ Troubleshooting

### L'email ne s'envoie pas

1. Vérifiez que `.env.local` existe et contient le bon `MAILER_DSN`
2. Vérifiez les identifiants Mailtrap
3. Videz le cache : `symfony console cache:clear`
4. Vérifiez les logs : `var/log/dev.log`

### Le token est invalide

- Le token expire après 1 heure
- Un token ne peut être utilisé qu'une fois
- Demandez un nouveau lien de réinitialisation

### Erreur de validation du mot de passe

Le mot de passe doit contenir :
- Au moins 6 caractères
- Une majuscule
- Une minuscule
- Un chiffre

## 🎉 Prêt !

Votre système de réinitialisation de mot de passe est maintenant opérationnel ! 🚀
