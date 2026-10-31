<?php
require_once __DIR__ . '/../../config/ai.php';

class OpenAIClient {
    private static $lastModelUsed = null;

    public static function request(
        string $prompt,
        string $systemPrompt = null,
        float $temperature = 0.7,
        int $maxTokens = 1200
    ) {
        $apiKey = OPENROUTER_API_KEY;
        if (!$apiKey) {
            return null;
        }

        $system = $systemPrompt ?: self::defaultSystemPrompt();
        $models = array_values(array_unique(array_filter([
            OPENROUTER_MODEL,
            OPENROUTER_MODEL_FALLBACK
        ])));

        foreach ($models as $model) {
            $result = self::callModel($apiKey, $model, $system, $prompt, $temperature, $maxTokens);
            if ($result !== null) {
                self::$lastModelUsed = $model;
                if ($model !== OPENROUTER_MODEL) {
                    error_log('OpenRouter fallback utilise : ' . $model);
                }
                return $result;
            }
        }

        self::$lastModelUsed = null;
        return null;
    }

    public static function getLastModelUsed(): ?string {
        return self::$lastModelUsed;
    }

    private static function callModel(
        string $apiKey,
        string $model,
        string $system,
        string $prompt,
        float $temperature,
        int $maxTokens
    ): ?string {
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ]);

        $ch = curl_init(OPENROUTER_BASE_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer: ' . OPENROUTER_SITE_URL,
                'X-Title: ' . OPENROUTER_APP_NAME
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('OpenRouter curl error (' . $model . '): ' . $curlError);
            return null;
        }

        if ($httpCode >= 400) {
            error_log('OpenRouter HTTP ' . $httpCode . ' (' . $model . '): ' . (string)$response);
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['choices'][0]['message']['content'])) {
            error_log('OpenRouter reponse invalide (' . $model . '): ' . (string)$response);
            return null;
        }

        $content = trim($data['choices'][0]['message']['content']);
        if ($content === '') {
            error_log('OpenRouter contenu vide (' . $model . ')');
            return null;
        }

        return $content;
    }

    public static function defaultSystemPrompt(): string {
        return "Tu es l'assistant pedagogique officiel de Katsuki Trading, plateforme de formation au trading basee en RDC (Republique Democratique du Congo). "
            . "Tu enseignes avec rigueur : lexique du trading, structure de marche, types de marches (Forex, indices, crypto, actions), "
            . "lecture des bougies japonaises, time frames, money management strict, psychologie du trader, analyse technique et fondamentale, Prop Firms. "
            . "Tu contextualises toujours tes explications par rapport a la RDC (franc congolais, marche de Kinshasa, Airtel Money, realite locale). "
            . "Tu connais le programme 'Genese du Trader' (30 modules couvrant : lexique, broker WellTrade, MetaTrader 5, Forex, indices, crypto, structure, bougies, time frames, money management). "
            . "Reponds toujours en francais, de maniere claire, structuree et actionnable. "
            . "Ne donne jamais de conseil financier personnalise ni de promesse de gain. "
            . "Rappelle systematiquement la gestion du risque (1-2% max par trade) quand c'est pertinent.";
    }

    public static function extractJson(string $text) {
        $text = trim($text);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $text, $m)) {
            $text = trim($m[1]);
        }
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : null;
    }
}
