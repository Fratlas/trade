<?php
declare(strict_types=1);

/**
 * Copy trading foundation. Simulation only: NO broker orders or fund movements.
 * All endpoints require a validated JWT. User identifiers come from the token.
 */
class CopyTradingController {
    private static function respond($data, int $status = 200): void {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    public static function traders(): void {
        $db = getDB();
        $stmt = $db->query("SELECT t.id, t.display_name, t.bio, t.status, COUNT(s.id) AS followers
          FROM copy_traders t LEFT JOIN copy_subscriptions s ON s.trader_id=t.id AND s.status='active'
          GROUP BY t.id,t.display_name,t.bio,t.status ORDER BY followers DESC,t.id ASC");
        self::respond(['mode'=>'simulation','traders'=>$stmt->fetchAll()]);
    }
    public static function mySubscriptions(int $userId): void {
        $db = getDB();
        $stmt = $db->prepare("SELECT s.id,s.trader_id,t.display_name,s.allocation_percent,s.max_loss_percent,s.status,s.created_at
          FROM copy_subscriptions s JOIN copy_traders t ON t.id=s.trader_id WHERE s.follower_user_id=? ORDER BY s.id DESC");
        $stmt->execute([$userId]);
        self::respond(['mode'=>'simulation','subscriptions'=>$stmt->fetchAll()]);
    }
    public static function follow(int $userId, array $data): void {
        $traderId = filter_var($data['trader_id'] ?? null, FILTER_VALIDATE_INT);
        $allocation = filter_var($data['allocation_percent'] ?? null, FILTER_VALIDATE_FLOAT);
        $limit = filter_var($data['max_loss_percent'] ?? null, FILTER_VALIDATE_FLOAT);
        if (!$traderId || $traderId < 1 || $allocation === false || $allocation <= 0 || $allocation > 100
            || $limit === false || $limit <= 0 || $limit > 100 || ($data['risk_consent'] ?? false) !== true) {
            self::respond(['error'=>'trader_id, allocation_percent (0-100), max_loss_percent (0-100) et consentement explicite requis'],422);
            return;
        }
        $db = getDB();
        $q = $db->prepare("SELECT id FROM copy_traders WHERE id=? AND user_id<>? AND status='active'");
        $q->execute([$traderId,$userId]);
        if (!$q->fetch()) { self::respond(['error'=>'Trader indisponible ou auto-abonnement interdit'],404); return; }
        $q=$db->prepare("INSERT INTO copy_subscriptions (follower_user_id,trader_id,allocation_percent,max_loss_percent,status)
          VALUES (?,?,?,?,'paused')
          ON DUPLICATE KEY UPDATE allocation_percent=VALUES(allocation_percent),max_loss_percent=VALUES(max_loss_percent),status='paused',consent_at=CURRENT_TIMESTAMP");
        $q->execute([$userId,$traderId,$allocation,$limit]);
        self::respond(['success'=>true,'status'=>'paused','mode'=>'simulation','message'=>'Abonnement enregistré et suspendu par défaut. Aucune opération réelle.'],201);
    }
    public static function setStatus(int $userId, int $subscriptionId, array $data): void {
        $status = $data['status'] ?? null;
        if (!in_array($status,['paused','active'],true)) { self::respond(['error'=>'status doit être paused ou active'],422); return; }
        $db=getDB();
        $q=$db->prepare("UPDATE copy_subscriptions s JOIN copy_traders t ON t.id=s.trader_id
          SET s.status=? WHERE s.id=? AND s.follower_user_id=? AND (?='paused' OR t.status='active')");
        $q->execute([$status,$subscriptionId,$userId,$status]);
        if (!$q->rowCount()) {
            $check=$db->prepare("SELECT s.id,s.status,t.status AS trader_status FROM copy_subscriptions s JOIN copy_traders t ON t.id=s.trader_id WHERE s.id=? AND s.follower_user_id=?");
            $check->execute([$subscriptionId,$userId]);
            $existing=$check->fetch();
            if (!$existing) { self::respond(['error'=>'Abonnement introuvable'],404); return; }
            if ($status==='active' && $existing['trader_status']!=='active') { self::respond(['error'=>'Trader suspendu'],409); return; }
        }
        self::respond(['success'=>true,'status'=>$status,'mode'=>'simulation']);
    }
    public static function events(int $userId): void {
        $db=getDB();
        $q=$db->prepare("SELECT e.id,e.trader_id,e.symbol,e.direction,e.entry_price,e.stop_loss,e.take_profit,e.created_at
          FROM copy_simulated_events e JOIN copy_subscriptions s ON s.trader_id=e.trader_id
          WHERE s.follower_user_id=? ORDER BY e.id DESC LIMIT 100");
        $q->execute([$userId]);
        self::respond(['mode'=>'simulation','events'=>$q->fetchAll()]);
    }
}
