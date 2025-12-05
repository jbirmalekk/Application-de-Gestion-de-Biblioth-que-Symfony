# Script de Configuration Google OAuth pour Bibliothèque
# ========================================================

Write-Host "`n==================================================" -ForegroundColor Cyan
Write-Host "   Configuration Google OAuth - Bibliothèque" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

Write-Host "`nCe script va vous aider à configurer la connexion Google.`n" -ForegroundColor Yellow

# Étape 1: Ouvrir Google Cloud Console
Write-Host "[Étape 1/5] Ouverture de Google Cloud Console..." -ForegroundColor Green
Write-Host "  -> Créez un nouveau projet ou sélectionnez-en un existant" -ForegroundColor Gray
Start-Sleep -Seconds 2
Start-Process "https://console.cloud.google.com/projectcreate"

Write-Host "`nAppuyez sur Entrée après avoir créé votre projet..." -ForegroundColor Yellow
$null = Read-Host

# Étape 2: Configurer l'écran de consentement
Write-Host "`n[Étape 2/5] Configuration de l'écran de consentement OAuth..." -ForegroundColor Green
Write-Host "  -> Sélectionnez 'External' puis remplissez les informations:" -ForegroundColor Gray
Write-Host "     - App name: Bibliothèque" -ForegroundColor Gray
Write-Host "     - User support email: votre email" -ForegroundColor Gray
Write-Host "     - Scopes: email, profile" -ForegroundColor Gray
Write-Host "     - Test users: ajoutez malekjbir12@gmail.com" -ForegroundColor Gray
Start-Sleep -Seconds 2
Start-Process "https://console.cloud.google.com/apis/credentials/consent"

Write-Host "`nAppuyez sur Entrée après avoir configuré l'écran de consentement..." -ForegroundColor Yellow
$null = Read-Host

# Étape 3: Créer les identifiants OAuth
Write-Host "`n[Étape 3/5] Création des identifiants OAuth 2.0..." -ForegroundColor Green
Write-Host "  -> Cliquez sur 'CREATE CREDENTIALS' > 'OAuth client ID'" -ForegroundColor Gray
Write-Host "  -> Application type: Web application" -ForegroundColor Gray
Write-Host "  -> Authorized redirect URIs: http://127.0.0.1:8000/login/check-google" -ForegroundColor Gray
Start-Sleep -Seconds 2
Start-Process "https://console.cloud.google.com/apis/credentials"

Write-Host "`nAppuyez sur Entrée après avoir créé les identifiants..." -ForegroundColor Yellow
$null = Read-Host

# Étape 4: Demander les identifiants
Write-Host "`n[Étape 4/5] Configuration des identifiants dans .env.local" -ForegroundColor Green
Write-Host "`nCopiez vos identifiants depuis Google Cloud Console:" -ForegroundColor Yellow

Write-Host "`nEntrez votre Client ID (se termine par .apps.googleusercontent.com):" -ForegroundColor Cyan
$clientId = Read-Host

Write-Host "`nEntrez votre Client Secret (format: GOCSPX-...):" -ForegroundColor Cyan
$clientSecret = Read-Host

# Vérification basique
if ([string]::IsNullOrWhiteSpace($clientId) -or [string]::IsNullOrWhiteSpace($clientSecret)) {
    Write-Host "`n❌ ERREUR: Les identifiants ne peuvent pas être vides!" -ForegroundColor Red
    Write-Host "Relancez le script et fournissez vos vrais identifiants.`n" -ForegroundColor Red
    exit 1
}

if (-not $clientId.Contains(".apps.googleusercontent.com")) {
    Write-Host "`n⚠️  ATTENTION: Le Client ID ne semble pas valide (doit contenir .apps.googleusercontent.com)" -ForegroundColor Yellow
    Write-Host "Voulez-vous continuer quand même? (O/N)" -ForegroundColor Yellow
    $response = Read-Host
    if ($response -ne "O" -and $response -ne "o") {
        Write-Host "Configuration annulée.`n" -ForegroundColor Red
        exit 1
    }
}

# Mise à jour du fichier .env.local
Write-Host "`nMise à jour du fichier .env.local..." -ForegroundColor Green

$envPath = ".env.local"
$envContent = Get-Content $envPath -Raw

# Remplacer les valeurs
$envContent = $envContent -replace "GOOGLE_CLIENT_ID=.*", "GOOGLE_CLIENT_ID=$clientId"
$envContent = $envContent -replace "GOOGLE_CLIENT_SECRET=.*", "GOOGLE_CLIENT_SECRET=$clientSecret"

Set-Content -Path $envPath -Value $envContent

Write-Host "✅ Fichier .env.local mis à jour avec succès!" -ForegroundColor Green

# Étape 5: Redémarrer le serveur
Write-Host "`n[Étape 5/5] Redémarrage du serveur Symfony..." -ForegroundColor Green

# Arrêter le serveur s'il tourne
Write-Host "Arrêt du serveur..." -ForegroundColor Gray
symfony server:stop 2>$null

Start-Sleep -Seconds 2

# Vider le cache
Write-Host "Nettoyage du cache..." -ForegroundColor Gray
symfony console cache:clear 2>$null

Start-Sleep -Seconds 1

# Redémarrer le serveur
Write-Host "Démarrage du serveur..." -ForegroundColor Gray
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; symfony serve"

Start-Sleep -Seconds 3

Write-Host "`n==================================================" -ForegroundColor Cyan
Write-Host "   ✅ Configuration terminée avec succès!" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Cyan

Write-Host "`nVous pouvez maintenant tester la connexion Google:" -ForegroundColor Yellow
Write-Host "  1. Ouvrez: http://127.0.0.1:8000/login" -ForegroundColor Cyan
Write-Host "  2. Cliquez sur 'Continuer avec Google'" -ForegroundColor Cyan
Write-Host "  3. Connectez-vous avec: malekjbir12@gmail.com" -ForegroundColor Cyan

Write-Host "`nPage d'aide disponible: http://127.0.0.1:8000/oauth/config-help`n" -ForegroundColor Gray

# Ouvrir automatiquement la page de connexion
Write-Host "Ouverture de la page de connexion dans 3 secondes..." -ForegroundColor Yellow
Start-Sleep -Seconds 3
Start-Process "http://127.0.0.1:8000/login"

Write-Host "`n✨ Bonne chance avec la connexion Google! ✨`n" -ForegroundColor Magenta
