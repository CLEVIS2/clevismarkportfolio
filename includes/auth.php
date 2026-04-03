<?php

function clevis_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function clevis_is_authenticated(): bool
{
    return !empty($_SESSION['user_id']);
}

function clevis_get_current_user_role(): string
{
    return (string)($_SESSION['user_role'] ?? 'user');
}

function clevis_is_admin(): bool
{
    return clevis_is_authenticated() && clevis_get_current_user_role() === 'admin';
}

function clevis_set_auth_flash(string $type, string $message): void
{
    $_SESSION['auth_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function clevis_set_admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function clevis_request_statuses(): array
{
    return ['New', 'In Progress', 'Awaiting Client', 'Completed', 'Cancelled'];
}

function clevis_normalize_request_status(string $status): string
{
    $status = trim($status);

    foreach (clevis_request_statuses() as $allowedStatus) {
        if (strcasecmp($status, $allowedStatus) === 0) {
            return $allowedStatus;
        }
    }

    return 'New';
}

function clevis_log_admin_action(PDO $pdo, string $actionType, array $context = []): void
{
    if (!$pdo || !clevis_is_admin()) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO audit_logs
                (request_id, request_code, admin_user_id, admin_name, action_type, old_value, new_value, details)
             VALUES
                (:request_id, :request_code, :admin_user_id, :admin_name, :action_type, :old_value, :new_value, :details)'
        );

        $statement->execute([
            ':request_id' => isset($context['request_id']) ? (int)$context['request_id'] : null,
            ':request_code' => isset($context['request_code']) ? (string)$context['request_code'] : null,
            ':admin_user_id' => (int)($_SESSION['user_id'] ?? 0),
            ':admin_name' => (string)($_SESSION['user_name'] ?? 'Admin'),
            ':action_type' => $actionType,
            ':old_value' => isset($context['old_value']) ? (string)$context['old_value'] : null,
            ':new_value' => isset($context['new_value']) ? (string)$context['new_value'] : null,
            ':details' => isset($context['details']) ? (string)$context['details'] : null,
        ]);
    } catch (PDOException $exception) {
        // Audit logging should never block the main admin workflow.
    }
}

function clevis_request_is_deleted(array $request): bool
{
    return !empty($request['deleted_at']);
}

function clevis_split_multiline_values($value): array
{
    if (!is_string($value) || trim($value) === '') {
        return [];
    }

    $lines = preg_split('/\R+/', $value) ?: [];
    $lines = array_map(static fn ($item) => trim((string)$item), $lines);

    return array_values(array_filter($lines, static fn ($item) => $item !== ''));
}

function clevis_request_file_entries(array $request, string $pathKey, string $nameKey): array
{
    $paths = clevis_split_multiline_values($request[$pathKey] ?? '');
    $names = clevis_split_multiline_values($request[$nameKey] ?? '');

    $entries = [];
    foreach ($paths as $index => $path) {
        if ($path === '') {
            continue;
        }

        $entries[] = [
            'path' => $path,
            'name' => $names[$index] ?? basename($path),
        ];
    }

    return $entries;
}

function clevis_request_uploaded_files(array $request): array
{
    return [
        'brief' => clevis_request_file_entries($request, 'brief_file_path', 'brief_file_name'),
        'logo' => clevis_request_file_entries($request, 'logo_file_path', 'logo_file_name'),
        'references' => clevis_request_file_entries($request, 'reference_file_path', 'reference_file_name'),
    ];
}

function clevis_get_audit_logs(PDO $pdo, int $limit = 12): array
{
    $limit = max(1, min(100, $limit));

    $statement = $pdo->prepare(
        'SELECT a.id, a.action_type, a.old_value, a.new_value, a.details, a.created_at, a.admin_name, a.request_code, a.request_id, r.client_name, r.service_type
         FROM audit_logs a
         LEFT JOIN service_requests r ON r.id = a.request_id
         ORDER BY a.created_at DESC
         LIMIT ' . $limit
    );
    $statement->execute();

    return $statement->fetchAll();
}

function clevis_get_internal_notes(PDO $pdo, int $requestId, int $limit = 5): array
{
    $limit = max(1, min(50, $limit));

    $statement = $pdo->prepare(
        'SELECT id, request_id, admin_user_id, admin_name, note_text, created_at
         FROM internal_notes
         WHERE request_id = :request_id
         ORDER BY created_at DESC
         LIMIT ' . $limit
    );
    $statement->execute([':request_id' => $requestId]);

    return $statement->fetchAll();
}

function clevis_get_user_feedback(PDO $pdo, int $userId, string $email): array
{
    $statement = $pdo->prepare(
        'SELECT f.id, f.message, f.created_at, f.recipient_email, f.admin_name, f.request_id, r.service_type
         FROM feedback_messages f
         LEFT JOIN service_requests r ON r.id = f.request_id
         WHERE f.recipient_user_id = :user_id OR f.recipient_email = :email
         ORDER BY f.created_at DESC'
    );

    $statement->execute([
        ':user_id' => $userId,
        ':email' => $email,
    ]);

    return $statement->fetchAll();
}

function clevis_normalize_request_code(string $requestCode): string
{
    $requestCode = strtoupper(trim($requestCode));
    $requestCode = preg_replace('/[^A-Z0-9-]+/', '', $requestCode);

    return $requestCode ?? '';
}

function clevis_request_code_prefix(string $clientName): string
{
    $prefix = strtoupper(trim($clientName));
    $prefix = preg_replace('/[^A-Z0-9]+/', '', $prefix);

    if ($prefix === '') {
        return 'CLIENT';
    }

    return substr($prefix, 0, 12);
}

function clevis_east_africa_timezone(): DateTimeZone
{
    static $timezone = null;

    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    $timezone = new DateTimeZone('Africa/Nairobi');

    return $timezone;
}

function clevis_format_east_africa_datetime(?string $value, string $format = 'M j, Y g:i a'): string
{
    $timezone = clevis_east_africa_timezone();
    $value = is_string($value) ? trim($value) : '';

    if ($value === '') {
        return (new DateTimeImmutable('now', $timezone))->format($format);
    }

    try {
        $datetime = new DateTimeImmutable($value, $timezone);
    } catch (Throwable $exception) {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return (new DateTimeImmutable('now', $timezone))->format($format);
        }

        $datetime = (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
    }

    return $datetime->setTimezone($timezone)->format($format);
}

function clevis_get_request_by_code(PDO $pdo, string $requestCode): ?array
{
    $requestCode = clevis_normalize_request_code($requestCode);
    if ($requestCode === '') {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, user_id, client_name, email, phone, service_type, budget_range, preferred_contact, project_details, request_code, request_status, deleted_at, deleted_by_admin_id, brief_file_path, logo_file_path, reference_file_path, brief_file_name, logo_file_name, reference_file_name, created_at
         FROM service_requests
         WHERE request_code = :request_code
           AND deleted_at IS NULL
         LIMIT 1'
    );
    $statement->execute([':request_code' => $requestCode]);

    $request = $statement->fetch();

    return $request ?: null;
}

function clevis_get_request_by_verification_code(PDO $pdo, string $verificationCode): ?array
{
    $verificationCode = clevis_normalize_request_code($verificationCode);
    if ($verificationCode === '') {
        return null;
    }

    $request = clevis_get_request_by_code($pdo, $verificationCode);
    if ($request) {
        return $request;
    }

    if (!preg_match('/^RCPT-(\d{8})-(\d{5,})$/', $verificationCode, $matches)) {
        return null;
    }

    $requestId = (int)$matches[2];
    if ($requestId <= 0) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, user_id, client_name, email, phone, service_type, budget_range, preferred_contact, project_details, request_code, request_status, deleted_at, deleted_by_admin_id, brief_file_path, logo_file_path, reference_file_path, brief_file_name, logo_file_name, reference_file_name, created_at
         FROM service_requests
         WHERE id = :id
           AND deleted_at IS NULL
         LIMIT 1'
    );
    $statement->execute([':id' => $requestId]);

    $request = $statement->fetch();
    if (!$request) {
        return null;
    }

    $receiptDate = clevis_format_east_africa_datetime((string)$request['created_at'], 'Ymd');
    if ($receiptDate !== $matches[1]) {
        return null;
    }

    return $request;
}

function clevis_get_verification_match_type(PDO $pdo, string $verificationCode): string
{
    $verificationCode = clevis_normalize_request_code($verificationCode);
    if ($verificationCode === '') {
        return 'unknown';
    }

    if (clevis_get_request_by_code($pdo, $verificationCode)) {
        return 'request_code';
    }

    if (!preg_match('/^RCPT-(\d{8})-(\d{5,})$/', $verificationCode, $matches)) {
        return 'unknown';
    }

    $requestId = (int)$matches[2];
    if ($requestId <= 0) {
        return 'unknown';
    }

    $statement = $pdo->prepare(
        'SELECT created_at
         FROM service_requests
         WHERE id = :id
           AND deleted_at IS NULL
         LIMIT 1'
    );
    $statement->execute([':id' => $requestId]);

    $request = $statement->fetch();
    if (!$request) {
        return 'unknown';
    }

    $receiptDate = clevis_format_east_africa_datetime((string)$request['created_at'], 'Ymd');
    if ($receiptDate !== $matches[1]) {
        return 'unknown';
    }

    return 'receipt_number';
}

function clevis_get_feedback_by_request_code(PDO $pdo, string $requestCode): array
{
    $requestCode = clevis_normalize_request_code($requestCode);
    if ($requestCode === '') {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT f.id, f.message, f.created_at, f.recipient_email, f.admin_name, f.request_id, r.service_type, r.client_name, r.request_code
         FROM feedback_messages f
         INNER JOIN service_requests r ON r.id = f.request_id
         WHERE r.request_code = :request_code
           AND r.deleted_at IS NULL
         ORDER BY f.created_at DESC'
    );

    $statement->execute([':request_code' => $requestCode]);

    return $statement->fetchAll();
}

function clevis_get_verification_options(): array
{
    return [
        'car' => ['label' => 'Car', 'image' => 'assets/images/captcha-car.jpg'],
        'umbrella' => ['label' => 'Umbrella', 'image' => 'assets/images/captcha-umbrella.jpg'],
        'house' => ['label' => 'House', 'image' => 'assets/images/captcha-house.jpg'],
    ];
}

function clevis_reset_verification(): void
{
    $keys = array_keys(clevis_get_verification_options());
    $_SESSION['verification_cycle'] = $keys;
    $_SESSION['verification_target'] = $keys[0];
    $_SESSION['verification_solved'] = false;
    unset($_SESSION['verification_choice']);
}

function clevis_bootstrap_verification(): array
{
    $options = clevis_get_verification_options();
    $cycle = $_SESSION['verification_cycle'] ?? [];

    if (!is_array($cycle) || $cycle === []) {
        clevis_reset_verification();
        $cycle = $_SESSION['verification_cycle'];
    }

    $cycle = array_values(array_intersect($cycle, array_keys($options)));
    if ($cycle === []) {
        clevis_reset_verification();
        $cycle = $_SESSION['verification_cycle'];
    }

    $_SESSION['verification_cycle'] = $cycle;

    $target = $_SESSION['verification_target'] ?? null;
    if (!is_string($target) || !in_array($target, $cycle, true)) {
        $target = $cycle[0];
        $_SESSION['verification_target'] = $target;
    }

    $cards = [];
    foreach ($cycle as $key) {
        $cards[] = [
            'key' => $key,
            'label' => $options[$key]['label'],
            'image' => $options[$key]['image'],
        ];
    }

    return [
        'target' => $target,
        'target_label' => $options[$target]['label'],
        'cards' => $cards,
        'solved' => !empty($_SESSION['verification_solved']),
    ];
}

function clevis_rotate_verification_after_wrong_choice(string $choice): array
{
    $options = clevis_get_verification_options();
    $cycle = $_SESSION['verification_cycle'] ?? array_keys($options);

    if (!is_array($cycle) || $cycle === []) {
        $cycle = array_keys($options);
    }

    $cycle = array_values(array_intersect($cycle, array_keys($options)));
    if ($cycle === []) {
        $cycle = array_keys($options);
    }

    $first = array_shift($cycle);
    $cycle[] = $first;

    $_SESSION['verification_cycle'] = $cycle;
    $_SESSION['verification_target'] = $cycle[0];
    $_SESSION['verification_solved'] = false;
    unset($_SESSION['verification_choice']);

    $target = $cycle[0];

    return [
        'target' => $target,
        'target_label' => $options[$target]['label'],
        'cards' => array_map(static function (string $key) use ($options): array {
            return [
                'key' => $key,
                'label' => $options[$key]['label'],
                'image' => $options[$key]['image'],
            ];
        }, $cycle),
    ];
}

function clevis_mark_verification_solved(string $choice): void
{
    $_SESSION['verification_solved'] = true;
    $_SESSION['verification_choice'] = $choice;
}

function clevis_is_verified_choice(?string $choice): bool
{
    $cycle = $_SESSION['verification_cycle'] ?? [];
    $target = $_SESSION['verification_target'] ?? null;

    if (!is_array($cycle) || $cycle === []) {
        return false;
    }

    if (!is_string($target) || !in_array($target, $cycle, true)) {
        return false;
    }

    return $choice !== null && $choice !== '' && !empty($_SESSION['verification_solved']) && hash_equals($target, $choice);
}
