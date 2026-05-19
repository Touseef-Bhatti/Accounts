<?php

declare(strict_types=1);

function layout_header(string $title, string $active = ''): void
{
    $appName = env('APP_NAME', 'Bhatti Export Documents');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= e($appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(base_url('index.php')) ?>"><?= e($appName) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if (App\Auth::check()): ?>
                    <li class="nav-item"><span class="nav-link small opacity-75"><?= e(App\Auth::userEmail()) ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(base_url('settings.php')) ?>">Settings</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(base_url('logout.php')) ?>">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php
    $success = flash('success');
    $error = flash('error');
    if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif;
    if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif;
}

function layout_footer(bool $withSuggest = false): void
{
    ?>
</main>
<footer class="text-center text-muted small py-4 border-top">
    &copy; <?= date('Y') ?> Bhatti Export Documents
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<?php if ($withSuggest): ?>
<script>
window.SUGGEST_ACCOUNT_ID = <?= (int) ($_SESSION['account_id'] ?? 0) ?>;
window.SUGGEST_API = <?= json_encode(base_url('api/suggestions.php')) ?>;
</script>
<?php endif; ?>
</body>
</html>
    <?php
}
