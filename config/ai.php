<?php
/**
 * OpenRouter : Qwen principal, Gemma gratuit en secours.
 * Variables d env : OPENROUTER_API_KEY, OPENROUTER_MODEL, OPENROUTER_MODEL_FALLBACK
 */
define('OPENROUTER_API_KEY', getenv('OPENROUTER_API_KEY') ?: '');
define('OPENROUTER_MODEL', getenv('OPENROUTER_MODEL') ?: 'qwen/qwen-2.5-7b-instruct');
define('OPENROUTER_MODEL_FALLBACK', getenv('OPENROUTER_MODEL_FALLBACK') ?: 'google/gemma-4-26b-a4b-it:free');
define('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1/chat/completions');
define('OPENROUTER_SITE_URL', 'https://projet.stockflowrdc.com');
define('OPENROUTER_APP_NAME', 'Katsuki Trading');

/**
 * OpenAI direct (requis pour l'API Realtime / WebRTC vocal).
 * Definir OPENAI_API_KEY dans les variables d'environnement du serveur.
 */
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
