# Guide d'utilisation — Katsuki Trading

Plateforme de formation trading en ligne : site vitrine, espace étudiant, espace administrateur et API REST.

**URL de production :** `https://projet.stockflowrdc.com`

---

## Sommaire

1. [Vue d'ensemble](#1-vue-densemble)
2. [Site public (page d'accueil)](#2-site-public-page-daccueil)
3. [Créer un compte et se connecter](#3-créer-un-compte-et-se-connecter)
4. [Espace étudiant](#4-espace-étudiant)
5. [Formations gratuites et payantes](#5-formations-gratuites-et-payantes)
6. [Suivre une formation (modules)](#6-suivre-une-formation-modules)
7. [Examens et progression](#7-examens-et-progression)
8. [Messagerie](#8-messagerie)
9. [Assistant IA](#9-assistant-ia)
10. [Espace administrateur](#10-espace-administrateur)
11. [Gérer les paiements](#11-gérer-les-paiements)
12. [Gérer le contenu des modules](#12-gérer-le-contenu-des-modules)
13. [Créer un compte administrateur](#13-créer-un-compte-administrateur)
14. [Installation et migrations](#14-installation-et-migrations)
15. [URLs utiles](#15-urls-utiles)

---

## 1. Vue d'ensemble

| Rôle | Accès | Description |
|------|--------|-------------|
| **Visiteur** | Page d'accueil | Découvre les formations, peut s'inscrire |
| **Étudiant** | `/student/*` | Suit les cours, passe les examens, messagerie, IA |
| **Admin** | `/admin/*` | Gère étudiants, contenus, paiements, messagerie |

### Formations disponibles par défaut

| Formation | Niveau | Prix | Accès |
|-----------|--------|------|--------|
| Genèse du Trader | Débutant | **Gratuit** | Immédiat après inscription |
| Prop Firm Mastery | Avancé | 299 € | Après validation du paiement |
| Inner Circle Katsuki | Élite | 999 € | Après validation du paiement |

---

## 2. Site public (page d'accueil)

**URL :** `/` ou `/index.html`

La page d'accueil présente :

- **Accueil** — Présentation, chiffres clés, fonctionnalités de la plateforme
- **Formations** — Catalogue complet avec prix et bouton d'inscription
- **Mental** — Psychologie du trading et FAQ
- **Services** — Offres premium (Terminal, Mentorat)
- **Philosophie** — Manifeste et parcours du trader

### Actions possibles sans être connecté

- Parcourir toutes les sections
- Ouvrir la fenêtre **Rejoindre l'Élite** (inscription / connexion)
- Cliquer sur **Espace formation** → redirection vers `/auth/login.php`

### Actions une fois connecté

- **Mon espace** → tableau de bord étudiant ou admin selon le rôle
- **Continuer la formation** → accès direct au cours (gratuit) ou page de paiement (payant)

---

## 3. Créer un compte et se connecter

### Inscription depuis la page d'accueil

1. Cliquer sur **Rejoindre l'Élite** ou **Commencer gratuitement**
2. Choisir l'onglet **Inscription**
3. Renseigner : nom, email, mot de passe
4. Valider → redirection automatique vers les formations

### Inscription / connexion via page dédiée

| Page | URL |
|------|-----|
| Connexion | `/auth/login.php` |
| Inscription | `/auth/register.php` |

Après connexion :

- **Étudiant** → `/student/dashboard.php`
- **Admin** → `/admin/dashboard.php`

### Déconnexion

Cliquer sur **Déconnexion** dans le menu latéral (lien `?logout=1`).

---

## 4. Espace étudiant

**URL de base :** `/student/`

| Page | Fichier | Description |
|------|---------|-------------|
| Tableau de bord | `dashboard.php` | Vue d'ensemble des formations et progression |
| Mes formations | `course.php` | Lecteur de modules (vidéo, audio, PDF, texte) |
| Ma progression | `progress.php` | Détail module par module |
| Examens | `quiz.php` | Passer les quiz de validation |
| Messagerie | `messages.php` | Échanges avec l'administration |
| Assistant IA | `assistant.php` | Coach intelligent contextuel |
| Inscription payante | `enroll.php` | Envoi de preuve de paiement |

### Tableau de bord

Chaque formation affiche :

- Son **niveau** et son **statut** : `Gratuit`, `À payer`, `En attente`, `Accès validé`, `Refusé`
- Une **barre de progression** (% de modules terminés)
- Un bouton adapté :
  - **Commencer / Continuer** — accès autorisé
  - **S'inscrire / Renvoyer preuve** — formation payante non validée
  - **En attente** — preuve envoyée, en cours de vérification

---

## 5. Formations gratuites et payantes

### Formation gratuite (prix = 0 €)

1. Créer un compte étudiant
2. Cliquer sur la formation (ex. *Genèse du Trader*)
3. Accès immédiat au lecteur de cours

### Formation payante

1. Créer un compte
2. Aller sur la formation ou cliquer **Demander l'accès**
3. Vous arrivez sur `/student/enroll.php?courseId=X`
4. Effectuer le paiement (virement, Mobile Money, etc.)
5. **Téléverser une preuve** : capture d'écran ou reçu (JPG, PNG, WEBP, PDF — max 10 Mo)
6. Ajouter une note optionnelle (référence, date, mode de paiement)
7. Cliquer **Envoyer pour validation**

**Statuts possibles :**

| Statut | Signification |
|--------|---------------|
| `pending` | En attente de validation admin |
| `approved` | Paiement accepté — accès débloqué |
| `rejected` | Paiement refusé — possibilité de renvoyer une preuve |

Lors d'une validation ou d'un refus, l'étudiant reçoit une **notification automatique** dans la messagerie.

---

## 6. Suivre une formation (modules)

**URL :** `/student/course.php?courseId=X`

### Accès

- Formation **gratuite** : accès direct
- Formation **payante** : uniquement si le paiement est **approuvé** par l'admin

### Contenu d'un module

Chaque module peut contenir :

| Type | Affichage |
|------|-----------|
| **Vidéo** | Lecteur HTML5 (fichier local) ou iframe YouTube/Vimeo |
| **Audio** | Lecteur audio intégré |
| **PDF** | Aperçu + lien de téléchargement |
| **Texte** | Contenu affiché avec boutons **Écouter le texte** / **Arrêter** (synthèse vocale française du navigateur) |

### Navigation entre modules

- Les modules se débloquent **progressivement** : un module doit être terminé avant d'accéder au suivant
- Cliquer sur un module dans la liste latérale pour l'ouvrir
- Utiliser la zone **Notes personnelles** (sauvegardées localement dans le navigateur)
- Cliquer **Marquer comme terminé** pour enregistrer la progression

---

## 7. Examens et progression

### Progression

- **Ma progression** (`/student/progress.php`) — liste détaillée des modules complétés par formation
- La progression est synchronisée avec le serveur via l'API `/api/progress`

### Examens

**URL :** `/student/quiz.php`

1. Choisir un examen lié à une formation
2. Répondre aux questions à choix multiples
3. Soumettre → score et résultat (réussi / échoué)
4. Score minimum requis défini par examen (ex. 70 %, 75 %, 80 %)

L'admin peut consulter les résultats dans **Examens** et sur le dashboard.

---

## 8. Messagerie

**Étudiant :** `/student/messages.php`  
**Admin :** `/admin/messages.php`

### Côté étudiant

- Un fil de discussion unique avec l'équipe Katsuki
- Envoi de **texte** et **images** (JPG, PNG, WEBP)
- Réception des notifications de paiement (validation / refus)

### Côté admin

- Liste de tous les étudiants avec fil de discussion
- Possibilité de démarrer une conversation avec un étudiant sans fil existant
- Indicateur de messages non lus

---

## 9. Assistant IA

**Étudiant :** `/student/assistant.php`  
**Admin :** `/admin/assistant.php`

L'assistant répond aux questions sur le trading et la formation, en tenant compte du contexte Katsuki et de la progression de l'étudiant.

**Modes disponibles :**

- **Leçon** — Explication pédagogique sur un sujet
- **Questions** — Réponses ciblées

> Nécessite une clé API OpenRouter configurée dans `config/ai.php`.

---

## 10. Espace administrateur

**URL de base :** `/admin/`

| Page | Description |
|------|-------------|
| `dashboard.php` | Statistiques : étudiants, formations, completion, top performers |
| `students.php` | Liste des étudiants, progression et résultats d'examens |
| `courses.php` | Vue des formations |
| `resources.php` | Gestion des ressources / contenus |
| `quizzes.php` | Gestion et suivi des examens |
| `payments.php` | **Validation des preuves de paiement** |
| `modules.php` | **Édition du contenu des modules** (vidéo, audio, PDF, texte) |
| `messages.php` | Messagerie avec les étudiants |
| `assistant.php` | Assistant IA |

---

## 11. Gérer les paiements

**URL :** `/admin/payments.php`

### Workflow

1. Un étudiant envoie une preuve → statut **pending**
2. L'admin consulte la preuve (lien **Voir preuve**)
3. Actions possibles :
   - **Valider** — l'étudiant obtient l'accès à la formation
   - **Refuser** — avec note optionnelle (motif affiché à l'étudiant)
4. Une **notification messagerie** est envoyée automatiquement à l'étudiant

### Filtres

- Tous / En attente / Validés / Refusés

---

## 12. Gérer le contenu des modules

**URL :** `/admin/modules.php`

### Ajouter un module

1. Sélectionner la formation dans le menu déroulant
2. Cliquer **Ajouter un module**

### Modifier un module

1. Cliquer **Modifier** sur le module souhaité
2. Renseigner ou mettre à jour :

| Champ | Format |
|-------|--------|
| Titre | Texte |
| Ordre | Numéro (position dans le parcours) |
| Vidéo | URL YouTube/Vimeo **ou** fichier MP4/WebM |
| Audio | URL **ou** fichier MP3/WAV/OGG |
| PDF | URL **ou** fichier PDF |
| Texte | Contenu de la leçon (affiché + lecture vocale) |

3. Cliquer **Enregistrer**

Les fichiers uploadés sont stockés dans `uploads/modules/` (sous-dossiers `video/`, `audio/`, `pdf/`).

---

## 13. Créer un compte administrateur

Il n'existe pas de compte admin par défaut. Deux méthodes :

### Méthode 1 — Promouvoir un utilisateur existant (phpMyAdmin)

```sql
UPDATE users SET role = 'admin' WHERE email = 'votre@email.com';
```

### Méthode 2 — Créer un admin manuellement

```sql
INSERT INTO users (email, password, name, role, status)
VALUES (
  'admin@katsuki.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrateur',
  'admin',
  'active'
);
```

> Le hash ci-dessus correspond au mot de passe `Admin123!` — **changez-le immédiatement** après la première connexion.

Connexion : `/auth/login.php` → redirection automatique vers `/admin/dashboard.php`.

---

## 14. Installation et migrations

### Première installation de la base

Exécuter une fois : `sql/init_u223322817_trad.sql`  
(via phpMyAdmin ou script de setup)

### Migrations additionnelles

| Script | URL à visiter | Effet |
|--------|---------------|-------|
| Messagerie | `/api/setup-chat.php` | Crée les tables `chat_threads`, `chat_messages` |
| Inscriptions payantes | `/api/setup-enrollment.php` | Crée `course_enrollments` + colonnes `audio_url`, `pdf_url` sur `modules` |

### Diagnostic

**URL :** `/api/diagnostic.php`  
Vérifie la connexion base de données et l'état des tables.

---

## 15. URLs utiles

### Public

| URL | Description |
|-----|-------------|
| `/` | Page d'accueil |
| `/auth/login.php` | Connexion |
| `/auth/register.php` | Inscription |

### Étudiant

| URL | Description |
|-----|-------------|
| `/student/dashboard.php` | Tableau de bord |
| `/student/course.php?courseId=1` | Lecteur de formation |
| `/student/enroll.php?courseId=2` | Envoi preuve de paiement |
| `/student/quiz.php` | Examens |
| `/student/messages.php` | Messagerie |
| `/student/assistant.php` | Coach IA |

### Admin

| URL | Description |
|-----|-------------|
| `/admin/dashboard.php` | Dashboard |
| `/admin/payments.php` | Validation paiements |
| `/admin/modules.php` | Contenu des modules |
| `/admin/messages.php` | Messagerie |
| `/admin/students.php` | Gestion étudiants |

### API (JSON)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/register` | Inscription |
| POST | `/api/login` | Connexion |
| GET | `/api/user` | Profil connecté |
| GET | `/api/courses` | Liste des formations |
| GET | `/api/courses/{id}/modules` | Modules d'une formation |
| POST | `/api/progress` | Marquer un module terminé |
| GET | `/api/messages` | Boîte de messagerie |
| POST | `/api/messages/send` | Envoyer un message |
| POST | `/api/ai/lesson` | Assistant IA — leçon |

> L'authentification API utilise le header `Authorization: Bearer <token>` ou le cookie `katsuki_token`.

---

## Résumé du parcours type

```
Visiteur
  → S'inscrit sur la page d'accueil
  → Accède à la formation gratuite immédiatement
  → OU souscrit à une formation payante
       → Envoie preuve de paiement
       → Admin valide
       → Notification messagerie
       → Accès au cours débloqué
  → Suit les modules (vidéo, audio, PDF, TTS)
  → Passe les examens
  → Utilise le coach IA et la messagerie
```

---

*Katsuki Trading — Le trading comporte des risques importants. Ce guide décrit l'utilisation de la plateforme, pas des conseils financiers.*
