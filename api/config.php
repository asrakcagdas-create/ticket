<?php
declare(strict_types=1);

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
  $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue; // skip comments
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if (!getenv($key)) {
      putenv("$key=$value");
    }
  }
}

// DB - Read from environment variables with defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'ciik8ph9c_ticket');
define('DB_USER', getenv('DB_USER') ?: 'ciik8ph9c_ticket');
define('DB_PASS', getenv('DB_PASS') ?: '123aresmm');
define('DB_CHARSET', 'utf8mb4');

// APP
define('APP_NAME', getenv('APP_NAME') ?: "7's Lounge");

// 6 haneli PIN'ler (değiştirebilirsin)
define('ADMIN_PIN', '111111'); // Admin

define('SELLERS', [
  '222222' => 'Bahri Culcu',
  '333333' => 'Hüseyin Boztepe',
]);

// CORS
define('CORS_ALLOW_ORIGIN', getenv('CORS_ALLOW_ORIGIN') ?: '*');

// Database connection helper
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
  } catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection error.');
  }

  return $pdo;
}
