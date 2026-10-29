# Katsuki Trading — Copy Trading (simulation)

Cette branche ajoute une première version **sans ordres réels** du copy trading. Aucun transfert de fonds ni connexion broker n'est implémenté.

## Installation

1. Exécuter `sql/schema.sql` pour une nouvelle installation, puis `sql/copy_trading.sql` (une seule fois). Sur une base existante, exécuter uniquement `sql/copy_trading.sql`.
2. Configurer les variables d'environnement côté serveur : `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `JWT_SECRET` (32 caractères aléatoires minimum), et `CORS_ALLOWED_ORIGIN` si nécessaire.
3. Héberger sur PHP 8.0+ avec PDO MySQL et mod_rewrite Apache. Ne pas servir `config/`, `confing/`, `src/` ni `sql/` directement au public ; préférer `public/` comme DocumentRoot et configurer les redirections de routes vers `public/index.php`. Le fichier `.htaccess` racine existant peut être utilisé uniquement si l'hébergement refuse ces dossiers sensibles.
4. Créer un utilisateur dans l'application puis lui associer un profil maître dans `copy_traders` via une intervention administrateur validée. Aucun endpoint public ne permet de s'auto-proclamer maître.
5. Ouvrir `copy-trading.html` depuis la navigation du site. La page utilise le JWT de session Katsuki (`katsuki_token`).

## Endpoints

- `GET /api/copy/traders` : traders maîtres et nombre de suiveurs.
- `GET /api/copy/subscriptions` : abonnements de l'utilisateur connecté.
- `POST /api/copy/subscriptions` : `{"trader_id":1,"allocation_percent":10,"max_loss_percent":5,"risk_consent":true}`. Crée un abonnement **suspendu**.
- `PATCH /api/copy/subscriptions/{id}` : `{"status":"active"}` ou `{"status":"paused"}` (simulation seulement).
- `GET /api/copy/events` : événements simulés visibles par l'utilisateur abonné.

Toutes les routes nécessitent une authentification JWT. Les requêtes à ID d'abonnement sont limitées au propriétaire. Les événements ne déclenchent aucun ordre broker.

## Avant production

- Créer un service d'exécution asynchrone par broker, gestion des erreurs, idempotence et synchronisation des positions.
- Ne pas stocker des identifiants de courtier non chiffrés ; établir les autorisations broker sur un backend dédié.
- Implémenter la taille de position à partir de la valeur nette du compte, de la distance de stop-loss, des limites broker, du slippage et des contraintes de marge.
- Ajouter limites quotidiennes/globales, coupe-circuit, clôture de sécurité, anti-duplication des ordres, réconciliation positions, tests de charge et surveillance.
- Faire valider les obligations réglementaires, KYC/AML, consentement, politique de risque, commissions et publicité selon les juridictions concernées.
- Vérifier et supprimer l'ancien fichier `confing/database.php` qui contient encore une ancienne configuration ; ne pas exposer ce dossier sur le web.
- Exécuter tests manuels et automatisés sur un serveur de staging avant tout déploiement. Les changements de cette branche n'ont pas été testés avec un broker réel.
