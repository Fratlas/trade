<?php
require_once __DIR__ . '/../utils/OpenAIClient.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Course.php';

class AIController {

    // -------------------------------------------------------------------------
    // ENDPOINTS PUBLICS
    // -------------------------------------------------------------------------

    public static function lesson($userId, $data) {
        $topic = trim($data['topic'] ?? 'trading');
        $level = trim($data['level'] ?? 'etudiant');

        if (!$topic) {
            http_response_code(400);
            echo json_encode(['error' => 'topic requis']);
            return;
        }

        $context = self::buildUserContext((int)$userId, $level);
        $moduleRef = self::findModuleByTopic($topic);
        $prompt = self::buildLessonPrompt($topic, $level, $context, $moduleRef);
        $system = OpenAIClient::defaultSystemPrompt() . "\n\nContexte plateforme:\n" . $context;

        $message = OpenAIClient::request($prompt, $system, 0.65, 1800);
        if (!$message) {
            echo json_encode([
                'lesson'  => self::fallbackLesson($topic, $level),
                'warning' => 'Service IA temporairement indisponible. Reponse de secours affichee.'
            ]);
            return;
        }

        $out = ['lesson' => $message];
        if (OpenAIClient::getLastModelUsed() && OpenAIClient::getLastModelUsed() !== OPENROUTER_MODEL) {
            $out['model'] = OpenAIClient::getLastModelUsed();
        }
        echo json_encode($out);
    }

    public static function questions($userId, $data) {
        $topic = trim($data['topic'] ?? 'trading');
        $level = trim($data['level'] ?? 'etudiant');
        $count = max(1, min(10, (int)($data['count'] ?? 5)));

        if (!$topic) {
            http_response_code(400);
            echo json_encode(['error' => 'topic requis']);
            return;
        }

        $context = self::buildUserContext((int)$userId, $level);
        $prompt  = self::buildQuestionsPrompt($topic, $level, $count, $context);
        $system  = OpenAIClient::defaultSystemPrompt()
            . "\nTu generes des QCM pour la formation Katsuki Trading. Reponds UNIQUEMENT avec un tableau JSON valide, sans markdown.";

        $message = OpenAIClient::request($prompt, $system, 0.4, 1400);
        if (!$message) {
            echo json_encode([
                'questions' => self::fallbackQuestions($topic, $count),
                'warning'   => 'Service IA temporairement indisponible. Questions de secours affichees.'
            ]);
            return;
        }

        $decoded = OpenAIClient::extractJson($message);
        if (is_array($decoded)) {
            $out = ['questions' => $decoded];
            if (OpenAIClient::getLastModelUsed() && OpenAIClient::getLastModelUsed() !== OPENROUTER_MODEL) {
                $out['model'] = OpenAIClient::getLastModelUsed();
            }
            echo json_encode($out);
            return;
        }

        echo json_encode([
            'questions' => [['question' => $message, 'options' => [], 'correct_option' => '']],
            'warning'   => 'Reponse non JSON, texte brut renvoye.'
        ]);
    }

    // -------------------------------------------------------------------------
    // CONSTRUCTION DU CONTEXTE UTILISATEUR
    // -------------------------------------------------------------------------

    private static function buildUserContext(int $userId, string $level): string {
        $courses = Course::getAll();
        $catalog = [];
        foreach ($courses as $c) {
            $catalog[] = '- ' . $c['title'] . ' (' . $c['level'] . ') : ' . mb_substr($c['full_desc'] ?? '', 0, 120);
        }

        $lines = [
            'Catalogue Katsuki Trading :',
            implode("\n", $catalog),
            '',
            'Parcours : Genese du Trader (30 modules, debutant), Prop Firm Mastery (avance), Inner Circle Katsuki (elite).',
            '',
            'Programme Genese du Trader — Chapitres principaux :',
            'CH1 Lexique : Bourse, marche boursier, trading, trader, broker (direct/indirect), WellTrade, MetaTrader 5, spread.',
            'CH2 Marches : Forex (paires majeures/mineures), devises, indices reels vs synthetiques (Deriv), actions, crypto (Bitcoin, blockchain, minage, wallet), structure de marche (tendances, impulsions, corrections, theorie de Dow), supports/resistances forts/faibles, zones de demande/offre, range/accumulation, figures chartistes (double top, ETE, triangles, gaps, pullback), bougies japonaises (engulfing, doji, marteau, pin bar), time frames (MN/W1/D1/H4/H1/M30/M15/M5/M1), money management (R, SL, TP, drawdown, break-even, trailing stop, lots, martingale, grid trading), analyse technique et fondamentale.',
            'Contexte RDC : franc congolais, marches locaux de Kinshasa, Airtel Money, Binance P2P, cambistes, risque currency.',
        ];

        if ($level === 'admin') {
            $lines[] = 'Interlocuteur : administrateur pedagogique (creation de contenu, examens, analyse).';
            return implode("\n", $lines);
        }

        $user = User::findById($userId);
        if ($user) {
            $lines[] = 'Etudiant : ' . ($user['name'] ?? 'inconnu') . '.';
        }

        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT c.title AS course_title, m.title AS module_title, up.completed
               FROM user_progress up
               JOIN modules m ON m.id = up.module_id
               JOIN courses c ON c.id = m.course_id
              WHERE up.user_id = ?
              ORDER BY up.completed_at DESC
              LIMIT 8"
        );
        $stmt->execute([$userId]);
        $progress = $stmt->fetchAll();

        if ($progress) {
            $done    = count(array_filter($progress, fn($r) => (int)($r['completed'] ?? 0) === 1));
            $lines[] = 'Progression enregistree : ' . $done . ' module(s) termine(s) sur les derniers suivis.';
            foreach ($progress as $row) {
                $status  = (int)($row['completed'] ?? 0) ? 'termine' : 'en cours';
                $lines[] = '- ' . $status . ' : ' . $row['course_title'] . ' / ' . $row['module_title'];
            }
        }

        $lines[] = "Adapte tes explications au niveau de l'etudiant et aux formations Katsuki. Contextualise toujours par rapport a la RDC.";
        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // RECHERCHE DU MODULE CORRESPONDANT AU TOPIC
    // -------------------------------------------------------------------------

    /**
     * Cherche dans la base le contenu du module le plus pertinent pour un topic.
     * Retourne le contenu brut (TEXT) si trouvé, null sinon.
     */
    private static function findModuleByTopic(string $topic): ?string {
        try {
            $pdo  = getDB();
            // Recherche par mots-clés dans le titre du module (course_id = 1 = Genèse du Trader)
            $words   = array_filter(explode(' ', preg_replace('/[^a-zA-ZÀ-ÿ\s]/u', '', $topic)));
            $words   = array_slice($words, 0, 4); // max 4 mots-clés
            if (empty($words)) return null;

            $like    = '%' . implode('%', $words) . '%';
            $stmt    = $pdo->prepare(
                "SELECT content FROM modules
                  WHERE course_id = 1
                    AND (title LIKE ? OR content LIKE ?)
                  ORDER BY CHAR_LENGTH(title) ASC
                  LIMIT 1"
            );
            $stmt->execute([$like, $like]);
            $row = $stmt->fetch();
            return ($row && !empty($row['content']) && $row['content'] !== 'Contenu...')
                ? mb_substr($row['content'], 0, 1200)
                : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // CONSTRUCTION DES PROMPTS
    // -------------------------------------------------------------------------

    private static function buildLessonPrompt(
        string $topic,
        string $level,
        string $context,
        ?string $moduleRef
    ): string {
        $refSection = '';
        if ($moduleRef) {
            $refSection = "\n\nExtrait du module de reference Katsuki (base tes explications dessus, adapte et enrichis) :\n"
                . "---\n" . $moduleRef . "\n---\n";
        }

        return "Contexte formation Katsuki Trading :\n$context\n"
            . $refSection
            . "\nDemande de l'utilisateur (niveau '$level') : $topic\n\n"
            . "Redige une lecon pedagogique complete en francais avec la structure suivante :\n"
            . "1) Definition claire et simple\n"
            . "2) Explication detaillee avec exemples concrets contextualisés RDC (franc congolais, Kinshasa, realite locale)\n"
            . "3) Exemple chiffre ou cas pratique si applicable\n"
            . "4) Erreurs frequentes et comment les eviter\n"
            . "5) Lien avec la methode Katsuki (discipline, risque 1-2% max, plan de trading)\n"
            . "6) Conclusion actionnable (1-2 actions concretes a faire maintenant)\n"
            . "7) 3 questions de revision\n"
            . "Adapte la profondeur au niveau '$level'. Sois pedagogique, direct et concis.";
    }

    private static function buildQuestionsPrompt(
        string $topic,
        string $level,
        int $count,
        string $context
    ): string {
        return "Contexte formation Katsuki Trading (RDC) :\n$context\n\n"
            . "Genere exactement $count questions QCM en francais sur le sujet : $topic\n"
            . "Niveau cible : $level\n"
            . "Inclure des questions sur : definitions, exemples chiffres, erreurs a eviter, contexte RDC si pertinent.\n"
            . "Format JSON strict UNIQUEMENT (aucun texte avant ou apres) :\n"
            . '[{"question":"...","options":["A. ...","B. ...","C. ...","D. ..."],"correct_option":"A. ..."}]';
    }

    // -------------------------------------------------------------------------
    // FALLBACKS (si l'IA est indisponible)
    // -------------------------------------------------------------------------

    private static function fallbackLesson(string $topic, string $level): string {
        $topicLower = mb_strtolower($topic);

        // Réponses ciblées selon le sujet demandé
        if (str_contains($topicLower, 'bourse') || str_contains($topicLower, 'marche boursier')) {
            return "La Bourse (mode hors-ligne)\n\n"
                . "La Bourse est un marche organise ou acheteurs et vendeurs echangent des titres financiers (actions, devises, indices) a des prix fixes par l'offre et la demande.\n\n"
                . "Exemple RDC : comme le Grand Marche de Kinshasa, mais pour les actifs financiers mondiaux. Le prix monte si tout le monde veut acheter, descend si personne n'achete.\n\n"
                . "Roles : financer les entreprises, investir son epargne, fixer les prix, transferer le risque.\n\n"
                . "En RDC, aucune bourse nationale operationnelle — les Congolais accedent aux marches mondiaux via internet et des brokers en ligne.\n\n"
                . "Questions de revision :\n- Quelle est la difference entre une bourse et un marche local ?\n- Pourquoi la RDC n'a-t-elle pas encore de bourse nationale ?\n- Comment un Congolais peut-il acceder aux marches financiers mondiaux ?";
        }

        if (str_contains($topicLower, 'spread')) {
            return "Le Spread (mode hors-ligne)\n\n"
                . "Le spread est la difference entre le prix d'achat (Ask) et le prix de vente (Bid). C'est la remuneration implicite du broker.\n\n"
                . "Exemple RDC : le cambiste achete a 2 800 FC/USD et revend a 2 850 FC — spread = 50 FC.\n\n"
                . "Sur MT5 EUR/USD : Bid 1.0850 / Ask 1.0852 = spread 2 pips.\n\n"
                . "Impact : a l'ouverture d'un trade, tu es deja en legere perte du montant du spread. Avec 0.01 lot et 2 pips = -0.20$.\n\n"
                . "Bonne pratique : choisir un broker a faible spread, eviter les trades pendant les grosses annonces (spread peut tripler).";
        }

        if (str_contains($topicLower, 'lot') || str_contains($topicLower, 'money management') || str_contains($topicLower, 'risque')) {
            return "Money Management — Les Lots (mode hors-ligne)\n\n"
                . "Un lot est l'unite de mesure d'une position :\n"
                . "- 1 lot standard = 100 000 unites (~10$ par pip EUR/USD)\n"
                . "- 0.10 lot = ~1$ par pip\n"
                . "- 0.01 lot (micro) = ~0.10$ par pip\n\n"
                . "Calcul du lot (regle Katsuki) :\n"
                . "Lot = (Capital x Risque%) / (SL en pips x valeur du pip)\n\n"
                . "Exemple — compte 100$, risque 1%, SL 15 pips :\n"
                . "Lot = (100 x 0.01) / (15 x 0.10) = 1 / 1.50 = 0.007 → utiliser 0.01 lot.\n\n"
                . "Regle absolue : ne jamais risquer plus de 1-2% du capital par trade.";
        }

        if (str_contains($topicLower, 'broker') || str_contains($topicLower, 'welltrade') || str_contains($topicLower, 'metatrader')) {
            return "Broker et MetaTrader 5 (mode hors-ligne)\n\n"
                . "Un broker est l'intermediaire entre toi et le marche. WellTrade est un broker accessible depuis la RDC.\n\n"
                . "MetaTrader 5 est le logiciel de trading (graphiques + ordres). Le broker detient ton argent ; MT5 est l'interface.\n\n"
                . "Inscription WellTrade : welltrade.com → Ouvrir un compte → choisir Demo pour commencer → verifier email → upload piece d'identite.\n\n"
                . "Connexion MT5 : Fichier → Ouvrir un compte → chercher WellTrade → entrer numero de compte et mot de passe recus par email.\n\n"
                . "Broker Direct (ECN) : ordres envoyes au marche reel, spread variable, pas de conflit d'interet.\n"
                . "Broker Indirect (Market Maker) : traite les ordres en interne, peut etre contrepartie. WellTrade = Market Maker.";
        }

        if (str_contains($topicLower, 'bougie') || str_contains($topicLower, 'chandel') || str_contains($topicLower, 'doji') || str_contains($topicLower, 'engulfing')) {
            return "Bougies Japonaises (mode hors-ligne)\n\n"
                . "Chaque bougie = 4 donnees : Ouverture, Fermeture, Haut, Bas.\n\n"
                . "Bougie standard (Marabozu) : corps long, peu de meches → force directionnelle.\n"
                . "Engulfing : avale la bougie precedente → retournement. Bullish = vert avale rouge. Bearish = inverse.\n"
                . "Doji : corps minuscule → indecision, possible retournement.\n"
                . "Marteau (Hammer) : corps haut, longue meche basse → signal haussier apres baisse.\n"
                . "Pin Bar : longue meche d'un cote → rejet fort d'un niveau.\n\n"
                . "Regle : une bougie ne se lit jamais seule. Elle prend du sens dans son contexte (tendance + niveau).";
        }

        if (str_contains($topicLower, 'time frame') || str_contains($topicLower, 'timeframe')) {
            return "Les Time Frames (mode hors-ligne)\n\n"
                . "MN (mois) > W1 (semaine) > D1 (jour) > H4 > H1 > M30 > M15 > M5 > M1\n\n"
                . "Correspondances :\n"
                . "1 bougie D1 = 6 bougies H4 = 24 bougies H1 = 48 bougies M30 = 96 bougies M15 = 288 bougies M5 = 1 440 bougies M1\n"
                . "1 bougie W1 = 5 bougies D1 = 120 bougies H1 = 1 440 bougies M5\n\n"
                . "Regle des 3 TF (Top-Down) :\n"
                . "1. Grand TF (D1) → direction generale\n"
                . "2. TF moyen (H1) → zone d'entree\n"
                . "3. Petit TF (M15) → confirmation de l'entree\n\n"
                . "Meilleur moment depuis Kinshasa : 09h00–18h00 (session Londres + New York).";
        }

        if (str_contains($topicLower, 'crypto') || str_contains($topicLower, 'bitcoin') || str_contains($topicLower, 'blockchain')) {
            return "Crypto et Blockchain (mode hors-ligne)\n\n"
                . "Cryptomonnaie : monnaie numerique decentralisee, aucune banque centrale ne la controle.\n"
                . "Bitcoin : premiere crypto (2009, Satoshi Nakamoto). Plafond = 21 millions de BTC. Utilisee comme reserve de valeur.\n"
                . "Blockchain : registre public, immuable, decentralise. Chaque transaction verifiee par le reseau et inscrite dans un bloc enchaine.\n"
                . "Minage : ordinateurs qui valident les transactions et recoivent des BTC en recompense.\n"
                . "Wallet : application (Binance, Trust Wallet) qui stocke tes cles cryptographiques.\n\n"
                . "Contexte RDC : l'USDT (stablecoin = 1$ fixe) est utilise pour epargner en dollars et eviter les frais Western Union. "
                . "Binance P2P permet d'acheter des crypto en CDF via Airtel Money.";
        }

        // Fallback générique enrichi
        return "Cours Katsuki — $topic (niveau $level)\n\n"
            . "1. Definition : concept fondamental du trading a maitriser absolument.\n"
            . "2. Application RDC : comme le cambiste a Kinshasa, chaque concept du trading a un equivalent local.\n"
            . "3. Gestion du risque : toujours limiter a 1-2% du capital par trade.\n"
            . "4. Psychologie : suivre son plan, eviter le revenge trading et le FOMO.\n"
            . "5. Pratique : ouvrir MT5, analyser EUR/USD sur D1, identifier la tendance.\n\n"
            . "Questions de revision :\n"
            . "- Pourquoi limiter le risque par position a 1-2% ?\n"
            . "- Quel est le role d'un plan de trading ?\n"
            . "- Comment gerer une serie de pertes sans revenge trading ?";
    }

    private static function fallbackQuestions(string $topic, int $count): array {
        $bank = [
            // Lexique
            ['question' => 'Quelle est la difference principale entre un broker ECN et un Market Maker ?',
             'options'  => ['A. Le broker ECN garde ta mise en cas de perte', 'B. Le broker ECN envoie tes ordres au vrai marche sans etre ta contrepartie', 'C. Le Market Maker a toujours un spread plus bas', 'D. Il n\'y a aucune difference pratique'],
             'correct_option' => 'B. Le broker ECN envoie tes ordres au vrai marche sans etre ta contrepartie'],

            ['question' => 'Sur MT5, EUR/USD affiche Bid 1.0850 / Ask 1.0852. Quel est le spread en pips ?',
             'options'  => ['A. 1 pip', 'B. 2 pips', 'C. 5 pips', 'D. 0.5 pip'],
             'correct_option' => 'B. 2 pips'],

            ['question' => 'Quel risque maximal par trade recommande la methode Katsuki ?',
             'options'  => ['A. 10% du capital', 'B. 5% du capital', 'C. 1 a 2% du capital', 'D. 25% du capital'],
             'correct_option' => 'C. 1 a 2% du capital'],

            // Money Management
            ['question' => 'Tu as un compte de 200$. Tu risques 1% avec un SL de 20 pips sur EUR/USD (0.10$/pip pour 0.01 lot). Quel lot utilises-tu ?',
             'options'  => ['A. 0.01 lot', 'B. 0.10 lot', 'C. 1 lot', 'D. 0.001 lot'],
             'correct_option' => 'A. 0.01 lot'],

            ['question' => 'Qu\'est-ce que le break-even en trading ?',
             'options'  => ['A. Fermer un trade en perte maximale', 'B. Deplacer le stop loss au prix d\'entree pour risque zero', 'C. Doubler la position apres une perte', 'D. Attendre que le trade atteigne le TP'],
             'correct_option' => 'B. Deplacer le stop loss au prix d\'entree pour risque zero'],

            ['question' => 'Quel est le danger principal de la strategie Martingale ?',
             'options'  => ['A. Elle ne fonctionne qu\'en tendance', 'B. Quelques pertes consecutives peuvent exiger un capital enorme et ruiner le compte', 'C. Elle est interdite par les brokers', 'D. Elle necessite un compte de minimum 10 000$'],
             'correct_option' => 'B. Quelques pertes consecutives peuvent exiger un capital enorme et ruiner le compte'],

            // Structure de marché
            ['question' => 'Qu\'est-ce qu\'un support fort par opposition a un support faible ?',
             'options'  => ['A. Un support sur lequel le prix n\'a jamais rebondi', 'B. Un support teste plusieurs fois avec des rebonds puissants, visible sur les grands time frames', 'C. Un support uniquement sur M1', 'D. Un support cree par un indicateur RSI'],
             'correct_option' => 'B. Un support teste plusieurs fois avec des rebonds puissants, visible sur les grands time frames'],

            ['question' => 'Qu\'est-ce qu\'un retest apres cassure de resistance ?',
             'options'  => ['A. Le prix continue sa hausse sans revenir', 'B. Le prix revient toucher l\'ancienne resistance (devenue support) avant de repartir a la hausse', 'C. Le prix revient au point de depart', 'D. Une figure en double top'],
             'correct_option' => 'B. Le prix revient toucher l\'ancienne resistance (devenue support) avant de repartir a la hausse'],

            // Bougies
            ['question' => 'Qu\'indique un Bullish Engulfing apres une tendance baissiere ?',
             'options'  => ['A. Continuation de la baisse', 'B. Indecision totale du marche', 'C. Signal de retournement haussier potentiel', 'D. Une zone de distribution'],
             'correct_option' => 'C. Signal de retournement haussier potentiel'],

            // Time frames
            ['question' => 'Combien de bougies H1 contient une bougie D1 ?',
             'options'  => ['A. 12', 'B. 24', 'C. 48', 'D. 60'],
             'correct_option' => 'B. 24'],

            // Forex / Crypto
            ['question' => 'Combien de paires majeures existe-t-il sur le marche Forex ?',
             'options'  => ['A. 4', 'B. 5', 'C. 7', 'D. 12'],
             'correct_option' => 'C. 7'],

            ['question' => 'Quelle est la grande difference entre un indice synthetique (Deriv) et un indice reel comme le S&P 500 ?',
             'options'  => ['A. L\'indice synthetique suit les cours des actions americaines', 'B. L\'indice synthetique est genere par algorithme, disponible 24h/24 7j/7, sans lien avec l\'economie reelle', 'C. L\'indice reel est disponible uniquement le week-end', 'D. Il n\'y a aucune difference pratique'],
             'correct_option' => 'B. L\'indice synthetique est genere par algorithme, disponible 24h/24 7j/7, sans lien avec l\'economie reelle'],

            // Psychologie
            ['question' => 'Qu\'est-ce que le FOMO en trading ?',
             'options'  => ['A. Une strategie de scalping sur M1', 'B. La peur de rater un trade qui pousse a entrer trop tard dans un mouvement', 'C. Un indicateur de volume', 'D. La regle des 2% de risque'],
             'correct_option' => 'B. La peur de rater un trade qui pousse a entrer trop tard dans un mouvement'],

            ['question' => 'Pourquoi tenir un journal de trading ?',
             'options'  => ['A. Pour montrer ses performances a sa famille', 'B. Pour identifier les erreurs repetitives et ameliorer sa strategie sur la duree', 'C. Pour augmenter le levier progressivement', 'D. Pour eviter de payer des spreads'],
             'correct_option' => 'B. Pour identifier les erreurs repetitives et ameliorer sa strategie sur la duree'],
        ];

        $questions = [];
        for ($i = 0; $i < $count; $i++) {
            $questions[] = $bank[$i % count($bank)];
        }
        return $questions;
    }
}
