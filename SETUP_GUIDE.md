# 🔧 Guide de Correction - Register & Login

## 🔴 PROBLÈME IDENTIFIÉ

Votre API d'authentification (register/login) ne fonctionne pas car **la base de données n'est pas initialisée**.

### Causes :
- `schema.sql` crée une base `katsuki_trading`
- Mais `config/database.php` utilise `u223322817_trad` (serveur de production)
- **La table `users` n'existe PAS dans `u223322817_trad`**

---

## ✅ SOLUTIONS

### ÉTAPE 1 : Exécuter le SQL Correct

J'ai créé un nouveau fichier SQL : **`sql/init_u223322817_trad.sql`**

Ce fichier contient :
- Création de la table `users` avec tous les champs nécessaires
- Création de toutes les autres tables (`courses`, `subjects`, `exams`, etc.)
- Données initiales pour tester

**Comment l'exécuter :**

#### Option A : Via phpMyAdmin (Recommandé)
1. Allez sur `https://projet.stockflowrdc.com/phpmyadmin/` (ou votre panel)
2. Sélectionnez la base `u223322817_trad`
3. Onglet "SQL"
4. Copiez-collez le contenu de `sql/init_u223322817_trad.sql`
5. Cliquez "Exécuter"

#### Option B : Via Terminal (SSH)
```bash
mysql -h projet.stockflowrdc.com -u u223322817_trad -p u223322817_trad < sql/init_u223322817_trad.sql
# Entrez le mot de passe : Fratlas@1997
```

#### Option C : Via votre app PHP (une seule fois)
Créez temporairement `public/setup.php` :

```php
<?php
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$sql = file_get_contents(__DIR__ . '/../sql/init_u223322817_trad.sql');

// Exécuter chaque requête
$queries = array_filter(array_map('trim', explode(';', $sql)));

foreach ($queries as $query) {
    if (!empty($query)) {
        try {
            $pdo->exec($query);
            echo "✓ Exécuté: " . substr($query, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "⚠️ Erreur: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Installation terminée!";
?>
```

Puis visitez : `https://projet.stockflowrdc.com/api/setup.php`

---

### ÉTAPE 2 : Vérifier que tout fonctionne

Visitez le **diagnostic** : https://projet.stockflowrdc.com/api/diagnostic.php

Vous devriez voir :
```json
{
  "database": {
    "connected": true,
    "tables": {
      "users": { "exists": true, "rows": 0 }
    }
  }
}
```

---

### ÉTAPE 3 : Tester Register & Login

Utilisez **Postman** ou **curl** :

#### Register
```bash
curl -X POST https://projet.stockflowrdc.com/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@exemple.com",
    "password": "Password123!",
    "name": "Jean Dupont"
  }'
```

**Réponse attendue :**
```json
{
  "token": "eyJhbGc...",
  "user": {
    "id": 1,
    "email": "test@exemple.com",
    "name": "Jean Dupont",
    "role": "student"
  }
}
```

#### Login
```bash
curl -X POST https://projet.stockflowrdc.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@exemple.com",
    "password": "Password123!"
  }'
```

---

## 📋 RÉCAPITULATIF - Fichiers Créés/Modifiés

| Fichier | Status | Description |
|---------|--------|-------------|
| `sql/init_u223322817_trad.sql` | ✅ CRÉÉ | Script SQL pour initialiser la BD |
| `public/diagnostic.php` | ✅ CRÉÉ | Outil de diagnostic |
| `config/database.php` | ✓ OK | Déjà correct |
| `public/index.php` | ✓ OK | Routes API correctes |
| `src/controller/AuthController.php` | ✓ OK | Register/Login implémentés |
| `.htaccess` | ✓ OK | Redirection vers public/index.php |

---

## 🔍 Dépannage

### Le diagnostic dit "Table users n'existe pas"
→ Exécutez le SQL (Étape 1)

### Erreur "Database connection failed"
→ Vérifiez les identifiants dans `config/database.php`:
- Host: `projet.stockflowrdc.com` ✓
- DB: `u223322817_trad` ✓
- User: `u223322817_trad` ✓
- Pass: `Fratlas@1997` ✓

### "Non authentifié" au login
→ Vérifiez que le token JWT est retourné par `/register` ou `/login`

### La page HTML ne se connecte pas
→ Ouvrez la console du navigateur (F12)
→ Allez dans "Network"
→ Essayez de vous inscrire
→ Vérifiez la réponse HTTP du `/register`

---

## 📞 Support

Si vous avez des erreurs :
1. Visitez `https://projet.stockflowrdc.com/api/diagnostic.php`
2. Vérifiez les erreurs affichées
3. Consultez la section "Dépannage" ci-dessus
