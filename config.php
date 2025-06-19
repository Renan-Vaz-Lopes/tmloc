<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);

$dotenv->safeLoad();


function getConfig(): array {
    return [
        'email' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => $_ENV['EMAIL_USERNAME'] ?? getenv('EMAIL_USERNAME'),
            'password' => $_ENV['EMAIL_PASSWORD'] ?? getenv('EMAIL_PASSWORD'),
            'SMTPSecure' => 'tls'
        ]
    ];
}
