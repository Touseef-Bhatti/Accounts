<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

use App\AccountRepository;
use App\Auth;

Auth::requireLogin();

$accounts = [];
try {
    $accounts = AccountRepository::all();
} catch (Throwable $e) {
    flash('error', 'Database not ready. Please run install.php first.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $slug = $_POST['account_slug'] ?? '';
    $account = AccountRepository::findBySlug($slug);
    if ($account) {
        $_SESSION['account_id'] = (int) $account['id'];
        $_SESSION['account_slug'] = $account['slug'];
        $_SESSION['account_name'] = $account['name'];
        redirect(base_url('documents/create.php'));
    }
    flash('error', 'Please select a valid company account.');
}

layout_header('Select Company');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="text-center mb-4">
            <h1 class="h3">Select Company Account</h1>
            <p class="text-muted">Choose Bhatti Trader or Bhatti Chemicals Industry to create export documents.</p>
        </div>
        <form method="post" class="row g-4">
            <?= csrf_field() ?>
            <?php foreach ($accounts as $acc):
                $logo = $acc['logo_path'] && is_file(__DIR__ . '/' . $acc['logo_path'])
                    ? asset_url($acc['logo_path']) : null;
            ?>
            <div class="col-md-6">
                <label class="account-card card h-100 border-2 shadow-sm">
                    <input type="radio" name="account_slug" value="<?= e($acc['slug']) ?>" class="d-none account-radio" required>
                    <div class="card-body text-center p-4">
                        <?php if ($logo): ?>
                            <img src="<?= e($logo) ?>" alt="" class="account-logo mb-3">
                        <?php else: ?>
                            <div class="account-placeholder mb-3"><?= e(mb_substr($acc['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <h2 class="h5 mb-1"><?= e($acc['name']) ?></h2>
                        <p class="text-muted small mb-0"><?= e($acc['city'] ?? '') ?>, <?= e($acc['country'] ?? 'Pakistan') ?></p>
                    </div>
                </label>
            </div>
            <?php endforeach; ?>
            <?php if (!$accounts): ?>
                <div class="col-12"><div class="alert alert-warning">No accounts found. <a href="install.php">Run installation</a>.</div></div>
            <?php endif; ?>
        </form>
        <?php if (!empty($_SESSION['account_id'])): ?>
        <div class="mt-4 text-center">
            <a href="<?= e(base_url('documents/history.php')) ?>" class="btn btn-outline-secondary">View Previous Document Sets</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.querySelectorAll('.account-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.account-card').forEach(c => c.classList.remove('border-primary'));
        card.classList.add('border-primary');
        const radio = card.querySelector('.account-radio');
        if (radio) {
            radio.checked = true;
            // Submit the form immediately upon selection
            card.closest('form').submit();
        }
    });
});
</script>
<?php layout_footer(); ?>
