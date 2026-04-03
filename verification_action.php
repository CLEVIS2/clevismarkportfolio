<?php

session_start();

require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$verification = clevis_bootstrap_verification();

$choice = trim($_POST['choice'] ?? '');
$options = clevis_get_verification_options();

if ($choice === '' || !isset($options[$choice])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid choice.']);
    exit;
}

$target = $verification['target'] ?? '';

if (hash_equals((string) $target, $choice)) {
    clevis_mark_verification_solved($choice);

    echo json_encode([
        'ok' => true,
        'correct' => true,
        'target' => $choice,
        'targetLabel' => $options[$choice]['label'],
        'cards' => $verification['cards'],
        'hidePanel' => true,
    ]);
    exit;
}

$next = clevis_rotate_verification_after_wrong_choice($choice);

echo json_encode([
    'ok' => true,
    'correct' => false,
    'target' => $next['target'],
    'targetLabel' => $next['target_label'],
    'cards' => $next['cards'],
    'hidePanel' => false,
]);
