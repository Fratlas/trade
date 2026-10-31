<?php
declare(strict_types=1);

/** Copy trading API, simulation only. No broker orders are sent. */
class CopyTradingController {
    private static function respond(array $data, int $status = 200): void {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public static function traders(): void {
        $db = getDB();
        $stmt = $db->query("SELECT t.id, t.display_name, t.bio,
                COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.id END) AS followers,
                COUNT(DISTINCT e.id) AS signals_count
            FROM copy_traders t
            LEFT JOIN copy_subscriptions s ON s.trader_id = t.id
            LEFT JOIN copy_signals e ON e.trader_id = t.id
            WHERE t.status = 'active'
            GROUP BY t.id, t.display_name, t.bio
            ORDER BY followers DESC, t.id ASC");
        self::respond(['mode' => 'simulation', 'traders' => $stmt->fetchAll()]);
    }

    public static function myProfile(int $userId): void {
        $stmt = getDB()->prepare('SELECT id, display_name, bio, status, created_at FROM copy_traders WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        self::respond(['mode' => 'simulation', 'profile' => $stmt->fetch() ?: null]);
    }

    public static function applyAsTrader(int $userId, array $data): void {
        $name = trim((string)($data['display_name'] ?? ''));
        $bio = trim((string)($data['bio'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || mb_strlen($bio) > 500) {
            self::respond(['error' => 'Nom requis (2 à 100 caractères); présentation limitée à 500 caractères.'], 422);
            return;
        }
        $db = getDB();
        $exists = $db->prepare('SELECT id, status FROM copy_traders WHERE user_id = ? LIMIT 1');
        $exists->execute([$userId]);
        $current = $exists->fetch();
        if ($current) {
            self::respond(['error' => 'Une demande ou un profil existe déjà.', 'profile_status' => $current['status']], 409);
            return;
        }
        $stmt = $db->prepare("INSERT INTO copy_traders (user_id, display_name, bio, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$userId, $name, $bio]);
        self::respond(['success' => true, 'status' => 'pending', 'message' => 'Demande envoyée. Le profil sera visible après validation administrateur.'], 201);
    }

    public static function moderateTrader(int $traderId, array $data): void {
        $status = $data['status'] ?? null;
        if (!in_array($status, ['active', 'paused', 'rejected'], true)) {
            self::respond(['error' => 'Statut non valide.'], 422);
            return;
        }
        $stmt = getDB()->prepare('UPDATE copy_traders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $traderId]);
        if (!$stmt->rowCount()) {
            self::respond(['error' => 'Profil maître introuvable ou statut inchangé.'], 404);
            return;
        }
        self::respond(['success' => true, 'status' => $status]);
    }

    public static function subscriptions(int $userId): void {
        $stmt = getDB()->prepare("SELECT s.id, s.trader_id, t.display_name, s.allocation_percent,
                s.max_loss_percent, s.status, s.consent_at, s.created_at
            FROM copy_subscriptions s
            JOIN copy_traders t ON t.id = s.trader_id
            WHERE s.follower_user_id = ?
            ORDER BY s.id DESC");
        $stmt->execute([$userId]);
        self::respond(['mode' => 'simulation', 'subscriptions' => $stmt->fetchAll()]);
    }

    public static function follow(int $userId, array $data): void {
        $traderId = filter_var($data['trader_id'] ?? null, FILTER_VALIDATE_INT);
        $allocation = filter_var($data['allocation_percent'] ?? null, FILTER_VALIDATE_FLOAT);
        $lossLimit = filter_var($data['max_loss_percent'] ?? null, FILTER_VALIDATE_FLOAT);
        if (!$traderId || $traderId < 1 || $allocation === false || $allocation <= 0 || $allocation > 100
            || $lossLimit === false || $lossLimit <= 0 || $lossLimit > 100 || ($data['risk_consent'] ?? false) !== true) {
            self::respond(['error' => 'Choisissez un trader, une allocation et une limite de perte valides, puis confirmez le consentement.'], 422);
            return;
        }
        $db = getDB();
        $check = $db->prepare("SELECT id FROM copy_traders WHERE id = ? AND user_id <> ? AND status = 'active'");
        $check->execute([$traderId, $userId]);
        if (!$check->fetch()) {
            self::respond(['error' => 'Trader indisponible ou abonnement à son propre profil interdit.'], 404);
            return;
        }
        $stmt = $db->prepare("INSERT INTO copy_subscriptions (follower_user_id, trader_id, allocation_percent, max_loss_percent, status)
            VALUES (?, ?, ?, ?, 'paused')
            ON DUPLICATE KEY UPDATE allocation_percent = VALUES(allocation_percent),
                max_loss_percent = VALUES(max_loss_percent), status = 'paused', consent_at = CURRENT_TIMESTAMP");
        $stmt->execute([$userId, $traderId, $allocation, $lossLimit]);
        self::respond(['success' => true, 'status' => 'paused', 'mode' => 'simulation', 'message' => 'Abonnement créé en pause. Activez-le après vérification des paramètres.'], 201);
    }

    public static function setSubscriptionStatus(int $userId, int $subscriptionId, array $data): void {
        $status = $data['status'] ?? null;
        if (!in_array($status, ['active', 'paused'], true)) {
            self::respond(['error' => 'Le statut doit être active ou paused.'], 422);
            return;
        }
        $stmt = getDB()->prepare("UPDATE copy_subscriptions s
            JOIN copy_traders t ON t.id = s.trader_id
            SET s.status = ?
            WHERE s.id = ? AND s.follower_user_id = ? AND (? = 'paused' OR t.status = 'active')");
        $stmt->execute([$status, $subscriptionId, $userId, $status]);
        if (!$stmt->rowCount()) {
            self::respond(['error' => 'Abonnement introuvable ou trader suspendu.'], 404);
            return;
        }
        self::respond(['success' => true, 'status' => $status, 'mode' => 'simulation']);
    }

    public static function publishSignal(int $userId, array $data): void {
        $eventId = trim((string)($data['client_event_id'] ?? ''));
        $positionRef = trim((string)($data['position_ref'] ?? ''));
        $eventType = $data['event_type'] ?? 'open';
        $symbol = strtoupper(trim((string)($data['symbol'] ?? '')));
        $direction = $data['direction'] ?? '';
        $entry = self::optionalPositiveNumber($data['entry_price'] ?? null);
        $stop = self::optionalPositiveNumber($data['stop_loss'] ?? null);
        $target = self::optionalPositiveNumber($data['take_profit'] ?? null);
        $valid = preg_match('/^[A-Za-z0-9_-]{8,80}$/', $eventId)
            && preg_match('/^[A-Za-z0-9_.:-]{1,80}$/', $positionRef)
            && preg_match('/^[A-Z0-9._-]{2,30}$/', $symbol)
            && in_array($eventType, ['open', 'modify', 'close'], true)
            && in_array($direction, ['buy', 'sell'], true);
        if ($eventType === 'open') {
            $valid = $valid && $entry !== null && $stop !== null && $target !== null
                && ($direction === 'buy' ? ($stop < $entry && $entry < $target) : ($target < $entry && $entry < $stop));
        } else {
            $valid = $valid && ($entry !== false) && ($stop !== false) && ($target !== false);
        }
        if (!$valid) {
            self::respond(['error' => 'Événement invalide. Vérifiez identifiant, position, symbole, sens et niveaux de prix.'], 422);
            return;
        }
        $db = getDB();
        $master = $db->prepare("SELECT id FROM copy_traders WHERE user_id = ? AND status = 'active' LIMIT 1");
        $master->execute([$userId]);
        $trader = $master->fetch();
        if (!$trader) {
            self::respond(['error' => 'Un profil maître actif est nécessaire pour publier un signal.'], 403);
            return;
        }
        $stmt = $db->prepare("INSERT INTO copy_signals
            (trader_id, client_event_id, position_ref, event_type, symbol, direction, entry_price, stop_loss, take_profit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
        $stmt->execute([$trader['id'], $eventId, $positionRef, $eventType, $symbol, $direction, $entry, $stop, $target]);
        self::respond(['success' => true, 'mode' => 'simulation', 'signal_id' => (int)$db->lastInsertId(), 'message' => 'Signal simulé enregistré; aucun ordre broker exécuté.'], 201);
    }

    public static function signals(int $userId): void {
        $stmt = getDB()->prepare("SELECT e.id, e.client_event_id, e.position_ref, e.event_type, e.symbol,
                e.direction, e.entry_price, e.stop_loss, e.take_profit, e.created_at, t.display_name
            FROM copy_signals e
            JOIN copy_traders t ON t.id = e.trader_id
            JOIN copy_subscriptions s ON s.trader_id = e.trader_id
            WHERE s.follower_user_id = ? AND e.created_at >= s.consent_at
            ORDER BY e.id DESC LIMIT 100");
        $stmt->execute([$userId]);
        self::respond(['mode' => 'simulation', 'signals' => $stmt->fetchAll()]);
    }

    private static function optionalPositiveNumber($value) {
        if ($value === null || $value === '') return null;
        $number = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($number === false || !is_finite((float)$number) || $number <= 0) return false;
        return (float)$number;
    }
}
