# Configuration Stripe pour le projet Bibliothèque

## 📋 Prérequis

1. Compte Stripe (gratuit) : https://dashboard.stripe.com/register
2. PHP 8.2+ avec Composer

## 🔧 Installation

Le package Stripe PHP est déjà installé :
```bash
composer require stripe/stripe-php
```

## 🔑 Configuration des clés API

### 1. Récupérer vos clés Stripe

1. Connectez-vous à https://dashboard.stripe.com/
2. Allez dans **Développeurs** > **Clés API**
3. Vous verrez deux types de clés :
   - **Clé publiable** (pk_test_...) - côté client
   - **Clé secrète** (sk_test_...) - côté serveur

⚠️ **Important** : Ne partagez JAMAIS votre clé secrète publiquement !

### 2. Configurer les variables d'environnement

Modifiez le fichier `.env.local` :

```env
###> stripe ###
STRIPE_PUBLIC_KEY=pk_test_votre_cle_publique_ici
STRIPE_SECRET_KEY=sk_test_votre_cle_secrete_ici
STRIPE_WEBHOOK_SECRET=whsec_votre_secret_webhook_ici
###< stripe ###

APP_URL=http://127.0.0.1:8000
```

## 💳 Fonctionnalités Stripe implémentées

### 1. Paiement des commandes
- Route : `/commande/checkout` (POST)
- Création de session Stripe Checkout
- Redirection vers page de paiement Stripe sécurisée
- Confirmation automatique de la commande après paiement
- Vidage automatique du panier

### 2. Abonnement Premium
- Route : `/subscription/create-checkout-session` (POST)
- Deux types d'abonnement : Mensuel (9.99€) et Annuel (99.99€)
- Activation automatique du statut premium
- Calcul de la date d'expiration

## 🧪 Tests avec cartes de test Stripe

Utilisez ces numéros de carte pour tester :

### Cartes qui fonctionnent
- **4242 4242 4242 4242** - Paiement réussi
- **4000 0025 0000 3155** - Authentification 3D Secure requise

### Cartes qui échouent
- **4000 0000 0000 9995** - Paiement refusé
- **4000 0000 0000 9987** - Fonds insuffisants

**Pour tous les tests** :
- Date d'expiration : N'importe quelle date future (ex: 12/25)
- CVV : N'importe quel 3 chiffres (ex: 123)
- Code postal : N'importe quel code

## 📁 Fichiers créés/modifiés

### Nouveaux fichiers
- `src/Service/StripeService.php` - Service principal Stripe
- `src/Controller/StripeCommandeController.php` - Paiement des commandes
- `src/Controller/StripePremiumController.php` - Abonnements premium
- `config/services/stripe.yaml` - Configuration du service

### Fichiers modifiés
- `src/Controller/SubscriptionController.php` - Ajout clé publique Stripe
- `templates/subscription/index.html.twig` - Intégration Stripe.js
- `.env` - Ajout APP_URL
- `.env.local` - Variables Stripe

## 🚀 Utilisation

### Pour les commandes :

1. Ajoutez des livres au panier
2. Allez sur `/panier`
3. Cliquez sur "Commander"
4. Remplissez les informations de livraison
5. Cliquez sur "Payer avec Stripe"
6. Complétez le paiement sur Stripe Checkout
7. Redirection automatique après paiement

### Pour l'abonnement Premium :

1. Allez sur `/premium` ou `/subscription`
2. Choisissez "Mensuel" ou "Annuel"
3. Cliquez sur "S'abonner"
4. Complétez le paiement sur Stripe Checkout
5. Statut premium activé automatiquement

## 🔄 Webhooks (optionnel mais recommandé)

Pour production, configurez des webhooks Stripe :

1. Allez sur https://dashboard.stripe.com/webhooks
2. Ajoutez un endpoint : `https://votre-domaine.com/stripe/webhook`
3. Sélectionnez les événements :
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
4. Copiez le secret de signature
5. Ajoutez-le dans `.env.local` : `STRIPE_WEBHOOK_SECRET=whsec_...`

## 📝 URLs importantes

- **Dashboard Stripe** : https://dashboard.stripe.com/
- **Documentation** : https://stripe.com/docs
- **Cartes de test** : https://stripe.com/docs/testing
- **Logs** : https://dashboard.stripe.com/test/logs

## ⚠️ En production

Avant de passer en production :

1. Remplacez les clés test (pk_test_ et sk_test_) par les clés live (pk_live_ et sk_live_)
2. Activez HTTPS obligatoire
3. Configurez les webhooks
4. Testez minutieusement tous les scénarios
5. Activez les notifications d'échec de paiement
6. Configurez la gestion des litiges

## 🆘 Dépannage

### Erreur "Invalid API Key"
- Vérifiez que les clés sont correctes dans `.env.local`
- Videz le cache : `symfony console cache:clear`

### Redirection Stripe ne fonctionne pas
- Vérifiez que `APP_URL` est correct dans `.env`
- Vérifiez que le serveur Symfony tourne

### Paiement réussi mais statut non mis à jour
- Vérifiez les logs : `var/log/dev.log`
- Vérifiez que l'ID de session est correct
- Testez avec une carte de test valide

## 📞 Support

- Documentation Stripe : https://stripe.com/docs
- Support Stripe : https://support.stripe.com/
- Forum Stripe : https://github.com/stripe

---

**Note** : Ce guide utilise Stripe Checkout (hosted payment page). C'est la solution la plus simple et sécurisée. Pour une intégration personnalisée, consultez Stripe Elements.
