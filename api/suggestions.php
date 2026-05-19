<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Auth;
use App\SuggestionService;

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$accountId = (int) ($_GET['account_id'] ?? $_SESSION['account_id'] ?? 0);
$field = trim($_GET['field'] ?? '');
$q = trim($_GET['q'] ?? '');

if ($accountId <= 0 || $field === '') {
    echo json_encode([]);
    exit;
}

try {
    $items = SuggestionService::search($accountId, $field, $q);
    echo json_encode($items);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}
