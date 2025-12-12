<?php
declare(strict_types=1);

// DB - Read from environment variables with fallback defaults
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
