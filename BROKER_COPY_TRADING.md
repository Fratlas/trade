# Copy Trading Katsuki — état et prochaine intégration

## État du module

Le module fourni dans cette version est un système **de simulation**. Il inclut les profils maîtres à valider, les abonnements suiveurs, les paramètres d’allocation et de perte, le consentement, les signaux ouverts/modifiés/fermés et un historique. Il n’envoie pas d’ordre à un broker.

Installation : configurer `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` et `JWT_SECRET` dans les variables d’environnement de l’hébergement (`JWT_SECRET` doit faire au moins 32 caractères); `.env.example` ne contient que les noms de variables et n’est pas chargé automatiquement. Exécuter ensuite `sql/copy_trading.sql` une fois après `sql/schema.sql`, puis ouvrir `copy-trading.html`.

Endpoints authentifiés :

- `GET /api/copy/traders` — stratégies maîtres actives.
- `GET /api/copy/profile` et `POST /api/copy/profile` — consulter ou demander un profil maître (validation admin requise).
- `GET /api/copy/subscriptions` et `POST /api/copy/subscriptions` — consulter ou créer un abonnement, créé en pause.
- `PUT /api/copy/subscriptions/{id}` — activer ou suspendre son propre abonnement.
- `GET /api/copy/signals` et `POST /api/copy/signals` — consulter les signaux auxquels l’utilisateur s’est abonné ou en publier comme maître actif.
- `PUT /api/copy/admin/traders/{id}` — validation/suspension/refus d’un profil, réservé aux administrateurs.

## Weltrade

Weltrade propose un service natif avec un CopyWallet et des fournisseurs de stratégie. Un utilisateur peut y choisir son fournisseur, son allocation, ses limites et le traitement des positions déjà ouvertes. Katsuki ne dispose pas d’un connecteur Weltrade. Avant d’en développer un, obtenir l’autorisation partenaire écrite, les spécifications API et un environnement démo. Ne pas demander aux clients leurs mots de passe Weltrade/MT5.

## Deriv

Pour une plateforme web, prévoir une application Deriv OAuth, des scopes limités, des URL HTTPS et des jetons gérés exclusivement côté serveur. Le développeur doit vérifier les exigences d’accès courantes dans la documentation Deriv.

L’API Deriv documente un achat groupé bêta du même contrat pour plusieurs comptes; les réponses sont individualisées. Un adaptateur complet doit encore gérer événements de clôture et modification, risques, idempotence, échecs partiels, reprise, rapprochement des positions et révocation. Ne pas activer de comptes réels avant validation en démo et revue des conditions broker.

## Avant toute exécution réelle

- Implémenter les adaptateurs broker derrière une interface commune et exécuter les ordres uniquement côté serveur.
- Chiffrer les jetons et prévoir leur expiration, rotation et révocation.
- Ajouter une file de traitement, des clés d’idempotence, des limites quotidiennes/de perte, un coupe-circuit et des journaux d’audit.
- Traiter séparément « arrêter les nouveaux ordres » et « fermer les positions existantes ».
- Tester les échecs, la latence, les doublons, les déconnexions et la réconciliation en environnement démo.
- Faire valider les obligations réglementaires, le consentement et la divulgation des risques selon les juridictions concernées.

Guide utilisateur et technique : [broker-guides.html](broker-guides.html).
