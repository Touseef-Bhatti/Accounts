<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

use App\Auth;
use App\Mailer;

if (Auth::check()) {
    redirect(base_url('index.php'));
}

$step = $_SESSION['otp_step'] ?? 'email';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'send';

    if ($action === 'send') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (!Auth::isAuthorizedEmail($email)) {
            $error = 'This email is not authorized. Contact administrator.';
        } else {
            $otp = Auth::createOtp($email);
            if (Mailer::sendOtp($email, $otp)) {
                $_SESSION['otp_pending_email'] = $email;
                $_SESSION['otp_step'] = 'verify';
                $step = 'verify';
                flash('success', 'Verification code sent to your email.');
            } elseif (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
                $_SESSION['otp_pending_email'] = $email;
                $_SESSION['otp_step'] = 'verify';
                $_SESSION['otp_debug'] = $otp;
                $step = 'verify';
                flash('success', 'DEBUG mode: OTP shown below (mail failed).');
            } else {
                $error = 'Could not send email. Check SMTP settings in .env';
            }
        }
    } elseif ($action === 'verify') {
        $email = $_SESSION['otp_pending_email'] ?? '';
        $otp = trim($_POST['otp'] ?? '');
        if ($email && Auth::verifyOtp($email, $otp)) {
            Auth::login($email);
            unset($_SESSION['otp_step'], $_SESSION['otp_pending_email'], $_SESSION['otp_debug']);
            redirect(base_url('index.php'));
        }
        $error = 'Invalid or expired code. Try again.';
        $step = 'verify';
    }
}

layout_header('Login');
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">Authorized Login</h1>
                <p class="text-muted small text-center">OTP will be sent to your registered email only.</p>
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

                <?php if ($step === 'email'): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send">
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control form-control-lg" required autofocus placeholder="you@company.com">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Verification Code</button>
                </form>
                <?php else: ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="verify">
                    <p class="small">Code sent to <strong><?= e($_SESSION['otp_pending_email'] ?? '') ?></strong></p>
                    <?php if (!empty($_SESSION['otp_debug'])): ?>
                        <div class="alert alert-warning small">DEBUG OTP: <strong><?= e($_SESSION['otp_debug']) ?></strong></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Enter 6-digit code</label>
                        <input type="text" name="otp" class="form-control form-control-lg text-center letter-spacing" maxlength="8" pattern="[0-9]+" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-2">Verify & Login</button>
                    <a href="login.php?reset=1" class="btn btn-link w-100">Use different email</a>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
if (isset($_GET['reset'])) {
    unset($_SESSION['otp_step'], $_SESSION['otp_pending_email'], $_SESSION['otp_debug']);
}
layout_footer();
