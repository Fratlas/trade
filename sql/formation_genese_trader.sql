-- =============================================================================
-- FORMATION "GENÈSE DU TRADER" — Contenu pédagogique complet
-- Contextualisé RDC | Chapitre 1 : Lexique | Chapitre 2 : Marchés + Structure
-- À exécuter sur la base u223322817_trad
-- ATTENTION : Supprime et recrée tous les modules du cours 1 (Genèse du Trader)
-- =============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- Supprimer les anciens modules du cours 1 (et leur progression)
DELETE FROM user_progress WHERE module_id IN (SELECT id FROM modules WHERE course_id = 1);
DELETE FROM modules WHERE course_id = 1;

-- Mettre à jour le cours 1 : 30 modules, 10 semaines
UPDATE courses SET
  modules_count = 30,
  duration      = '10 semaines',
  full_desc     = 'Le programme fondamental qui vous fait passer de zéro à trader conscient. Deux chapitres denses : le Lexique complet du trading (contexte RDC) et tous les concepts de marché — structure, bougies, time frames et money management.'
WHERE id = 1;


-- =============================================================================
-- CHAPITRE 1 : LE LEXIQUE DU TRADING
-- =============================================================================

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH1 — C''est quoi la Bourse ?', 1,
'## C''est quoi la Bourse ?

La **Bourse** est un marché organisé où des acheteurs et des vendeurs échangent des titres financiers (actions, obligations, devises, matières premières…) à des prix déterminés par l''offre et la demande.

### Illustration concrète — contexte RDC
Imagine le **Grand Marché de Kinshasa**. Chaque matin, des commerçants viennent vendre du poisson, du manioc, des pagnes. Les prix montent si tout le monde veut acheter et descendent si personne n''achète. La Bourse fonctionne exactement pareil, mais au lieu de poisson on échange des parts d''entreprises ou des devises, et tout se passe en quelques millisecondes sur des serveurs informatiques.

En RDC, il n''existe pas encore de bourse nationale opérationnelle (la BRVM est en Côte d''Ivoire pour l''Afrique de l''Ouest). Les Congolais accèdent aux marchés mondiaux via internet et des brokers en ligne.

### À quoi sert une Bourse ?
- **Financer les entreprises** : une entreprise vend des parts (actions) pour lever des fonds et se développer.
- **Investir son épargne** : un particulier achète ces parts et espère les revendre plus cher ou toucher des dividendes.
- **Fixer les prix** : la confrontation offre/demande révèle le "juste prix" d''un actif à chaque instant.
- **Transférer le risque** : les traders spécialisés absorbent le risque que les investisseurs ne veulent pas porter.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH1 — C''est quoi le Trading ? (contexte RDC)', 2,
'## C''est quoi le Trading ?

Le **trading** c''est l''acte d''acheter et revendre rapidement un actif financier pour réaliser un profit sur la différence de prix, sans nécessairement garder cet actif longtemps.

### En RDC concrètement
Tu connais le **cambiste** au coin de la rue à Gombe qui achète des dollars à 2 800 FC et les revend à 2 850 FC ? C''est du trading manuel de devises. Le trader en ligne fait la même chose, mais sur les marchés mondiaux, depuis son téléphone ou son ordinateur, avec des sommes parfois minimes (10$) et un effet de levier.

### Différence avec l''investissement
| Trading | Investissement |
|---|---|
| Durée : secondes à quelques jours | Durée : mois à années |
| Profit sur les mouvements de prix | Profit sur la croissance à long terme |
| Risque élevé, rapide | Risque dilué dans le temps |

Le trading exige de la discipline, un plan et une gestion du risque stricte. Ce n''est **pas** un moyen de s''enrichir rapidement.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH1 — Qui est un Trader ? (contexte RDC)', 3,
'## Qui est un Trader ?

Un **trader** est une personne qui achète et vend des actifs financiers dans le but de réaliser un profit.

### Les profils en RDC
- **Trader particulier (retail)** : étudiant de Lubumbashi qui trade depuis son téléphone avec 50$. C''est le profil le plus courant ici.
- **Trader propriétaire** : utilise le capital d''une Prop Firm après avoir réussi un challenge (FTMO, MyForexFunds…).
- **Trader institutionnel** : travaille pour une banque ou un fonds — rarissime en RDC aujourd''hui.

### Ce qu''un vrai trader fait chaque jour
1. Analyse le marché avant l''ouverture des sessions.
2. Identifie des zones d''entrée selon son plan.
3. Pose ses ordres avec stop loss et take profit définis.
4. Note chaque trade dans son journal.
5. N''ouvre **jamais** un trade sans règle préalable.

> Un trader n''est **pas** un parieur. Un parieur mise sans règle. Un trader exécute un plan avec un avantage statistique.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH1 — C''est quoi un Broker ?', 4,
'## C''est quoi un Broker ?

Un **broker** (courtier) est l''intermédiaire entre toi et le marché financier. Sans broker, tu ne peux pas accéder aux marchés mondiaux.

Il te fournit :
- Une **plateforme de trading** (MetaTrader 5, cTrader…)
- Un **accès aux prix** en temps réel
- L''**exécution de tes ordres** sur le marché
- Un **effet de levier** (trader plus que ton dépôt réel)

Il se rémunère via le **spread** (différence entre prix d''achat et prix de vente) et/ou des commissions.

### Broker Direct (ECN/STP) vs Broker Indirect (Market Maker)

| | Broker Direct (ECN/STP) | Broker Indirect (Market Maker) |
|---|---|---|
| Tes ordres | Envoyés au marché réel | Traités en interne par le broker |
| Spread | Variable, souvent bas | Fixe, souvent plus élevé |
| Conflit d''intérêt | Aucun | Possible (le broker est ta contrepartie) |
| Avantage | Exécution transparente | Spread garanti, compte démo facile |
| Inconvénient | Minimum de dépôt parfois plus élevé | Peut "trader contre toi" |

**WellTrade** est un broker Market Maker qui offre des comptes accessibles dès de petits montants — adapté pour débuter en RDC.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH1 — Création compte WellTrade + MetaTrader 5', 5,
'## Ouvrir un compte chez WellTrade et se connecter sur MetaTrader 5

### Étape 1 — Inscription chez WellTrade
1. Rendez-vous sur **https://welltrade.com** (lien d''inscription partenaire Katsuki disponible dans la section Ressources).
2. Clique sur **"Ouvrir un compte"**.
3. Remplis : nom complet, email, pays (République Démocratique du Congo), numéro de téléphone.
4. Choisis le type de compte : **Démo** pour commencer, **Réel** quand tu es prêt.
5. Vérifie ton email et active ton compte.
6. Upload une pièce d''identité (carte d''électeur, passeport) pour la vérification KYC.

### Étape 2 — Qu''est-ce que MetaTrader 5 ?
MetaTrader 5 (MT5) est le **logiciel de trading** sur lequel tu vas lire les graphiques et placer tes ordres. Le broker (WellTrade) est la banque ; MT5 est l''application bancaire.

| WellTrade (Broker) | MetaTrader 5 |
|---|---|
| Détient ton argent | Affiche les graphiques |
| Exécute tes ordres | Te laisse placer les ordres |
| Surveille tes règles | Calcule ton profit/perte |

### Étape 3 — Connexion MT5
1. Télécharge MT5 sur **metatrader5.com** (PC, Android ou iOS).
2. Dans MT5 : **Fichier → Ouvrir un compte**.
3. Cherche **"WellTrade"** dans la liste des brokers.
4. Entre le **numéro de compte** et le **mot de passe** reçus par email.
5. Sélectionne le serveur indiqué par WellTrade (ex: WellTrade-Demo ou WellTrade-Live).

### Premier jour — checklist
- [ ] Compte démo ouvert et connecté sur MT5
- [ ] Tu vois les prix EUR/USD qui bougent en temps réel
- [ ] Tu as placé un premier ordre de test (sans argent réel) sur le compte démo
- [ ] Tu as fermé cet ordre et vu ton profit/perte fictif');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH1 — C''est quoi le Spread ?', 6,
'## C''est quoi le Spread ?

Le **spread** est la différence entre le prix d''achat (**Ask**) et le prix de vente (**Bid**) d''un actif. C''est la commission implicite du broker.

### Exemple concret RDC
Tu veux acheter des dollars à Kinshasa :
- Le cambiste achète ton franc congolais à **2 800 FC/USD** (Bid)
- Il te vend un dollar à **2 850 FC/USD** (Ask)
- Le spread = **50 FC** → c''est son profit

Sur MT5, tu vois deux prix affichés : par exemple EUR/USD à **1.0850 / 1.0852**.
- Bid = 1.0850 (tu vends à ce prix)
- Ask = 1.0852 (tu achètes à ce prix)
- Spread = **0.0002 = 2 pips**

### Pourquoi c''est important ?
Dès que tu ouvres un trade, tu es déjà en **légère perte** du montant du spread. Pour être en profit, le prix doit d''abord couvrir le spread.

> Avec un compte de **10$** et un spread de 2 pips sur EUR/USD, tu commences chaque trade avec -0.20$ si tu utilises 0.01 lot. Choisis toujours un broker à faible spread pour les petits comptes.');

-- =============================================================================
-- CHAPITRE 2 — DIFFÉRENTS TYPES DE MARCHÉ
-- =============================================================================

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Le Marché Forex : devises et paires', 7,
'## Le Marché Forex

Le **Forex** (Foreign Exchange) est le marché mondial d''échange de devises. C''est le plus grand marché financier au monde : **7 500 milliards de dollars échangés chaque jour** — soit 50 fois le PIB annuel de la RDC.

Il est ouvert 24h/24, 5 jours sur 7 (lundi matin Sydney → vendredi soir New York).

### C''est quoi une devise ?
Une **devise** est la monnaie officielle d''un pays ou d''une zone économique.
- USD = Dollar américain
- EUR = Euro
- GBP = Livre sterling
- JPY = Yen japonais
- CDF = Franc Congolais (non tradé sur le Forex international)

### C''est quoi une paire de devises ?
On trade toujours **deux devises ensemble**. La paire EUR/USD signifie : combien de dollars faut-il pour acheter 1 euro ?
- Si EUR/USD = 1.0850 → 1 euro vaut 1.0850 dollars.

### Paires Majeures (7 paires — les plus liquides)
EUR/USD · USD/JPY · GBP/USD · USD/CHF · AUD/USD · USD/CAD · NZD/USD

### Paires Mineures (crosses — sans USD)
EUR/GBP · EUR/JPY · GBP/JPY · AUD/JPY · EUR/AUD · GBP/AUD · etc.

> Les paires majeures ont les spreads les plus faibles. Recommandées pour débuter en RDC avec un petit capital.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Indices réels vs Indices synthétiques (Deriv)', 8,
'## Le Marché des Indices

Un **indice boursier** est un panier d''actions qui représente la performance globale d''un marché ou d''un secteur.

### Indices réels (exemples)
- **S&P 500** (US500) : les 500 plus grandes entreprises américaines (Apple, Google, Tesla…)
- **DAX 40** : les 40 plus grandes entreprises allemandes
- **CAC 40** : les 40 premières françaises
- **FTSE 100** : les 100 premières britanniques

Ces indices sont liés à l''économie réelle. Ils montent quand les entreprises vont bien et descendent en cas de crise.

### Indices synthétiques (Deriv — Boom, Crash, Volatility)
Les indices synthétiques sont des instruments **créés artificiellement** par la plateforme Deriv. Ils ne représentent aucune entreprise réelle. Leur mouvement est généré par un algorithme.

| Indice réel | Indice synthétique |
|---|---|
| Lié à l''économie | Algorithme uniquement |
| Influencé par les news | Aucune influence externe |
| Disponible heures de marché | Disponible 24h/24, 7j/7 |
| Ex : S&P 500, DAX | Ex : Volatility 75, Boom 500, Crash 1000 |

### Pourquoi les synthétiques sont populaires en RDC ?
Accessibles 24h/24 même le week-end, dépôt minimum faible sur Deriv (5$). Mais attention : ils sont très volatils et nombreux débutants y perdent tout rapidement.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Marché des Actions & Crypto', 9,
'## Marché des Actions

Une **action** est une part de propriété d''une entreprise. Acheter une action Apple, c''est devenir (très) petit actionnaire d''Apple.

Le trader d''actions spécule sur la hausse ou la baisse du prix de ces parts.

---

## Marché des Cryptomonnaies

### C''est quoi une cryptomonnaie ?
Une **cryptomonnaie** est une monnaie numérique décentralisée — aucune banque centrale ne la contrôle. Elle fonctionne grâce à la **technologie blockchain**.

### C''est quoi le Bitcoin ?
Le **Bitcoin (BTC)** est la première cryptomonnaie, créée en 2009 par un mystérieux personnage sous le pseudonyme **Satoshi Nakamoto**. Objectif : permettre des paiements peer-to-peer sans banque. Aujourd''hui il vaut plus de 60 000$ et est surtout utilisé comme réserve de valeur.

### C''est quoi la Blockchain ?
La **blockchain** est un registre numérique public, immuable et décentralisé. Imagine un cahier de comptes partagé entre des milliers d''ordinateurs dans le monde. Chaque transaction est vérifiée par le réseau, inscrite dans un "bloc" chaîné aux précédents — impossible à falsifier.

Créée pour éviter la double dépense et supprimer l''intermédiaire (banque, notaire…).

### C''est quoi le Mining (Minage) ?
Le **minage** est le processus par lequel des ordinateurs puissants valident les transactions blockchain et reçoivent en récompense de nouveaux bitcoins. En RDC, l''électricité bon marché dans certaines zones (barrages) attire des mineurs.

### C''est quoi un Wallet (Portefeuille blockchain) ?
Un **wallet** est une application (Binance, Trust Wallet, MetaMask…) qui stocke tes clés cryptographiques pour accéder à tes cryptos. Il ne stocke pas les coins eux-mêmes — ceux-ci restent sur la blockchain.

### Crypto vs Monnaie Fiduciaire
| Cryptomonnaie | Monnaie fiduciaire (CDF, USD…) |
|---|---|
| Décentralisée | Contrôlée par une banque centrale |
| Émission limitée (Bitcoin : 21M max) | Peut être imprimée à l''infini |
| Très volatile | Relativement stable |
| Disponible 24h/24 | Banques fermées le week-end |

### Comment gagner avec l''investissement crypto ?
1. **Buy & Hold** : acheter et conserver (ex: acheter BTC à 20k, vendre à 60k).
2. **Trading** : spéculer sur les fluctuations à court terme.
3. **Staking** : bloquer ses cryptos pour valider le réseau et toucher des intérêts (comme un dépôt bancaire).

> En RDC, la crypto est utilisée pour contourner les restrictions bancaires et les frais Western Union. Le franc congolais perdant régulièrement de la valeur, certains Congolais épargnent en USDT (stablecoin indexé sur le dollar).');

-- =============================================================================
-- STRUCTURE DU MARCHÉ
-- =============================================================================

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Structure du marché : tendances et impulsions', 10,
'## Structure du Marché

### C''est quoi une Tendance ?
Une **tendance** est la direction générale dans laquelle évolue un prix sur une période donnée.

- **Tendance haussière** : le prix fait des **hauts plus hauts** (HH) et des **bas plus hauts** (HL) — il monte globalement.
- **Tendance baissière** : le prix fait des **hauts plus bas** (LH) et des **bas plus bas** (LL) — il descend globalement.
- **Tendance neutre (range)** : le prix oscille entre deux niveaux sans direction claire.

### C''est quoi une Impulsion (Pulsion) ?
Une **impulsion** est un mouvement fort et rapide dans le sens de la tendance. C''est le "moteur" du marché.

### C''est quoi une Correction ?
Une **correction** est un mouvement contraire à la tendance principale, plus lent et moins fort que l''impulsion. Le prix "reprend son souffle" avant de repartir.

```
Tendance haussière :
    /\    /\
   /  \  /  \
        \/
Impulsion → Correction → Impulsion → ...
```

### La Théorie de Dow
Charles Dow (fondateur du Wall Street Journal, XIXe siècle) a posé les bases de l''analyse technique :
1. Les indices reflètent tout (toute info est dans le prix).
2. Le marché a trois types de tendances : primaire (mois/années), secondaire (semaines), tertiaire (jours).
3. Une tendance se confirme quand deux indices la confirment.
4. Le volume doit confirmer la tendance.
5. Une tendance reste en place jusqu''à un signal clair de retournement.

### Retournement de tendance haussière
Le prix casse le dernier bas de correction (HL) vers le bas → signal de retournement baissier possible.

### Retournement de tendance baissière
Le prix casse le dernier haut de correction (LH) vers le haut → signal de retournement haussier possible.

### Micro-tendance et Fractalité
Une **micro-tendance** est une tendance sur un très petit time frame (M1, M5). La **fractalité** signifie que la structure du marché est la même à toutes les échelles : une tendance haussière sur W1 contient des micro-tendances baissières sur M15. C''est comme zoomer sur une côte rocheuse — chaque rocher a la même forme que la côte entière.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Supports, Résistances, Tests et Retests', 11,
'## Supports et Résistances

### Support
Un **support** est un niveau de prix où la demande est suffisamment forte pour arrêter ou inverser une baisse. Le prix "rebondit" dessus comme sur un trampoline.

En RDC : imagine le prix du dollar au marché Gambela. Chaque fois qu''il tombe à 2 780 FC, des commerçants se ruent pour en acheter — ce niveau est un support.

### Résistance
Une **résistance** est un niveau de prix où l''offre est suffisamment forte pour arrêter ou inverser une hausse. Le prix "plafonne" contre elle.

### Support Fort vs Support Faible
- **Support fort** : testé plusieurs fois, rebonds puissants, visible sur les grands time frames (D1, W1). Beaucoup d''ordres s''y trouvent.
- **Support faible** : testé peu de fois, rebonds timides, visible seulement sur petits TF. Peut être cassé facilement.

### Résistance Forte vs Résistance Faible
Même logique : une résistance forte a été testée plusieurs fois avec des rejets puissants.

### Test
Un **test** c''est quand le prix revient toucher un niveau (support ou résistance) pour la première ou deuxième fois.

### Retest
Un **retest** se produit après une cassure. Par exemple : le prix casse un support vers le bas, puis remonte brièvement toucher ce niveau (qui devient résistance) avant de repartir à la baisse.

```
Résistance -------- (prix bloqué plusieurs fois)
                          |
                     CASSURE
                          |
Retest ----------- (prix revient tester = ancienne résistance devient support)
                          |
                   continuation
```

### Zone de Demande
Une **zone de demande** (Demand Zone) est une zone de prix où les acheteurs institutionnels ont massivement acheté dans le passé. Quand le prix y retourne, on s''attend à un nouveau mouvement haussier fort.

### Zone d''Offre
Une **zone d''offre** (Supply Zone) est l''inverse : zone de forte vente institutionnelle → mouvement baissier attendu au retour.

### Range (Accumulation)
Un **range** est une phase où le prix oscille horizontalement entre un support et une résistance sans tendance claire. C''est une phase d''**accumulation** (les gros acteurs accumulent des positions discrètement) avant une cassure explosive.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Figures Chartistes', 12,
'## Les Figures Chartistes

Les figures chartistes sont des **patterns graphiques récurrents** qui annoncent souvent la continuation ou le retournement d''une tendance.

---

### Figures de Retournement

**Top / Double Top / Triple Top**
- **Top** : un seul sommet → simple signal de résistance.
- **Double Top** : deux sommets au même niveau → signal fort de retournement baissier.
- **Triple Top** : trois sommets → signal encore plus fort (rare mais puissant).

**Bottom / Double Bottom / Triple Bottom**
- Même logique mais à l''envers (planchers) → retournement haussier.

**Épaule-Tête-Épaule (ETE)**
Trois sommets : le central (tête) plus haut que les deux autres (épaules). La cassure de la "neckline" (ligne de cou) annonce un retournement baissier. Figure la plus fiable de l''analyse technique.

**ETE Renversé**
Même figure à l''envers (trois creux) → retournement haussier.

---

### Figures de Continuation

**Triangle Symétrique**
Deux droites convergentes (hauts plus bas + bas plus hauts) → le prix comprime. Cassure dans un sens = continuation probable.

**Triangle Haussier**
Résistance horizontale + bas plus hauts → compression vers le haut → cassure haussière probable.

**Triangle Baissier**
Support horizontal + hauts plus bas → cassure baissière probable.

---

### Autres Figures Importantes

**Volatilité**
Mesure de l''amplitude des mouvements de prix. Forte volatilité = grandes bougies. Faible volatilité = range comprimé avant une explosion.

**Distribution**
Phase inverse de l''accumulation : les gros acteurs vendent discrètement en haut de tendance avant une baisse.

**Gap**
Un **gap** est un saut de prix entre la clôture d''une bougie et l''ouverture de la suivante (zone vide sur le graphique). Les gaps tendent à se refermer. Causes : news importantes, ouverture de session, week-end.

**Cassure de Support / Résistance**
- Cassure de support : le prix passe sous un support → accélération baissière.
- Cassure de résistance : le prix passe au-dessus → accélération haussière.

**Pullback**
Retour vers le niveau cassé après une cassure (= retest). Zone d''entrée classique pour les traders.

**Pause-Cassure**
Le prix consolide quelques bougies contre un niveau avant de le casser avec force.

**Trend Line (Ligne de tendance)**
Droite tracée reliant les bas d''une tendance haussière (support oblique) ou les hauts d''une tendance baissière (résistance oblique). Sa cassure est un signal de retournement.');

-- =============================================================================
-- TYPES DE BOUGIES
-- =============================================================================

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Lecture des Bougies Japonaises', 13,
'## Les Types de Bougies Japonaises

Une **bougie japonaise** (chandlestick) représente le mouvement du prix sur une période donnée. Elle affiche 4 informations : Ouverture (O), Fermeture (C), Haut (H), Bas (L).

```
        |   ← Mèche haute
     [     ]  ← Corps (vert = haussière, rouge = baissière)
        |   ← Mèche basse
```

---

### Bougie Standard (Marabozu)
Corps long, peu ou pas de mèches → les acheteurs (vert) ou vendeurs (rouge) ont dominé toute la période. Signal de force directionnelle.

### Bougie Engulfing (Avalement)
Une bougie qui "avale" entièrement la bougie précédente.
- **Bullish Engulfing** : bougie verte avale une rouge → retournement haussier.
- **Bearish Engulfing** : bougie rouge avale une verte → retournement baissier.

### Doji
Corps très petit ou inexistant (ouverture ≈ fermeture). Les forces sont équilibrées. Signal d''indécision → possible retournement si apparaît après une tendance forte.

### Marteau (Hammer)
Petite corps en haut, longue mèche basse. Apparaît après une baisse → les vendeurs ont poussé fort mais les acheteurs ont repris le contrôle → signal haussier.

### Marteau Renversé (Inverted Hammer / Shooting Star)
Corps en bas, longue mèche haute.
- Après une baisse : Inverted Hammer → signal haussier.
- Après une hausse : Shooting Star → signal baissier (les acheteurs ont échoué à maintenir les hauts).

### Pin Bar
Longue mèche d''un côté, corps court de l''autre. Le prix a rejeté fortement un niveau. C''est une bougie de **contrôle** : elle montre qui a pris le dessus.

### Bougie de Contrôle
Toute bougie qui montre une prise de contrôle nette par les acheteurs ou vendeurs — souvent une grande bougie impulsive qui englobe plusieurs petites bougies précédentes.

---

> **Règle d''or** : une bougie ne se lit jamais seule. Elle prend du sens dans son contexte : tendance, niveau de support/résistance, time frame.');

-- =============================================================================
-- TIME FRAMES
-- =============================================================================

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Time Frames : lecture multi-échelle', 14,
'## Les Time Frames

Un **time frame** (TF) est la durée représentée par chaque bougie sur le graphique.

| Abréviation | Nom | Durée d''une bougie |
|---|---|---|
| **MN** | Monthly | 1 mois |
| **W1** | Weekly | 1 semaine |
| **D1** | Daily | 1 jour |
| **H4** | 4 hours | 4 heures |
| **H1** | 1 hour | 1 heure |
| **M30** | 30 minutes | 30 minutes |
| **M15** | 15 minutes | 15 minutes |
| **M5** | 5 minutes | 5 minutes |
| **M1** | 1 minute | 1 minute |

---

### TP1 — Combien de bougies inférieures dans une bougie supérieure ?

| 1 bougie de... | = bougies de W1 | = bougies de D1 | = bougies de H4 | = bougies de H1 | = bougies de M30 | = bougies de M15 | = bougies de M5 | = bougies de M1 |
|---|---|---|---|---|---|---|---|---|
| **MN** | ~4.3 | ~22 | ~132 | ~528 | ~1 056 | ~2 112 | ~6 336 | ~31 680 |
| **W1** | — | 5 | 30 | 120 | 240 | 480 | 1 440 | 7 200 |
| **D1** | — | — | 6 | 24 | 48 | 96 | 288 | 1 440 |
| **H4** | — | — | — | 4 | 8 | 16 | 48 | 240 |
| **H1** | — | — | — | — | 2 | 4 | 12 | 60 |
| **M30** | — | — | — | — | — | 2 | 6 | 30 |
| **M15** | — | — | — | — | — | — | 3 | 15 |
| **M5** | — | — | — | — | — | — | — | 5 |

---

### À quoi sert le Time Frame ?

- **Grands TF (MN, W1, D1)** : donnent la **direction principale** et les niveaux majeurs (support/résistance forts).
- **TF intermédiaires (H4, H1)** : affinent la structure et confirment la tendance.
- **Petits TF (M15, M5, M1)** : permettent de **préciser l''entrée** en trade.

### La règle des 3 TF (Top-Down Analysis)
1. **Grand TF** → quelle est la tendance générale ?
2. **TF moyen** → y a-t-il une zone d''entrée ?
3. **Petit TF** → quelle bougie confirme l''entrée ?

> Exemple : D1 haussier → H4 revient sur support → M15 montre un bullish engulfing → ENTRÉE.');

-- =============================================================================
-- MONEY MANAGEMENT
-- =============================================================================

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Money Management : R, SL, TP, Drawdown', 15,
'## Money Management

Le money management (gestion du capital) est **la compétence la plus importante** du trading. Un mauvais trader avec un bon MM peut survivre. Un bon trader sans MM finit ruiné.

---

### C''est quoi R (Ratio Risque/Récompense) ?
**R** représente le rapport entre ce que tu risques et ce que tu espères gagner.

- R:R de 1:2 → tu risques 10$ pour gagner 20$
- R:R de 1:3 → tu risques 10$ pour gagner 30$

Avec un R:R de 1:2 et 50% de trades gagnants → **tu es profitable** même en perdant autant que tu gagnes.

---

### Stop Loss (SL)
Le **Stop Loss** est un ordre automatique qui ferme ton trade en perte à un prix défini pour limiter ta perte maximale.

Sans SL = suicide financier. En RDC, la connexion internet peut couper — ton SL te protège même hors ligne.

### Take Profit (TP)
Le **Take Profit** est un ordre automatique qui ferme ton trade en gain à un prix défini. Il sécurise ton profit sans que tu aies à surveiller l''écran.

### Drawdown
Le **drawdown** est la perte maximale subie depuis un pic de capital.
- Si tu pars de 1 000$ et tombes à 700$ → drawdown de 30%.
- Plus le drawdown est élevé, plus il faut un gain énorme pour récupérer (perdre 50% → il faut +100% pour revenir au départ).

### Break-Even (BE)
Le **break-even** consiste à déplacer ton stop loss au niveau du prix d''entrée une fois que le trade est en profit. Résultat : trade gratuit, risque = 0.

### Trailing Stop
Un **trailing stop** suit le prix à distance fixe quand il va dans ton sens. Il monte automatiquement si le prix monte (en trade haussier), sécurisant progressivement ton profit.

---

### Ce qu''on retrouve dans un compte MT5
| Terme | Signification |
|---|---|
| **Balance** | Capital de base (sans les trades ouverts) |
| **Equity** | Balance + profit/perte des trades ouverts |
| **Marge** | Somme bloquée par le broker pour ouvrir le trade |
| **Marge libre** | Capital disponible pour ouvrir de nouveaux trades |
| **Niveau de marge** | Equity / Marge × 100% — en dessous de 100% : danger de margin call |');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Les Lots : exemples avec 10$, 1 000$ et 1 000 000$', 16,
'## C''est quoi un Lot en Trading ?

Un **lot** est l''unité de mesure d''une position en trading.

| Lot | Unités de devise | Valeur pip EUR/USD (approx.) |
|---|---|---|
| 1 lot standard | 100 000 unités | ~10$ |
| 0.1 lot (mini) | 10 000 unités | ~1$ |
| 0.01 lot (micro) | 1 000 unités | ~0.10$ |
| 0.001 lot (nano) | 100 unités | ~0.01$ |

**Lot minimum** : 0.01 (micro-lot) — accessible même avec 5$ sur certains brokers.
**Lot maximum** : dépend du broker et de ta marge disponible.

---

### Exemple 1 — Trader 10$ avec un petit risque (standard)
- Capital : 10$
- Risque par trade : 1% = **0.10$**
- Stop Loss : 10 pips
- Lot à utiliser : 0.10$ ÷ 10 pips ÷ 0.10$/pip = **0.01 lot** (micro)
- Résultat : si le marché fait 20 pips dans ton sens (R:R 1:2) → **+0.20$**

### Exemple 2 — Trader 10$ en full margin (dangereux)
- Capital : 10$
- Lot : 1 standard (levier 1:100)
- Marge requise : ~10$
- 10 pips contre toi = **-10$** → compte grillé en quelques minutes

Ce type de trading est **du suicide financier**. Ne jamais utiliser la full marge.

### Exemple 3 — Trader 1 000 000$ avec risque minimal strict
- Capital : 1 000 000$
- Risque par trade : 0.5% = **5 000$**
- Stop Loss : 20 pips sur EUR/USD
- Lot : 5 000$ ÷ 20 pips ÷ 10$/pip = **25 lots**
- Take Profit : 60 pips (R:R 1:3) → **+15 000$** par trade gagnant

Le principe est identique quel que soit le capital : **définir le risque en $ d''abord, calculer le lot après**.

---

### Martingale
La **martingale** consiste à doubler la taille de position après chaque perte pour récupérer en un seul gain.
- **Avantage** : en théorie, une seule victoire efface toutes les pertes.
- **Désavantage** : 6 pertes consécutives × doublement = position × 64. Avec 10$ de départ : 640$ nécessaires pour le 7e trade. **Ruine quasi-certaine à long terme.**

### Grid Trading
Le **grid trading** consiste à placer plusieurs ordres buy et sell à intervalles réguliers (une grille).
- **Avantage** : profite de la volatilité dans les deux sens, pas besoin de prédire la direction.
- **Désavantage** : en tendance forte dans un seul sens, les pertes s''accumulent sur les ordres en sens inverse. Nécessite beaucoup de marge.

### Comment accumuler un gros lot avec plusieurs petits ?
Plutôt qu''ouvrir 1 lot d''un coup (grosse marge, gros risque), tu peux ouvrir **10 × 0.1 lot** sur la même zone de prix (par exemple sur un support fort). L''effet est identique à 1 lot total, mais :
- Tu échelonnes les entrées sur plusieurs niveaux.
- Si le marché baisse légèrement avant de monter, tes entrées successives donnent un **prix moyen d''entrée plus bas**.
- Chaque mini-lot a son propre SL — mieux contrôlé.

---

## C''est quoi l''Analyse Technique ?
L''**analyse technique** consiste à étudier les graphiques de prix passés pour anticiper les mouvements futurs. Elle se base sur : tendances, supports/résistances, figures chartistes, volumes, bougies.

Principe : l''histoire se répète — les comportements humains créent des patterns récurrents.

## C''est quoi l''Analyse Fondamentale ?
L''**analyse fondamentale** consiste à analyser les données économiques et financières réelles pour évaluer la valeur d''un actif : PIB, inflation, taux d''intérêt, résultats d''entreprises, évènements géopolitiques.

En Forex : une annonce Fed qui monte les taux → le dollar monte. En RDC : une hausse du prix du cobalt → les actions des sociétés minières montent.

> Les traders utilisent souvent les deux : la fondamentale pour savoir **dans quel sens** le marché va long terme, la technique pour décider **quand et où** entrer.');

-- =============================================================================
-- MODULES COMPLÉMENTAIRES (modules 17-30 : pratique et approfondissement)
-- =============================================================================

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Sessions de marché et fuseaux horaires (RDC)', 17,
'## Les Sessions de Marché

Le Forex est ouvert 24h/24 mais toutes les heures ne se valent pas. Il y a 4 grandes sessions :

| Session | Heure locale Kinshasa (UTC+2) | Volume |
|---|---|---|
| Sydney | 00h00 – 09h00 | Faible |
| Tokyo | 03h00 – 12h00 | Moyen |
| Londres | 09h00 – 18h00 | Très fort |
| New York | 14h00 – 23h00 | Très fort |

**Meilleur moment pour trader depuis Kinshasa** : 09h00 – 18h00 (chevauchement Londres/New York 14h-18h = volatilité maximale).

### Kills Zones (zones à haute liquidité)
- **London Open Kill Zone** : 09h00 – 12h00 Kinshasa
- **New York Open Kill Zone** : 14h00 – 17h00 Kinshasa

Ces zones concentrent les mouvements les plus puissants de la journée. C''est là que les institutions placent leurs gros ordres.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'CH2 — Les Zones de Liquidité et les pièges à débutants', 18,
'## Les Zones de Liquidité

Les **institutions** (banques, hedge funds) ont besoin de beaucoup de liquidité pour remplir leurs énormes ordres. Elles créent intentionnellement des mouvements pour aller "chasser" les stops des petits traders.

### Comment ça marche ?
Les débutants placent leurs stops juste sous un support évident. Les institutions le savent. Elles poussent brièvement le prix en dessous (fausse cassure = "stop hunt") pour déclencher ces stops, récupérer la liquidité, puis font monter le prix.

### Le faux breakout (Fakeout)
Le prix casse un support ou une résistance, tous les débutants entrent en pensant à une vraie cassure → le prix revient immédiatement → les débutants sont stoppés.

### Comment se protéger ?
1. Ne pas placer son SL exactement sur le support/résistance évident — laisser quelques pips de marge.
2. Attendre une **confirmation** (bougie de clôture, retest) avant d''entrer.
3. Analyser le volume : une vraie cassure s''accompagne de volume fort.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Psychologie du trader débutant — les 7 biais à éviter', 19,
'## Psychologie du Trader

95% des traders perdent non pas parce qu''ils manquent de stratégie, mais parce qu''ils ne contrôlent pas leurs émotions.

### Les 7 Biais Cognitifs à Connaître

1. **FOMO (Fear Of Missing Out)** : entrer en retard sur un trade par peur de rater. Résultat : entrée au pire moment.

2. **Revenge Trading** : reprendre un trade immédiatement après une perte pour "récupérer". Résultat : pertes qui s''accumulent.

3. **Surconfiance** : après 3 gains consécutifs, augmenter les lots inconsidérément. Résultat : une seule perte efface tout.

4. **Espoir** : garder un trade perdant en espérant qu''il revienne. Résultat : le SL ne se déclenche jamais car on l''a déplacé ou supprimé.

5. **Confirmation bias** : chercher uniquement les informations qui confirment sa vision. Résultat : analyses biaisées.

6. **Overtrading** : prendre trop de trades pour "s''occuper". Résultat : commission/spread qui érode le capital.

7. **Impatience** : sortir d''un trade gagnant trop tôt par peur. Résultat : ratio R:R inférieur au plan.

### Solution : LE PLAN DE TRADING
Un plan écrit avec des règles précises est le seul rempart contre les émotions. Si ce n''est pas dans le plan, ce n''est pas un trade.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Construire son Plan de Trading personnel', 20,
'## Le Plan de Trading

Un plan de trading est un document écrit qui définit **toutes** tes règles avant d''ouvrir le marché.

### Structure d''un Plan de Trading

**1. Actifs tradés** : EUR/USD uniquement en phase débutant.

**2. Time frames utilisés** : analyse D1, entrée H1/M15.

**3. Sessions** : uniquement London Open (09h-12h Kinshasa).

**4. Règles d''entrée** :
- Tendance D1 confirmée
- Prix sur zone de support/résistance forte
- Bougie de confirmation (pin bar, engulfing)

**5. Gestion du risque** :
- Risque max par trade : 1% du capital
- Risque max par jour : 3% du capital
- Si -3% atteint : arrêt total pour la journée

**6. Take Profit** : R:R minimum 1:2

**7. Règles de sortie** :
- SL atteint → accepter la perte, ne pas déplacer
- BE dès +1R de profit
- TP ferme la position automatiquement

**8. Journal** : noter chaque trade (screenshot + analyse)

> Colle ton plan au-dessus de ton bureau. Chaque jour avant d''ouvrir MT5, relis-le.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Journal de Trading : méthode et template', 21,
'## Le Journal de Trading

Le journal de trading est l''outil qui transforme un parieur en trader professionnel. Sans journal, tu répètes les mêmes erreurs indéfiniment.

### Ce que chaque entrée doit contenir
- Date et heure
- Paire tradée et time frame
- Direction (Buy/Sell)
- Prix d''entrée
- Stop Loss et Take Profit
- Lot utilisé
- Résultat (pips, dollars)
- Screenshot du graphique au moment de l''entrée
- Émotion ressentie (neutre, anxieux, confiant)
- Ce qui s''est passé (respecté le plan ? Erreur commise ?)

### Analyse mensuelle
Chaque fin de mois, calcule :
- Taux de gain (Win rate)
- R:R moyen réalisé
- Meilleure session de la journée pour toi
- Erreur la plus fréquente

> En RDC, tu peux tenir ton journal sur Google Sheets (accessible depuis mobile même avec une connexion lente).');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Backtesting : tester sa stratégie sur l''historique', 22,
'## Le Backtesting

Le **backtesting** consiste à appliquer ta stratégie sur des données de prix historiques pour voir si elle aurait été profitable dans le passé.

### Pourquoi backtester ?
- Vérifier que ta stratégie a un avantage statistique réel.
- Identifier ses points faibles.
- Gagner confiance avant de trader avec de l''argent réel.

### Comment backtester sur MT5
1. Ouvre un graphique (ex: EUR/USD D1).
2. Va dans **Affichage → Historique des cours** pour charger 10 ans de données.
3. Active le mode **simulation manuelle** : Outils → Strategy Tester → mode visuel.
4. Avance barre par barre et applique tes règles comme si c''était en direct.
5. Note chaque trade virtuel dans ton journal.

### Ce qu''un bon backtest révèle
- Win rate (ex: 55%)
- R:R moyen (ex: 1:2.1)
- Drawdown maximal (ex: -8%)
- Profit factor (gains totaux ÷ pertes totales) → doit être > 1.5 pour une bonne stratégie

> **Important** : un backtest positif ne garantit pas le futur. Mais un backtest négatif prouve que la stratégie ne fonctionne pas. Forward test (compte démo) ensuite.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Premiers Trades sur Compte Démo WellTrade', 23,
'## Tes Premiers Trades sur Démo

Le **compte démo** est ton terrain d''entraînement. Il fonctionne exactement comme un vrai compte mais avec de l''argent fictif.

### Règle d''or
Traite ton démo **exactement** comme un vrai compte. Même taille de lot relative, mêmes règles, même journal. Les traders qui traitent le démo à la légère (full marge, pas de SL) n''apprennent rien.

### Procédure pour ton premier trade sur MT5
1. Ouvre MT5 → sélectionne EUR/USD sur H1.
2. Analyse la structure (tendance D1, zone de support).
3. Identifie une entrée selon ton plan.
4. Clic droit sur le graphique → **Trading → Nouvel Ordre**.
5. Remplis : Volume (0.01), Stop Loss (prix), Take Profit (prix).
6. Clique **Buy** ou **Sell**.
7. Observe le trade dans l''onglet **Trade** en bas.
8. Screenshot + journal.

### Critères pour passer au compte réel
- 3 mois consécutifs en démo avec respect du plan
- Win rate ≥ 50% et R:R ≥ 1:2
- Aucun trade sans SL
- Journal tenu à jour');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Analyse des Sessions : Londres et New York', 24,
'## Analyser les Sessions de Marché

### Routine London Open (09h00 Kinshasa)
1. 08h30 — Analyse D1 : tendance et niveaux majeurs.
2. 08h45 — Analyse H4 : zones d''intérêt proches.
3. 09h00 — Ouverture London : observer les 3 premières bougies H1.
4. Chercher : impulsion initiale, faux breakout (stop hunt), retournement vers la direction D1.
5. Entrée si tout est aligné. Sinon : patience.

### Routine New York Open (14h00 Kinshasa)
- Souvent confirmation ou continuation de la session London.
- Volatilité élevée sur USD (annonces économiques US à 14h30, 16h00).
- Calendrier économique à consulter chaque matin : **investing.com/economic-calendar**

### Annonces économiques importantes
- **NFP** (Non-Farm Payroll) : 1er vendredi du mois à 14h30 → forte volatilité USD.
- **CPI** (inflation) : mensuel → impact direct sur les taux Fed.
- **FOMC** (décisions Fed) : 8 fois par an → mouvement parfois de 200+ pips.

> En période d''annonces : réduire les lots ou ne pas trader. Les spreads explosent en 30 secondes.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Les 12 erreurs fatales des débutants (et comment les éviter)', 25,
'## Les 12 Erreurs Fatales des Débutants

1. **Trader sans plan** → résultat aléatoire = ruine progressive.
2. **Pas de Stop Loss** → un seul trade suffit pour tout perdre.
3. **Trop gros lots** → une mauvaise journée = margin call.
4. **FOMO — entrer en retard** → acheter le sommet, vendre le creux.
5. **Revenge trading** → doubler après une perte = l''erreur numéro 1 des débutants congolais.
6. **Changer de stratégie chaque semaine** → aucune stratégie n''a de résultat sans au moins 100 trades de backtest.
7. **Suivre des signaux Telegram sans comprendre** → tu ne sais jamais pourquoi tu perds.
8. **Trader pendant les grosses news sans réduire le lot** → le spread triple, les stops sont déclenchés aléatoirement.
9. **Ne pas tenir de journal** → les erreurs se répètent.
10. **Surtrader (overtrading)** → 10 trades par jour = frais spread × 10.
11. **Dépôt avec argent qu''on ne peut pas se permettre de perdre** → la peur bloque les décisions rationnelles.
12. **Promettre des rendements à sa famille** → la pression sociale détruit la discipline.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Création d''un compte réel : quand et comment déposer', 26,
'## Passer du Démo au Compte Réel

### Quand est-tu prêt ?
Checklist avant le premier dépôt réel :
- [ ] 3 mois minimum en démo avec résultats stables
- [ ] Journal de trading tenu chaque semaine
- [ ] Règles du plan respectées à 90%+
- [ ] Win rate ≥ 50% sur 100+ trades démo
- [ ] Aucune position ouverte sans SL

### Combien déposer ?
Commence avec **le montant minimum** que le broker accepte (50–100$ sur WellTrade). Pas plus. La performance en trading ne dépend pas du capital initial mais de la gestion.

### Méthodes de dépôt depuis la RDC
- **Airtel Money / M-Pesa** (via Orange Money) → conversion en USD
- **Carte Visa prépayée** (certaines banques congolaises en émettent)
- **Binance P2P** → acheter de l''USDT, transférer chez le broker
- **Western Union / Ria** → certains brokers acceptent les virements agents

### Objectif réaliste pour un compte de 100$
- Mois 1-3 : apprendre à exécuter sans erreur = objectif 0% de perte (pas de gain, juste survivre)
- Mois 4-6 : viser 5-10% par mois **maximum** avec risque 1% par trade
- Ne jamais promettre de rendement à quelqu''un d''autre');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Prop Firms : trader avec l''argent des autres', 27,
'## Les Prop Firms (Sociétés de Financement)

Une **Prop Firm** (Proprietary Trading Firm) est une société qui finance des traders qui ont prouvé leur compétence en réussissant un "challenge".

### Comment ça marche ?
1. Tu paies des frais de challenge (ex: 100–500$ selon la taille du compte).
2. Tu trades un compte fictif (ex: 100 000$) en respectant des règles strictes.
3. Si tu respectes les règles pendant X jours → tu reçois un compte réel.
4. Tu gardes 80-90% des profits. La firm garde 10-20%.

### Prop Firms populaires accessibles depuis la RDC
- **FTMO** (Czech Republic) → la référence, challenge 10%
- **MyForexFunds** — vérifier disponibilité
- **The Funded Trader**
- **True Forex Funds**

### Règles typiques d''un challenge FTMO 100k
- Objectif profit : +10% (= +10 000$)
- Drawdown journalier max : -5% (= -5 000$/jour)
- Drawdown global max : -10% (= -10 000$)
- Durée minimum : 10 jours de trading

> La formation Prop Firm Mastery de Katsuki est entièrement dédiée à la réussite de ces challenges.');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Révision Chapitre 1 & 2 : Quiz de préparation', 28,
'## Révision Complète — Chapitres 1 et 2

### Chapitre 1 — Questions de révision
1. Quelle est la différence entre un broker ECN et un Market Maker ?
2. Explique le spread avec un exemple utilisant le franc congolais.
3. Pourquoi utilise-t-on MetaTrader 5 et non directement le site du broker ?
4. Qu''est-ce qui différencie un trader d''un parieur ?

### Chapitre 2 — Questions de révision
5. Cite les 7 paires majeures du Forex.
6. Quelle est la différence entre un indice synthétique et un indice réel ?
7. Explique la différence entre support fort et support faible avec un exemple.
8. Qu''est-ce qu''un Doji et que signifie-t-il ?
9. Combien de bougies H1 contient une bougie D1 ?
10. Tu as un compte de 200$. Avec un risque de 1% et un SL de 15 pips sur EUR/USD, quel lot dois-tu utiliser ?
11. Qu''est-ce que le trailing stop et dans quelle situation l''utiliser ?
12. Cite 3 erreurs fatales des débutants et leur solution.

### Réponse au calcul de lot (Q10)
- Risque = 1% × 200$ = **2$**
- Valeur du pip pour 0.01 lot EUR/USD = **0.10$**
- 15 pips × 0.10$ = 1.50$ pour 0.01 lot → 2$ ÷ 1.50$ × 0.01 = **0.013 lot**
- On arrondit à **0.01 lot** (lot minimum disponible)');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Conclusion : ta roadmap de 90 jours', 29,
'## Ta Roadmap de 90 Jours vers le Trading Profitable

### Jours 1-15 : Fondations
- Ouvrir compte démo WellTrade + connecter MT5
- Étudier les modules Chapitre 1 et 2
- Lire 1 graphique D1 chaque jour (EUR/USD) et noter la tendance

### Jours 16-30 : Structure et bougies
- Identifier quotidiennement : support, résistance, tendance, bougies de signal
- Backtester 50 trades manuels sur historique EUR/USD H1

### Jours 31-60 : Trading démo actif
- Placer 1-2 trades par semaine sur démo (pas plus)
- Respecter 100% du plan écrit
- Tenir journal pour chaque trade

### Jours 61-90 : Évaluation et décision
- Analyser le journal : win rate, R:R, erreurs récurrentes
- Si résultats positifs : préparer le premier dépôt réel
- Si résultats négatifs : identifier l''erreur principale, refaire 30 jours

### Rappels permanents
> "Le marché sera toujours là demain. Un trade raté n''est pas grave. Un compte grillé, si."

> "Discipline + Gestion du risque + Patience = Les 3 piliers du trader profitable."

> "En RDC, nous avons tous les atouts pour réussir en trading : accès internet, motivation, et maintenant — la méthode Katsuki."');

INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Ressources : calendrier éco, outils gratuits, communauté Katsuki', 30,
'## Ressources Essentielles pour le Trader Congolais

### Outils gratuits indispensables
| Outil | Usage | Lien |
|---|---|---|
| TradingView | Graphiques avancés gratuits | tradingview.com |
| Investing.com | Calendrier économique | investing.com/economic-calendar |
| Myfxbook | Analyser et partager ses performances | myfxbook.com |
| MetaTrader 5 | Plateforme de trading | metatrader5.com |
| Deriv | Indices synthétiques | deriv.com |

### Calculateur de lot en ligne
**cashbackforex.com/tools/position-size-calculator**
Entre ton capital, ton risque en %, tes pips de SL → il calcule le lot exact.

### Communauté Katsuki
- Groupe Telegram privé : accès via ton espace étudiant après inscription
- Sessions live mensuelles : annoncées dans le dashboard

### Lectures recommandées
- *Trading in the Zone* — Mark Douglas (psychologie)
- *Technical Analysis of the Financial Markets* — John J. Murphy
- *Reminiscences of a Stock Operator* — Edwin Lefèvre (histoire vraie d''un trader légendaire)

### Note importante pour la RDC
Le trading n''est **pas réglementé** officiellement en RDC. Utilise uniquement des brokers régulés (FCA, CySEC, FSC). Méfie-toi des "signaux Telegram" qui promettent 1 000$ par semaine — ce sont des arnaques dans 99% des cas.');

-- Mettre à jour le nombre de modules
UPDATE courses SET modules_count = 30 WHERE id = 1;

SET foreign_key_checks = 1;

-- Vérification
SELECT id, title, order_index FROM modules WHERE course_id = 1 ORDER BY order_index;
