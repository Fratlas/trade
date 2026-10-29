<?php
declare(strict_types=1);

/** Publish educational market simulations. Never places broker orders. */
class SimulationController {
    private static function answer(array $body,int $status=200): void {
        http_response_code($status);
        echo json_encode($body,JSON_UNESCAPED_UNICODE);
    }
    public static function publish(int $userId,array $data): void {
        $symbol=strtoupper(trim((string)($data['symbol']??'')));
        $side=$data['direction']??'';
        $externalId=trim((string)($data['client_event_id']??''));
        $entry=filter_var($data['entry_price']??null,FILTER_VALIDATE_FLOAT);
        $stop=filter_var($data['stop_loss']??null,FILTER_VALIDATE_FLOAT);
        $target=filter_var($data['take_profit']??null,FILTER_VALIDATE_FLOAT);
        if (!preg_match('/^[A-Z0-9._-]{2,30}$/',$symbol)
            || !in_array($side,['buy','sell'],true)
            || !preg_match('/^[A-Za-z0-9_-]{8,80}$/',$externalId)
            || $entry===false || $stop===false || $target===false
            || !is_finite($entry) || !is_finite($stop) || !is_finite($target)
            || $entry<=0 || $stop<=0 || $target<=0
            || ($side==='buy' && !($stop<$entry && $entry<$target))
            || ($side==='sell' && !($target<$entry && $entry<$stop))) {
            self::answer(['error'=>'Symbole, direction, identifiant unique ou prix invalides'],422);
            return;
        }
        $db=getDB();
        $q=$db->prepare("SELECT id FROM copy_traders WHERE user_id=? AND status='active'");
        $q->execute([$userId]);
        $trader=$q->fetch();
        if (!$trader) { self::answer(['error'=>'Profil maître actif requis'],403);return; }
        $q=$db->prepare("INSERT INTO copy_simulated_events (trader_id,client_event_id,symbol,direction,entry_price,stop_loss,take_profit)
            VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $q->execute([$trader['id'],$externalId,$symbol,$side,$entry,$stop,$target]);
        $eventId=(int)$db->lastInsertId();
        self::answer(['mode'=>'simulation','event_id'=>$eventId,'message'=>'Signal simulé enregistré ; aucun ordre réel exécuté'],201);
    }
}
