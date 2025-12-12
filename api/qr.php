<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_once __DIR__ . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$ticketNo = trim((string)($_GET['ticket_no'] ?? ''));
if ($ticketNo === '') { http_response_code(400); exit('ticket_no required'); }

$st = db()->prepare("
  SELECT t.ticket_no, e.title, e.event_date, g.category, g.group_code
  FROM tickets t
  JOIN events e ON e.id=t.event_id
  JOIN event_table_groups g ON g.id=t.group_id
  WHERE t.ticket_no=?
  LIMIT 1
");
$st->execute([$ticketNo]);
$row = $st->fetch();
if (!$row) { http_response_code(404); exit('not found'); }

// QR içine yazılacak payload (kapı için yeterli)
$payload = json_encode([
  'ticket_no' => $row['ticket_no'],
  'event'     => $row['title'],
  'date'      => $row['event_date'],
  'cat'       => $row['category'],
  'ref'       => $row['group_code'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// QR üret
$options = new QROptions([
  'outputType' => QRCode::OUTPUT_IMAGE_PNG,
  'eccLevel'   => QRCode::ECC_M,
  'scale'      => 8,
]);
$qrPng = (new QRCode($options))->render($payload);

// Şimdi üst/alt yazılı tek PNG yap (GD)
$qrImg = imagecreatefromstring($qrPng);

$w = imagesx($qrImg);
$h = imagesy($qrImg);

// Canvas: üstte yazı + altta bilgi
$padTop = 60;
$padBottom = 110;
$canvas = imagecreatetruecolor($w, $h + $padTop + $padBottom);

$white = imagecolorallocate($canvas, 255,255,255);
$black = imagecolorallocate($canvas, 0,0,0);
imagefill($canvas, 0,0, $white);

// Üst: 7's Lounge
$header = "7's Lounge";
imagestring($canvas, 5, (int)(($w - (strlen($header)*9))/2), 20, $header, $black);

// QR’ı yapıştır
imagecopy($canvas, $qrImg, 0, $padTop, 0, 0, $w, $h);

// Alt bilgiler
$line1 = $row['title'];
$line2 = $row['event_date'] . " | " . $row['category'] . " | " . $row['group_code'];
$line3 = "Bilet No: " . $row['ticket_no'];

imagestring($canvas, 3, 12, $padTop + $h + 15, $line1, $black);
imagestring($canvas, 3, 12, $padTop + $h + 40, $line2, $black);
imagestring($canvas, 4, 12, $padTop + $h + 70, $line3, $black);

header('Content-Type: image/png');
imagepng($canvas);

imagedestroy($qrImg);
imagedestroy($canvas);
