<?php

declare(strict_types=1);
use Core\Auth;

/**
 * 404.php — Page Not Found error view.
 *
 * Shown when the Router cannot match the requested URL to any registered route
 * (browser / non-AJAX request only; AJAX requests receive a JSON 404 response).
 *
 * The back link adapts to the user's auth state:
 *   - logged in  → /dashboard
 *   - logged out → /auth/login
 */

http_response_code(404);

$isLoggedIn = Auth::isLoggedIn();
$backHref   = $isLoggedIn ? '/dashboard' : '/auth/login';
$backLabel  = $isLoggedIn ? 'Go to Dashboard' : 'Go to Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Page Not Found</title>
    <link rel="icon" type="image/x-icon" href="/public/assets/img/logo.svg">
    <link rel="stylesheet" href="/public/assets/css/404.css" />
</head>
<body>
<main class="error-page">
    <span class="error-page__code" aria-hidden="true">404</span>

    <h1 class="error-page__title">Page Not Found</h1>

    <p class="error-page__description">
        The page you're looking for doesn't exist or has been moved.
    </p>

    <a href="<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>"
       class="btn-accent error-page__back">
        <span class="btn-accent__label">
            <?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </a>
</main>
</body>
</html>