-- =============================================================================
-- MISE À JOUR DE L'EXAMEN — Genèse du Trader (exam_id = 1)
-- Questions couvrant les chapitres 1 et 2 du nouveau programme
-- =============================================================================
SET NAMES utf8mb4;

-- Supprimer les anciennes questions de l'examen 1
DELETE FROM exam_questions WHERE exam_id = 1;

-- Mettre à jour les infos de l'examen
UPDATE exams SET
  title            = 'Examen Final : Genèse du Trader — Chapitres 1 et 2',
  passing_score    = 70,
  duration_minutes = 60,
  instructions     = 'Cet examen couvre l''intégralité des chapitres 1 (Lexique) et 2 (Marchés, Structure, Bougies, Time Frames, Money Management). Vous devez obtenir au moins 70% pour valider la formation. Bonne chance !'
WHERE id = 1;

-- ──────────────────────────────────────────────────────────────────────────────
-- CHAPITRE 1 — LEXIQUE (10 questions)
-- ──────────────────────────────────────────────────────────────────────────────
INSERT INTO exam_questions (exam_id, question, options, correct_option, points) VALUES

(1, 'En RDC, comment un particulier peut-il accéder aux marchés financiers mondiaux ?',
 '["En allant physiquement à la Bourse de Kinshasa","Via internet avec un broker en ligne comme WellTrade","Uniquement via la Banque Centrale du Congo","En achetant des actions à la BRVM de Côte d''Ivoire"]',
 'Via internet avec un broker en ligne comme WellTrade', 2),

(1, 'Quelle est la différence fondamentale entre le trading et l''investissement ?',
 '["Le trading est légal, l''investissement ne l''est pas en RDC","Le trading spécule sur des mouvements de prix à court terme ; l''investissement vise la croissance à long terme","Le trading ne nécessite aucun capital","L''investissement est plus risqué que le trading"]',
 'Le trading spécule sur des mouvements de prix à court terme ; l''investissement vise la croissance à long terme', 2),

(1, 'Qu''est-ce qu''un broker Market Maker comme WellTrade ?',
 '["Il envoie directement tes ordres au marché interbancaire","Il traite tes ordres en interne et peut être ta contrepartie","Il ne prend aucun spread","Il est réservé aux traders institutionnels"]',
 'Il traite tes ordres en interne et peut être ta contrepartie', 2),

(1, 'Sur MT5, EUR/USD affiche Bid 1.0848 / Ask 1.0851. Quel est le spread en pips ?',
 '["1 pip","2 pips","3 pips","5 pips"]',
 '3 pips', 2),

(1, 'Quelle est la première étape pour trader depuis Kinshasa avec WellTrade ?',
 '["Télécharger MetaTrader 5 et entrer n''importe quelles données","S''inscrire sur welltrade.com, vérifier son email et ouvrir un compte démo","Déposer 500$ minimum avant toute inscription","Contacter la Banque Centrale du Congo pour autorisation"]',
 'S''inscrire sur welltrade.com, vérifier son email et ouvrir un compte démo', 1),

(1, 'Quelle est la différence entre MetaTrader 5 et le broker WellTrade ?',
 '["MetaTrader 5 détient ton argent ; WellTrade est le logiciel de graphiques","WellTrade détient ton argent ; MetaTrader 5 est l''interface de graphiques et d''ordres","Ce sont deux noms du même service","MetaTrader 5 est uniquement disponible sur PC"]',
 'WellTrade détient ton argent ; MetaTrader 5 est l''interface de graphiques et d''ordres', 2),

(1, 'Un cambiste à Gombe achète des dollars à 2 790 FC et les revend à 2 840 FC. Quel concept du trading ce comportement illustre-t-il ?',
 '["Le drawdown","Le spread","Le lot","Le take profit"]',
 'Le spread', 2),

(1, 'Quel est l''avantage principal d''un broker ECN/STP par rapport à un Market Maker ?',
 '["Spread fixe garanti","Aucun conflit d''intérêt : tes ordres sont envoyés au marché réel","Commission nulle","Accessible avec 1$ de dépôt"]',
 'Aucun conflit d''intérêt : tes ordres sont envoyés au marché réel', 2),

(1, 'Quel critère principal distingue un trader d''un parieur ?',
 '["Le trader utilise toujours plus de levier","Le trader exécute un plan avec des règles précises et un avantage statistique","Le trader ne perd jamais","Le trader trade uniquement le Forex"]',
 'Le trader exécute un plan avec des règles précises et un avantage statistique', 2),

(1, 'Sur MT5, dans quel onglet vois-tu tes trades ouverts en temps réel ?',
 '["Historique","Journal","Trade","Navigator"]',
 'Trade', 1);

-- ──────────────────────────────────────────────────────────────────────────────
-- CHAPITRE 2 — MARCHÉS & FOREX (8 questions)
-- ──────────────────────────────────────────────────────────────────────────────
INSERT INTO exam_questions (exam_id, question, options, correct_option, points) VALUES

(1, 'Combien de paires majeures existe-t-il sur le marché Forex ?',
 '["4","5","7","10"]',
 '7', 2),

(1, 'Quelle est la grande différence entre un indice synthétique Deriv (Volatility 75) et le S&P 500 ?',
 '["Le S&P 500 est disponible 24h/24 7j/7 ; le Volatility 75 ferme le week-end","Le Volatility 75 est généré par algorithme sans lien avec l''économie réelle ; le S&P 500 reflète 500 entreprises américaines","Ils fonctionnent exactement de la même manière","Le S&P 500 a un spread plus bas"]',
 'Le Volatility 75 est généré par algorithme sans lien avec l''économie réelle ; le S&P 500 reflète 500 entreprises américaines', 2),

(1, 'Qu''est-ce que la blockchain ?',
 '["Une application mobile de paiement","Un registre numérique public, immuable et décentralisé qui enregistre les transactions","Une cryptomonnaie comme le Bitcoin","Un broker de crypto"]',
 'Un registre numérique public, immuable et décentralisé qui enregistre les transactions', 2),

(1, 'En RDC, pourquoi certains épargnent-ils en USDT plutôt qu''en franc congolais ?',
 '["L''USDT rapporte plus d''intérêts que la banque","L''USDT est indexé sur le dollar et protège de la dépréciation du franc congolais","L''USDT est la monnaie officielle de la RDC","Les banques congolaises refusent le franc congolais"]',
 'L''USDT est indexé sur le dollar et protège de la dépréciation du franc congolais', 2),

(1, 'Quelle paire Forex est considérée comme la plus liquide au monde ?',
 '["GBP/JPY","USD/CHF","EUR/USD","AUD/CAD"]',
 'EUR/USD', 2),

(1, 'Qu''est-ce qu''une paire de devises mineure (cross) ?',
 '["Une paire qui inclut obligatoirement le Dollar américain","Une paire qui n''inclut pas le Dollar américain, comme EUR/GBP ou GBP/JPY","Une paire uniquement disponible sur indices synthétiques","Une paire avec un spread supérieur à 50 pips"]',
 'Une paire qui n''inclut pas le Dollar américain, comme EUR/GBP ou GBP/JPY', 2),

(1, 'Le minage de Bitcoin en RDC est parfois avantageux pour quelle raison principale ?',
 '["La RDC a une réglementation très favorable aux cryptos","Certaines zones de RDC disposent d''électricité bon marché grâce aux barrages hydrauliques","Bitcoin est plus utilisé en RDC que le franc congolais","Les mineurs congolais sont exonérés d''impôts"]',
 'Certaines zones de RDC disposent d''électricité bon marché grâce aux barrages hydrauliques', 1),

(1, 'Quel est le rôle d''un wallet (portefeuille blockchain) ?',
 '["Stocker physiquement les cryptomonnaies dans un disque dur","Stocker les clés cryptographiques permettant d''accéder à tes cryptos sur la blockchain","Convertir automatiquement les cryptos en francs congolais","Remplacer le compte bancaire chez WellTrade"]',
 'Stocker les clés cryptographiques permettant d''accéder à tes cryptos sur la blockchain', 2);

-- ──────────────────────────────────────────────────────────────────────────────
-- STRUCTURE DE MARCHÉ (8 questions)
-- ──────────────────────────────────────────────────────────────────────────────
INSERT INTO exam_questions (exam_id, question, options, correct_option, points) VALUES

(1, 'Dans une tendance haussière selon la théorie de Dow, qu''observe-t-on sur le graphique ?',
 '["Des hauts plus bas (LH) et des bas plus bas (LL)","Des hauts plus hauts (HH) et des bas plus hauts (HL)","Le prix oscille dans un range horizontal","Des bougies exclusivement vertes"]',
 'Des hauts plus hauts (HH) et des bas plus hauts (HL)', 2),

(1, 'Qu''est-ce qu''un retest après la cassure d''un support ?',
 '["Le prix continue immédiatement la baisse sans revenir","Le prix remonte brièvement toucher l''ancien support (devenu résistance) avant de repartir à la baisse","Le prix retourne à son niveau de départ","Une figure en double bottom"]',
 'Le prix remonte brièvement toucher l''ancien support (devenu résistance) avant de repartir à la baisse', 2),

(1, 'Quelle est la caractéristique d''un support FORT ?',
 '["Testé une seule fois, rebond timide","Testé plusieurs fois avec des rebonds puissants, visible sur les grands time frames","Uniquement visible sur M1 ou M5","Créé par un indicateur RSI en zone de survente"]',
 'Testé plusieurs fois avec des rebonds puissants, visible sur les grands time frames', 2),

(1, 'Qu''est-ce qu''une zone de demande (Demand Zone) ?',
 '["Une zone où les vendeurs institutionnels ont massivement vendu","Une zone où les acheteurs institutionnels ont massivement acheté dans le passé — retour attendu = hausse","Un range sans direction","La zone de stop loss du broker"]',
 'Une zone où les acheteurs institutionnels ont massivement acheté dans le passé — retour attendu = hausse', 2),

(1, 'Qu''est-ce qu''un gap sur un graphique de prix ?',
 '["Un spread élevé du broker","Un saut de prix entre la clôture d''une bougie et l''ouverture de la suivante (zone vide)","Une bougie doji","Une ligne de tendance oblique"]',
 'Un saut de prix entre la clôture d''une bougie et l''ouverture de la suivante (zone vide)', 2),

(1, 'Dans le contexte d''un Épaule-Tête-Épaule (ETE), que signifie la cassure de la neckline ?',
 '["Signal de continuation haussière","Signal de retournement baissier","Entrée en zone de range","Confirmation d''un double top"]',
 'Signal de retournement baissier', 2),

(1, 'Qu''est-ce que la fractalité du marché ?',
 '["Le marché a la même structure à toutes les échelles de temps — une tendance D1 contient des micro-tendances sur M15","Une technique de scalping sur M1 uniquement","Un indicateur mesurant la volatilité","La répétition exacte des mêmes prix chaque semaine"]',
 'Le marché a la même structure à toutes les échelles de temps — une tendance D1 contient des micro-tendances sur M15', 2),

(1, 'Qu''est-ce qu''une phase d''accumulation (range) ?',
 '["Une tendance haussière forte et rapide","Le prix oscille horizontalement pendant que les gros acteurs accumulent des positions discrètement avant une cassure","Une correction après impulsion","Une figure en triple top"]',
 'Le prix oscille horizontalement pendant que les gros acteurs accumulent des positions discrètement avant une cassure', 2);

-- ──────────────────────────────────────────────────────────────────────────────
-- BOUGIES, TIME FRAMES & MONEY MANAGEMENT (9 questions)
-- ──────────────────────────────────────────────────────────────────────────────
INSERT INTO exam_questions (exam_id, question, options, correct_option, points) VALUES

(1, 'Qu''indique un Doji après une longue tendance haussière ?',
 '["Accélération de la hausse","Indécision du marché — possible retournement baissier","Confirmation de la tendance","Un gap haussier"]',
 'Indécision du marché — possible retournement baissier', 2),

(1, 'Combien de bougies H4 contient une bougie D1 ?',
 '["4","6","8","12"]',
 '6', 2),

(1, 'Quelle est la règle des 3 Time Frames (Top-Down Analysis) ?',
 '["Utiliser uniquement M1, M5 et M15 pour entrer","Grand TF pour la direction, TF moyen pour la zone, petit TF pour la confirmation d''entrée","Analyser uniquement le W1 et le D1","Changer de TF toutes les heures"]',
 'Grand TF pour la direction, TF moyen pour la zone, petit TF pour la confirmation d''entrée', 2),

(1, 'Tu as un compte de 500$. Tu risques 1% avec un stop loss de 20 pips. La valeur du pip pour 0.01 lot EUR/USD est 0.10$. Quel lot utilises-tu ?',
 '["0.01 lot","0.02 lot","0.25 lot","0.10 lot"]',
 '0.02 lot', 3),

(1, 'Qu''est-ce que le trailing stop ?',
 '["Un stop loss fixe placé sous le support","Un stop qui se déplace automatiquement dans le sens du profit pour sécuriser les gains progressivement","Un ordre qui double la position en cas de perte","Un stop loss à 50 pips fixe"]',
 'Un stop qui se déplace automatiquement dans le sens du profit pour sécuriser les gains progressivement', 2),

(1, 'Quel est le principal danger de trader en full marge avec un petit compte de 10$ ?',
 '["Le broker ferme le compte immédiatement","Quelques pips contre toi suffisent à déclencher un margin call et perdre tout le compte","La plateforme MT5 est bloquée","Il est impossible d''ouvrir un trade en full marge"]',
 'Quelques pips contre toi suffisent à déclencher un margin call et perdre tout le compte', 2),

(1, 'Qu''est-ce que l''analyse fondamentale en trading Forex ?',
 '["L''analyse des graphiques de prix et des figures chartistes","L''analyse des données économiques (PIB, inflation, taux d''intérêt, décisions Fed) pour anticiper les mouvements","L''analyse des indicateurs RSI et MACD uniquement","L''analyse du carnet d''ordres et du Footprint"]',
 'L''analyse des données économiques (PIB, inflation, taux d''intérêt, décisions Fed) pour anticiper les mouvements', 2),

(1, 'Quelle est la meilleure plage horaire pour trader le Forex depuis Kinshasa (UTC+2) ?',
 '["00h00 – 06h00 (session Sydney)","09h00 – 18h00 (sessions Londres + New York, volatilité maximale)","20h00 – 23h00","Samedi et dimanche"]',
 '09h00 – 18h00 (sessions Londres + New York, volatilité maximale)', 2),

(1, 'Quelle erreur fatale commet un trader qui déplace son stop loss pendant un trade perdant pour éviter d''être stoppé ?',
 '["Il optimise sa gestion du risque","Il transforme une perte contrôlée en perte potentiellement totale du compte","Il applique le trailing stop correctement","Il respecte la règle du break-even"]',
 'Il transforme une perte contrôlée en perte potentiellement totale du compte', 3);

-- Vérification du nombre de questions
SELECT COUNT(*) AS total_questions,
       SUM(points) AS total_points
FROM exam_questions WHERE exam_id = 1;
