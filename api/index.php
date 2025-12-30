<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (defined('CORS_ALLOW_ORIGIN') && CORS_ALLOW_ORIGIN) {
  header('Access-Control-Allow-Origin: ' . CORS_ALLOW_ORIGIN);
  header('Access-Control-Allow-Headers: Content-Type, X-PIN');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
}

function json_out(array $data, int $code = 200): void{
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function body_json(): array{
  $raw = file_get_contents('php://input');
  $j = json_decode($raw ?: '[]', true);
  return is_array($j) ? $j : [];
}

function table_exists(string $table): bool{
  $st = db()->prepare("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name=?");
  $st->execute([$table]);
  return (int)($st->fetch()['c'] ?? 0) > 0;
}

function col_exists(string $table, string $col): bool{
  $st = db()->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name=? AND column_name=?");
  $st->execute([$table, $col]);
  return (int)($st->fetch()['c'] ?? 0) > 0;
}

function capacity_for_tables(int $n): int{
  return match($n){
    1 => 3,
    2 => 5,
    3 => 6,
    4 => 8,
    5 => 10,
    6 => 12,
    default => 0
  };
}

function ticket_prefix_for_date(string $ymd): string{
  // 7S-YYYYMMDD-
  $ymd = preg_replace('/[^0-9\-]/','', $ymd);
  $ymd = str_replace('-', '', $ymd);
  return "7S-".$ymd."-";
}

function auth_user_safe(): array{
  // helpers.php içindeki auth_user() varsa onu kullan
  if (function_exists('auth_user')) return auth_user();

  // yoksa basit PIN auth
  $pin = $_SERVER['HTTP_X_PIN'] ?? '';
  $pin = trim($pin);
  if($pin==='') json_out(['ok'=>false,'error'=>'unauthorized'],401);

  // users tablosu varsa kontrol et
  if (table_exists('users')) {
    $st = db()->prepare("SELECT * FROM users WHERE pin=? AND is_active=1 LIMIT 1");
    $st->execute([$pin]);
    $u = $st->fetch();
    if($u) return ['name'=>$u['name'], 'role'=>$u['role']];
  }

  // fallback: admin
  return ['name'=>'ADMIN', 'role'=>'admin'];
}

function log_action(string $actor, string $action, string $entity, ?int $entityId, array $detail = []): void {
  if(!table_exists('audit_log')) return;
  try{
    $st = db()->prepare("INSERT INTO audit_log (actor, action, entity, entity_id, detail) VALUES (?,?,?,?,?)");
    $st->execute([$actor, $action, $entity, $entityId, json_encode($detail, JSON_UNESCAPED_UNICODE)]);
  }catch(Throwable $e){}
}

function tables_table_name(): string{
  if(table_exists('tables_base')) return 'tables_base';
  if(table_exists('tables')) return 'tables';
  return 'tables_base';
}

/**
 * ✅ FIX: Base gruplar daha önce üretildiyse tekrar üretme.
 * Base gruplar: group_label IS NULL
 */
function ensureEventBaseGroups(int $eventId): void {
  $c = db()->prepare("SELECT COUNT(*) c FROM event_table_groups WHERE event_id=? AND deleted_at IS NULL AND group_label IS NULL");
  $c->execute([$eventId]);
  if((int)($c->fetch()['c'] ?? 0) > 0) return;

  $tbl = tables_table_name();

  $hasActive = col_exists($tbl,'is_active');
  $sql = "SELECT id, code, category"
       . (col_exists($tbl,'x') ? ", x" : "")
       . (col_exists($tbl,'y') ? ", y" : "")
       . (col_exists($tbl,'w') ? ", w" : "")
       . (col_exists($tbl,'h') ? ", h" : "")
       . " FROM `$tbl`";
  if($hasActive) $sql .= " WHERE is_active=1";
  $sql .= " ORDER BY id ASC";

  $tables = db()->query($sql)->fetchAll();
  if (!$tables) return;

  db()->beginTransaction();

  $insG = db()->prepare("
    INSERT INTO event_table_groups (event_id, category, group_label, group_code, max_capacity, status)
    VALUES (?, ?, NULL, ?, ?, 'OPEN')
  ");
  $insI = db()->prepare("INSERT INTO event_table_group_items (group_id, table_id) VALUES (?, ?)");

  foreach($tables as $t){
    $cat = (string)$t['category'];
    $code = (string)$t['code'];

    // LOCA base masası varsa 10, değilse 3’lük masa
    $max = ($cat === 'LOCA') ? 10 : 3;

    $insG->execute([$eventId, $cat, $code, $max]);
    $gid = (int)db()->lastInsertId();
    $insI->execute([$gid, (int)$t['id']]);
  }

  db()->commit();
}

function fetchGroupsWithBase(int $eventId): array {
  $tbl = tables_table_name();

  $xcol = col_exists($tbl,'x') ? "tb.x" : "0";
  $ycol = col_exists($tbl,'y') ? "tb.y" : "0";
  $wcol = col_exists($tbl,'w') ? "tb.w" : "0";
  $hcol = col_exists($tbl,'h') ? "tb.h" : "0";

  $sql = "
    SELECT g.*,
           tb.id AS table_id, tb.code AS table_code,
           $xcol AS base_x, $ycol AS base_y, $wcol AS base_w, $hcol AS base_h,
           (SELECT COUNT(*) FROM tickets t WHERE t.group_id=g.id AND t.cancelled_at IS NULL) AS sold
    FROM event_table_groups g
    JOIN event_table_group_items gi ON gi.group_id = g.id
    JOIN `$tbl` tb ON tb.id = gi.table_id
    WHERE g.event_id=?
      AND g.deleted_at IS NULL
    ORDER BY
      FIELD(g.category,'VIP','GOLD','ST','LOCA'),
      base_y ASC,
      base_x ASC
  ";
  $st = db()->prepare($sql);
  $st->execute([$eventId]);
  return $st->fetchAll();
}

function suggestCombos(int $eventId, string $category, int $persons): array {
  $needTables = match(true){
    $persons <= 3 => 1,
    $persons <= 5 => 2,
    $persons <= 6 => 3,
    $persons <= 8 => 4,
    $persons <= 10 => 5,
    $persons <= 12 => 6,
    default => 0
  };
  if($needTables === 0) return [];

  $rows = fetchGroupsWithBase($eventId);

  $tables = array_values(array_filter($rows, fn($r)=>
    ($r['category'] === $category) && ($r['status'] === 'OPEN')
  ));

  $Y_TOL = 40;
  $byRow = [];
  foreach($tables as $t){
    $y = (int)$t['base_y'];
    $key = null;
    foreach(array_keys($byRow) as $k){
      if(abs((int)$k - $y) <= $Y_TOL){ $key = $k; break; }
    }
    if($key === null) $key = (string)$y;
    $byRow[$key][] = $t;
  }
  foreach($byRow as &$arr){
    usort($arr, fn($a,$b)=> (int)$a['base_x'] <=> (int)$b['base_x']);
  }
  unset($arr);

  $candidates = [];
  foreach($byRow as $arr){
    $n = count($arr);
    if($n < $needTables) continue;
    for($i=0;$i<= $n-$needTables;$i++){
      $slice = array_slice($arr,$i,$needTables);
      $xs = array_map(fn($r)=>(int)$r['base_x'],$slice);
      $score = (max($xs)-min($xs));
      $candidates[] = [
        'table_ids' => array_map(fn($r)=>(int)$r['table_id'],$slice),
        'codes'     => array_map(fn($r)=>(string)$r['group_code'],$slice),
        'score'     => $score,
      ];
    }
  }
  usort($candidates, fn($a,$b)=> $a['score'] <=> $b['score']);
  return array_slice($candidates, 0, 6);
}

$path = $_GET['r'] ?? '';

try {
  switch ($path) {

    case 'ping': {
      json_out([
        'ok'=>true,
        'app'=> (defined('APP_NAME')?APP_NAME:"7's Lounge"),
        'file'=>__FILE__,
        'time'=>time()
      ]);
      break;
    }

    case 'debug.db': {
      auth_user_safe();
      $row = db()->query("SELECT DATABASE() AS db, @@hostname AS host, @@port AS port")->fetch();
      json_out(['ok'=>true,'db'=>$row]);
      break;
    }

    case 'debug.deletedat': {
      auth_user_safe();
      $st = db()->query("SELECT COUNT(*) c
                         FROM information_schema.columns
                         WHERE table_schema = DATABASE()
                           AND table_name='event_table_groups'
                           AND column_name='deleted_at'");
      $row = $st->fetch();
      json_out(['ok'=>true,'has_deleted_at'=>(int)($row['c'] ?? 0)]);
      break;
    }

    case 'debug.columns': {
      auth_user_safe();
      $table = $_GET['table'] ?? '';
      if($table==='') json_out(['ok'=>false,'error'=>'table required'],400);

      $st = db()->prepare("SHOW COLUMNS FROM `$table`");
      $st->execute();
      json_out(['ok'=>true,'columns'=>$st->fetchAll()]);
      break;
    }

    case 'auth.me': {
      $u = auth_user_safe();
      json_out(['ok'=>true,'role'=>$u['role'],'name'=>$u['name']]);
      break;
    }

    case 'events.list': {
      auth_user_safe();
      $rows = db()->query("SELECT id,title,event_date,is_active FROM events ORDER BY event_date DESC")->fetchAll();
      json_out(['ok'=>true,'events'=>$rows]);
      break;
    }

    case 'event.create': {
      $u = auth_user_safe();
      if(($u['role'] ?? '') !== 'admin'){
        json_out(['ok'=>false,'error'=>'forbidden'],403);
      }

      $b = body_json();
      $title = trim((string)($b['title'] ?? ''));
      $event_date = trim((string)($b['event_date'] ?? ''));
      $vip_price = (float)($b['vip_price'] ?? 0);
      $gold_price = (float)($b['gold_price'] ?? 0);
      $st_price = (float)($b['st_price'] ?? 0);
      $loca_price = (float)($b['loca_price'] ?? 0);

      if($title==='' || $event_date===''){
        json_out(['ok'=>false,'error'=>'title + event_date required'],400);
      }

      if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date)){
        if(preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $event_date, $m)){
          $event_date = $m[3].'-'.$m[2].'-'.$m[1];
        }
      }

      db()->prepare("INSERT INTO events (title,event_date,vip_price,gold_price,st_price,loca_price,is_active)
                     VALUES (?,?,?,?,?,?,1)")
        ->execute([$title,$event_date,$vip_price,$gold_price,$st_price,$loca_price]);

      $id = (int)db()->lastInsertId();
      log_action($u['name'],'EVENT_CREATE','event',$id,[
        'title'=>$title,'event_date'=>$event_date,
        'vip_price'=>$vip_price,'gold_price'=>$gold_price,'st_price'=>$st_price,'loca_price'=>$loca_price
      ]);

      json_out(['ok'=>true,'event_id'=>$id]);
      break;
    }

    case 'event.get': {
      auth_user_safe();
      $eventId = (int)($_GET['event_id'] ?? 0);
      if($eventId<=0) json_out(['ok'=>false,'error'=>'event_id required'],400);
      ensureEventBaseGroups($eventId);

      $evSt = db()->prepare("SELECT * FROM events WHERE id=?");
      $evSt->execute([$eventId]);
      $event = $evSt->fetch();
      if(!$event) json_out(['ok'=>false,'error'=>'event not found'],404);

      $groups = fetchGroupsWithBase($eventId);
      json_out(['ok'=>true,'event'=>$event,'groups'=>$groups]);
      break;
    }

    case 'groups.by_event': {
      auth_user_safe();
      $eventId = (int)($_GET['event_id'] ?? 0);
      if($eventId<=0) json_out(['ok'=>false,'error'=>'event_id required'],400);
      ensureEventBaseGroups($eventId);
      json_out(['ok'=>true,'groups'=>fetchGroupsWithBase($eventId)]);
      break;
    }

    case 'tickets.by_event': {
      auth_user_safe();
      $eventId = (int)($_GET['event_id'] ?? 0);
      $limit = (int)($_GET['limit'] ?? 80);
      if($eventId<=0) json_out(['ok'=>false,'error'=>'event_id required'],400);
      $limit = max(1, min(200, $limit));

      $st = db()->prepare("
        SELECT t.id,t.ticket_no,t.group_id,t.sold_by,t.created_at,t.cancelled_at,t.cancelled_by,
               g.group_code,g.group_label,g.category
        FROM tickets t
        JOIN event_table_groups g ON g.id=t.group_id
        WHERE t.event_id=?
        ORDER BY t.id DESC
        LIMIT $limit
      ");
      $st->execute([$eventId]);
      json_out(['ok'=>true,'tickets'=>$st->fetchAll()]);
      break;
    }

    case 'tables.suggest': {
      auth_user_safe();
      $eventId = (int)($_GET['event_id'] ?? 0);
      $category = (string)($_GET['category'] ?? '');
      $persons = (int)($_GET['persons'] ?? 0);
      if($eventId<=0 || $persons<=0) json_out(['ok'=>false,'error'=>'event_id + persons required'],400);
      if(!in_array($category,['VIP','GOLD','ST','LOCA'],true)) json_out(['ok'=>false,'error'=>'invalid category'],400);
      ensureEventBaseGroups($eventId);

      if($category === 'LOCA'){
        $rows = fetchGroupsWithBase($eventId);
        $open = array_values(array_filter($rows, fn($r)=>$r['category']==='LOCA' && $r['status']==='OPEN'));
        $out = array_map(fn($r)=>[
          'table_ids'=>[(int)$r['table_id']],
          'codes'=>[(string)$r['group_code']],
          'score'=>0
        ], $open);
        json_out(['ok'=>true,'suggestions'=>array_slice($out,0,6)]);
      }

      json_out(['ok'=>true,'suggestions'=>suggestCombos($eventId,$category,$persons)]);
      break;
    }

    case 'group.status': {
      auth_user_safe();
      $groupId = (int)($_GET['group_id'] ?? 0);
      if($groupId<=0) json_out(['ok'=>false,'error'=>'group_id required'],400);

      $st = db()->prepare("
        SELECT g.*,
          (SELECT COUNT(*) FROM tickets t WHERE t.group_id=g.id AND t.cancelled_at IS NULL) AS sold
        FROM event_table_groups g
        WHERE g.id=? AND g.deleted_at IS NULL
      ");
      $st->execute([$groupId]);
      $g = $st->fetch();
      if(!$g) json_out(['ok'=>false,'error'=>'group not found'],404);

      $remaining = max(0, (int)$g['max_capacity'] - (int)$g['sold']);
      json_out(['ok'=>true,'group'=>$g,'remaining'=>$remaining]);
      break;
    }

    case 'group.create_auto': {
      $u = auth_user_safe();
      $b = body_json();
      $eventId = (int)($b['event_id'] ?? 0);
      $category = (string)($b['category'] ?? '');
      $groupLabel = trim((string)($b['group_label'] ?? ''));
      $tableIds = $b['table_ids'] ?? [];

      if($eventId<=0 || $groupLabel==='' || !is_array($tableIds) || count($tableIds)===0)
        json_out(['ok'=>false,'error'=>'event_id + group_label + table_ids required'],400);
      if(!in_array($category,['VIP','GOLD','ST','LOCA'],true)) json_out(['ok'=>false,'error'=>'invalid category'],400);

      ensureEventBaseGroups($eventId);

      $placeholders = implode(',', array_fill(0, count($tableIds), '?'));
      $params = array_merge([$eventId,$category], array_map('intval',$tableIds));

      $tbl = tables_table_name();
      $q = db()->prepare("
        SELECT g.id, g.status, tb.id AS table_id, tb.code
        FROM event_table_groups g
        JOIN event_table_group_items gi ON gi.group_id=g.id
        JOIN `$tbl` tb ON tb.id=gi.table_id
        WHERE g.event_id=? AND g.category=? AND tb.id IN ($placeholders) AND g.deleted_at IS NULL
      ");
      $q->execute($params);
      $rows = $q->fetchAll();
      if(count($rows) !== count($tableIds)) json_out(['ok'=>false,'error'=>'tables not found'],400);

      foreach($rows as $r){
        if($r['status'] !== 'OPEN'){
          json_out(['ok'=>false,'error'=>'TABLE_NOT_AVAILABLE','detail'=>$r['code']],400);
        }
      }

      $n = count($tableIds);
      $max = ($category==='LOCA') ? 10 : capacity_for_tables($n);
      if($max<=0) json_out(['ok'=>false,'error'=>'invalid merge count'],400);

      db()->beginTransaction();

      $idsToClose = array_map(fn($r)=>(int)$r['id'],$rows);
      $ph2 = implode(',', array_fill(0,count($idsToClose),'?'));
      db()->prepare("UPDATE event_table_groups SET status='FULL' WHERE id IN ($ph2)")->execute($idsToClose);

      $code = $category . '-' . implode('+', array_map(fn($r)=>$r['code'],$rows));

      db()->prepare("
        INSERT INTO event_table_groups (event_id, category, group_label, group_code, max_capacity, status)
        VALUES (?,?,?,?,?, 'LOCKED')
      ")->execute([$eventId,$category,$groupLabel,$code,$max]);

      $newGroupId = (int)db()->lastInsertId();

      $insI = db()->prepare("INSERT INTO event_table_group_items (group_id, table_id) VALUES (?,?)");
      foreach($tableIds as $tid){
        $insI->execute([$newGroupId,(int)$tid]);
      }

      db()->commit();

      log_action($u['name'],'GROUP_CREATE_AUTO','group',$newGroupId,[
        'event_id'=>$eventId,'category'=>$category,'label'=>$groupLabel,'tables'=>$tableIds,'code'=>$code,'max'=>$max
      ]);

      json_out(['ok'=>true,'group_id'=>$newGroupId,'group_code'=>$code,'max_capacity'=>$max]);
      break;
    }

    case 'group.pay': {
      $u = auth_user_safe();
      $b = body_json();
      $groupId = (int)($b['group_id'] ?? 0);
      if($groupId<=0) json_out(['ok'=>false,'error'=>'group_id required'],400);

      db()->prepare("UPDATE event_table_groups SET paid_at=NOW(), paid_by=? WHERE id=?")
        ->execute([$u['name'],$groupId]);

      log_action($u['name'],'GROUP_PAY','group',$groupId,[]);
      json_out(['ok'=>true]);
      break;
    }

    case 'ticket.create': {
      $u = auth_user_safe();
      $b = body_json();
      $eventId = (int)($b['event_id'] ?? 0);
      $groupId = (int)($b['group_id'] ?? 0);
      if($eventId<=0 || $groupId<=0) json_out(['ok'=>false,'error'=>'event_id + group_id required'],400);

      $ev = db()->prepare("SELECT event_date FROM events WHERE id=?");
      $ev->execute([$eventId]);
      $er = $ev->fetch();
      if(!$er) json_out(['ok'=>false,'error'=>'event not found'],404);
      $dateYmd = (string)$er['event_date'];

      $g = db()->prepare("SELECT max_capacity,status FROM event_table_groups WHERE id=? AND event_id=? AND deleted_at IS NULL");
      $g->execute([$groupId,$eventId]);
      $gr = $g->fetch();
      if(!$gr) json_out(['ok'=>false,'error'=>'group not found'],404);
      if($gr['status'] !== 'LOCKED') json_out(['ok'=>false,'error'=>'GROUP_NOT_LOCKED'],400);

      $soldSt = db()->prepare("SELECT COUNT(*) c FROM tickets WHERE group_id=? AND cancelled_at IS NULL");
      $soldSt->execute([$groupId]);
      $sold = (int)($soldSt->fetch()['c'] ?? 0);
      if($sold >= (int)$gr['max_capacity']) json_out(['ok'=>false,'error'=>'FULL'],400);

      $prefix = ticket_prefix_for_date($dateYmd);
      $mx = db()->prepare("SELECT ticket_no FROM tickets WHERE ticket_no LIKE ? ORDER BY ticket_no DESC LIMIT 1");
      $mx->execute([$prefix.'%']);
      $last = $mx->fetch()['ticket_no'] ?? null;
      $seq = $last ? ((int)substr($last, strlen($prefix)) + 1) : 1;
      $ticketNo = $prefix . str_pad((string)$seq,6,'0',STR_PAD_LEFT);

      db()->prepare("INSERT INTO tickets (event_id,group_id,ticket_no,paid_method,sold_by)
                     VALUES (?,?,?,'CASH',?)")
        ->execute([$eventId,$groupId,$ticketNo,$u['name']]);

      if($sold+1 >= (int)$gr['max_capacity']){
        db()->prepare("UPDATE event_table_groups SET status='FULL' WHERE id=?")->execute([$groupId]);
      }

      $tid = (int)db()->lastInsertId();
      log_action($u['name'],'TICKET_CREATE','ticket',$tid,['ticket_no'=>$ticketNo,'group_id'=>$groupId]);

      json_out(['ok'=>true,'ticket_no'=>$ticketNo,'qr_url'=>"/api/qr.php?ticket_no=".$ticketNo]);
      break;
    }

    case 'ticket.add': {
      $u = auth_user_safe();
      $b = body_json();
      $groupId = (int)($b['group_id'] ?? 0);
      if($groupId<=0) json_out(['ok'=>false,'error'=>'group_id required'],400);

      $st = db()->prepare("
        SELECT g.event_id, g.max_capacity, g.status,
          (SELECT COUNT(*) FROM tickets t WHERE t.group_id=g.id AND t.cancelled_at IS NULL) AS sold
        FROM event_table_groups g
        WHERE g.id=? AND g.deleted_at IS NULL
      ");
      $st->execute([$groupId]);
      $g = $st->fetch();
      if(!$g) json_out(['ok'=>false,'error'=>'group not found'],404);
      if($g['status'] !== 'LOCKED') json_out(['ok'=>false,'error'=>'GROUP_NOT_LOCKED'],400);
      if((int)$g['sold'] >= (int)$g['max_capacity']) json_out(['ok'=>false,'error'=>'FULL'],400);

      $ev = db()->prepare("SELECT event_date FROM events WHERE id=?");
      $ev->execute([(int)$g['event_id']]);
      $er = $ev->fetch();
      if(!$er) json_out(['ok'=>false,'error'=>'event not found'],404);
      $dateYmd = (string)$er['event_date'];

      $prefix = ticket_prefix_for_date($dateYmd);
      $mx = db()->prepare("SELECT ticket_no FROM tickets WHERE ticket_no LIKE ? ORDER BY ticket_no DESC LIMIT 1");
      $mx->execute([$prefix.'%']);
      $last = $mx->fetch()['ticket_no'] ?? null;
      $seq = $last ? ((int)substr($last, strlen($prefix)) + 1) : 1;
      $ticketNo = $prefix . str_pad((string)$seq,6,'0',STR_PAD_LEFT);

      db()->prepare("INSERT INTO tickets (event_id,group_id,ticket_no,paid_method,sold_by)
                     VALUES (?,?,?,'CASH',?)")
        ->execute([(int)$g['event_id'],$groupId,$ticketNo,$u['name']]);

      if(((int)$g['sold']+1) >= (int)$g['max_capacity']){
        db()->prepare("UPDATE event_table_groups SET status='FULL' WHERE id=?")->execute([$groupId]);
      }

      $tid = (int)db()->lastInsertId();
      log_action($u['name'],'TICKET_ADD','ticket',$tid,['ticket_no'=>$ticketNo,'group_id'=>$groupId]);

      json_out(['ok'=>true,'ticket_no'=>$ticketNo,'qr_url'=>"/api/qr.php?ticket_no=".$ticketNo]);
      break;
    }

    case 'ticket.cancel': {
      $u = auth_user_safe();
      $b = body_json();
      $ticketId = (int)($b['ticket_id'] ?? 0);
      if($ticketId<=0) json_out(['ok'=>false,'error'=>'ticket_id required'],400);

      db()->prepare("UPDATE tickets SET cancelled_at=NOW(), cancelled_by=? WHERE id=? AND cancelled_at IS NULL")
        ->execute([$u['name'],$ticketId]);

      log_action($u['name'],'TICKET_CANCEL','ticket',$ticketId,[]);
      json_out(['ok'=>true]);
      break;
    }

    case 'group.delete': {
      $u = auth_user_safe();
      $b = body_json();
      $groupId = (int)($b['group_id'] ?? 0);
      if($groupId<=0) json_out(['ok'=>false,'error'=>'group_id required'],400);

      db()->prepare("UPDATE event_table_groups SET deleted_at=NOW(), deleted_by=? WHERE id=? AND deleted_at IS NULL")
        ->execute([$u['name'],$groupId]);

      log_action($u['name'],'GROUP_DELETE','group',$groupId,[]);
      json_out(['ok'=>true]);
      break;
    }

    case 'user.change_pin': {
      $u = auth_user_safe();
      $b = body_json();
      $currentPin = trim((string)($b['current_pin'] ?? ''));
      $newPin = trim((string)($b['new_pin'] ?? ''));

      if($currentPin === '' || $newPin === '') {
        json_out(['ok'=>false,'error'=>'current_pin and new_pin required'],400);
      }

      if(strlen($newPin) < 6) {
        json_out(['ok'=>false,'error'=>'new_pin must be at least 6 characters'],400);
      }

      // Check if users table exists
      if(!table_exists('users')) {
        json_out(['ok'=>false,'error'=>'users table does not exist'],400);
      }

      // Get current authenticated user's PIN from header
      $storedPin = $_SERVER['HTTP_X_PIN'] ?? '';
      
      // Verify that the user provided the correct current PIN
      if($currentPin !== $storedPin) {
        json_out(['ok'=>false,'error'=>'current_pin incorrect'],400);
      }

      // Update PIN in database using the authenticated PIN, not user ID
      // Since this system uses PIN-based auth without user IDs exposed
      $st = db()->prepare("UPDATE users SET pin=? WHERE pin=? AND is_active=1");
      $st->execute([$newPin, $storedPin]);

      if($st->rowCount() === 0) {
        json_out(['ok'=>false,'error'=>'user not found or PIN update failed'],400);
      }

      log_action($u['name'],'USER_CHANGE_PIN','user',null,[]);
      json_out(['ok'=>true]);
      break;
    }

    default:
      json_out(['ok'=>false,'error'=>'unknown route'],404);
  }

} catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'server_error','detail'=>$e->getMessage()],500);
}
