# Vérification authentification et administration

## Corrections
- URL API relative `/api` (ne dépend plus de `localhost:3000`).
- Correction du nom réel du fichier `jwthandler.php` sur hébergement Linux.
- Prise en charge de l'en-tête Authorization via serveur ou Apache.
- Inscription / connexion avec validation, rôle étudiant par défaut et gestion d'email déjà utilisé.
- Tableau de bord administrateur protégé côté serveur par la colonne `users.role`.
- Affichage des erreurs d'authentification dans le formulaire plutôt qu'en alertes bloquantes.

## Pré-requis pour déployer
- PHP 8+, extension PDO MySQL, Apache mod_rewrite et base MySQL initialisée avec `sql/schema.sql`.
- Variables serveur `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, et `JWT_SECRET` (au moins 32 caractères).
- Pointer le domaine vers le projet contenant `.htaccess`, `index.html`, `admin.html` et `public/index.php`.
- Vérifier les autorisations MySQL (SELECT, INSERT et UPDATE sur les tables nécessaires).

## Créer un administrateur sur le serveur
Depuis le shell du serveur, hors de l'espace public :

```bash
export ADMIN_EMAIL="admin@votre-domaine.tld"
export ADMIN_NAME="Administrateur Katsuki"
read -rs -p "Mot de passe administrateur (12+ caractères) : " ADMIN_PASSWORD; echo
export ADMIN_PASSWORD
php scripts/create_admin.php
unset ADMIN_PASSWORD
```

Le mot de passe est traité avec `password_hash` et n'est pas écrit dans GitHub. Si l'adresse existe comme étudiant, le script refuse de la promouvoir automatiquement. Si elle existe déjà comme administrateur, il renouvelle le mot de passe. Utiliser un nouveau compte dédié pour l'administration.

## Recette obligatoire
1. `POST /api/register` avec un email nouveau puis vérifier le nouvel utilisateur dans MySQL.
2. `POST /api/login` puis `GET /api/user` avec le JWT.
3. Tester un mauvais mot de passe (401) et une seconde inscription au même email (409).
4. Tester `GET /api/admin/dashboard` : utilisateur standard (403), administrateur (200).
5. Tester les pages sur ordinateur et mobile, et vérifier les journaux PHP/Apache en cas de 500/503.

**Limite** : l'accès à la base MySQL de production n'a pas été fourni. Il est impossible de confirmer la connexion réelle, d'insérer un administrateur actif ou de valider les endpoints en production depuis la seule lecture du dépôt GitHub.
