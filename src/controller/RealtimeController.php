<?php
/**
 * RealtimeController — crée une session éphémère OpenAI Realtime API.
 *
 * Le token côté client (client_secret) est valide 60 secondes et permet au
 * navigateur de se connecter directement à OpenAI via WebRTC sans exposer
 * la clé principale OPENAI_API_KEY.
 *
 * Route : POST /api/ai/realtime-session
 */
require_once __DIR__ . '/../../config/ai.php';

class RealtimeController
{
    private const REALTIME_SESSIONS_URL = 'https://api.openai.com/v1/realtime/sessions';
    private const REALTIME_MODEL        = 'gpt-4o-realtime-preview-2024-10-01';

    /**
     * Crée une session Realtime et renvoie le client_secret au navigateur.
     *
     * @param int    $userId  ID de l'utilisateur authentifié
     * @param string $role    'admin' | 'student'
     */
    public static function createSession(int $userId, string $role = 'student'): void
    {
        // Vérification de la clé API
        $apiKey = OPENAI_API_KEY;
        if (!$apiKey) {
            http_response_code(503);
            echo json_encode([
                'error' => 'Clé OpenAI non configurée. Définissez OPENAI_API_KEY sur le serveur.'
            ]);
            return;
        }

        $instructions = $role === 'admin'
            ? "Tu es l'assistant administratif expert de Katsuki Trading, plateforme de formation au trading basée en RDC. "
              . "Tu maîtrises le programme complet : Chapitre 1 (Lexique : bourse, trader, broker WellTrade, MetaTrader 5, spread, broker direct/indirect) "
              . "et Chapitre 2 (Marchés : Forex, paires majeures/mineures, indices réels vs synthétiques Deriv, crypto/Bitcoin/blockchain, "
              . "structure de marché, supports/résistances, bougies japonaises, time frames, money management, analyse technique et fondamentale). "
              . "Tu aides l'équipe pédagogique à créer du contenu, générer des examens et analyser les performances étudiantes. "
              . "Réponds de manière concise, structurée et dynamique en français. "
              . "Ne donne jamais de conseil financier personnalisé ni de promesse de gain."
            : "Tu es l'assistant pédagogique officiel de Katsuki Trading, coach de trading personnel pour les étudiants de RDC. "
              . "Tu maîtrises le programme Genèse du Trader (30 modules) : "
              . "Chapitre 1 — Lexique : bourse (comparaison Grand Marché Kinshasa), trading (comparaison cambiste CDF/USD), trader, broker, "
              . "WellTrade, MetaTrader 5, spread, broker direct ECN vs indirect Market Maker. "
              . "Chapitre 2 — Marchés : Forex (7 paires majeures, paires mineures), devises, indices réels (S&P 500, DAX) vs synthétiques Deriv "
              . "(Volatility 75, Boom/Crash), actions, crypto (Bitcoin, blockchain, minage, wallet, USDT/Binance P2P en RDC), "
              . "structure de marché (tendances HH/HL/LH/LL, impulsions, corrections, théorie de Dow, fractalité), "
              . "supports/résistances forts et faibles, zones de demande/offre, range/accumulation, distribution, "
              . "figures chartistes (double top/bottom, ETE, triangles, gap, pullback), "
              . "bougies (engulfing, doji, marteau, pin bar, marabozu), "
              . "time frames (MN/W1/D1/H4/H1/M30/M15/M5/M1 et leurs correspondances), "
              . "money management (R ratio, stop loss, take profit, drawdown, break-even, trailing stop, "
              . "lots micro/mini/standard, calcul du lot, martingale, grid trading, full marge vs risque strict), "
              . "analyse technique et fondamentale. "
              . "Contextualise TOUJOURS tes réponses par rapport à la RDC (franc congolais, Airtel Money, réalité locale). "
              . "Réponds en français, de manière claire, naturelle et actionnable. "
              . "Risque max recommandé : 1-2% par trade. "
              . "Ne donne jamais de conseil financier personnalisé ni de promesse de gain.";

        $payload = json_encode([
            'model'        => self::REALTIME_MODEL,
            'voice'        => 'alloy',
            'modalities'   => ['audio', 'text'],
            'instructions' => $instructions,
        ]);

        $ch = curl_init(self::REALTIME_SESSIONS_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('RealtimeController cURL error: ' . $curlError);
            http_response_code(502);
            echo json_encode(['error' => 'Impossible de contacter OpenAI: ' . $curlError]);
            return;
        }

        if ($httpCode >= 400) {
            error_log('RealtimeController HTTP ' . $httpCode . ': ' . $response);
            http_response_code($httpCode >= 500 ? 502 : $httpCode);
            $body = json_decode($response, true);
            echo json_encode([
                'error' => ($body['error']['message'] ?? 'Erreur OpenAI HTTP ' . $httpCode),
            ]);
            return;
        }

        $data = json_decode($response, true);
        if (
            !is_array($data)
            || empty($data['client_secret']['value'])
        ) {
            error_log('RealtimeController réponse inattendue: ' . $response);
            http_response_code(502);
            echo json_encode(['error' => 'Réponse inattendue de OpenAI Realtime API.']);
            return;
        }

        // On renvoie uniquement les informations nécessaires au client
        echo json_encode([
            'client_secret' => $data['client_secret']['value'],
            'expires_at'    => $data['client_secret']['expires_at'] ?? null,
            'session_id'    => $data['id'] ?? null,
        ]);
    }
}
