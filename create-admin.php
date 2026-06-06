<?php

declare(strict_types=1);

const BASE_PATH = __DIR__;

require_once __DIR__ . '/autoload.php';

$email    = getenv('ADMIN_EMAIL')    ?: 'admin@clutch.gg';
$password = getenv('ADMIN_PASSWORD') ?: 'Admin1234!';
$nickname = 'Admin';

if (strlen($password) < 10) {
    fwrite(STDERR, "[create-admin] WARNING: ADMIN_PASSWORD is shorter than 10 characters.\n");
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo  = Core\Database::getInstance()->getPDO();

$stmt = $pdo->prepare("
    INSERT INTO users (nickname, email, password, system_role_id)
    SELECT :nickname, :email, :hash,
           (SELECT id FROM system_roles WHERE ident = 'ADMIN')
    WHERE NOT EXISTS (
        SELECT 1 FROM users WHERE email = :email_check
    )
");

$stmt->execute([
    ':nickname'    => $nickname,
    ':email'       => $email,
    ':hash'        => $hash,
    ':email_check' => $email,
]);

if ($stmt->rowCount() > 0) {
    echo "[create-admin] Admin account created: $email\n";
} else {
    echo "[create-admin] Admin account already exists, skipping.\n";
}