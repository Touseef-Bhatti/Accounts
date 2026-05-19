<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

use App\Auth;

Auth::logout();
redirect(base_url('login.php'));
