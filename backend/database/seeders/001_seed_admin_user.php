<?php

declare(strict_types=1);

/**
 * Run with: php database/seeders/001_seed_admin_user.php
 * Creates a default admin so the app has an initial login. Change the
 * password immediately after first login in a real deployment.
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Config\Env;
use App\Core\Database;

Env::load();

$pdo = Database::connection();

$email = 'admin@example.com';
$password = 'ChangeMe123!';

$exists = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$exists->execute(['email' => $email]);

if ($exists->fetch()) {
    echo "Admin user already exists ({$email}), skipping.\n";
    exit(0);
}

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
);

$stmt->execute([
    'name'          => 'System Administrator',
    'email'         => $email,
    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
    'role'          => 'admin',
]);

echo "Seeded admin user: {$email} / {$password}\n";