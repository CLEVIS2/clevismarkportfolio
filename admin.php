<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$isAdmin = clevis_is_admin();

if (!$isAdmin) {
    header('Location: admin-login');
    exit;
}

$adminFlash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$adminStats = [
    'users' => 0,
    'registrations' => 0,
    'logins' => 0,
    'requests' => 0,
    'feedback' => 0,
];

$users = [];
$requests = [];
$feedback = [];
$auditLogs = [];
$analytics = [];
$analyticsMax = 1;
$requestVolume = [];
$topServices = [];
$requestStatusSummary = [];
$completionRate = 0;
$completionSummary = [
    'completed' => 0,
    'total' => 0,
];
$requestSearch = trim((string)($_GET['request_search'] ?? ''));
$requestStatusFilter = trim((string)($_GET['request_status'] ?? ''));
$requestServiceFilter = trim((string)($_GET['request_service'] ?? ''));

$allowedRequestStatuses = clevis_request_statuses();
$requestStatusFilter = in_array($requestStatusFilter, $allowedRequestStatuses, true) ? $requestStatusFilter : '';

if ($isAdmin && $pdo) {
    $adminStats['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    try {
        $adminStats['registrations'] = (int)$pdo->query('SELECT COUNT(*) FROM registration_records')->fetchColumn();
    } catch (PDOException $exception) {
        $adminStats['registrations'] = 0;
    }

    try {
        $adminStats['logins'] = (int)$pdo->query('SELECT COUNT(*) FROM login_records')->fetchColumn();
    } catch (PDOException $exception) {
        $adminStats['logins'] = 0;
    }

    $adminStats['requests'] = (int)$pdo->query('SELECT COUNT(*) FROM service_requests WHERE deleted_at IS NULL')->fetchColumn();
    $adminStats['feedback'] = (int)$pdo->query('SELECT COUNT(*) FROM feedback_messages')->fetchColumn();

    $users = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();

    $requestConditions = ['r.deleted_at IS NULL'];
    $requestParams = [];

    if ($requestSearch !== '') {
        $requestConditions[] = '(r.client_name LIKE :request_search OR r.email LIKE :request_search OR r.request_code LIKE :request_search)';
        $requestParams[':request_search'] = '%' . $requestSearch . '%';
    }

    if ($requestStatusFilter !== '') {
        $requestConditions[] = 'r.request_status = :request_status';
        $requestParams[':request_status'] = $requestStatusFilter;
    }

    if ($requestServiceFilter !== '') {
        $requestConditions[] = 'r.service_type LIKE :request_service';
        $requestParams[':request_service'] = '%' . $requestServiceFilter . '%';
    }

    $requestSql = '
        SELECT r.id, r.client_name, r.email, r.phone, r.service_type, r.budget_range, r.preferred_contact, r.project_details, r.request_code, r.request_status, r.created_at, r.user_id, r.brief_file_path, r.logo_file_path, r.reference_file_path, r.brief_file_name, r.logo_file_name, r.reference_file_name, u.name AS registered_name
        FROM service_requests r
        LEFT JOIN users u ON u.id = r.user_id
        WHERE ' . implode(' AND ', $requestConditions) . '
        ORDER BY r.created_at DESC';

    try {
        $requestStatement = $pdo->prepare($requestSql);
        $requestStatement->execute($requestParams);
        $requests = $requestStatement->fetchAll();
    } catch (PDOException $exception) {
        $requestStatement = $pdo->prepare(
            'SELECT id, client_name, email, phone, service_type, budget_range, preferred_contact, project_details, request_code, request_status, created_at
             FROM service_requests
             WHERE deleted_at IS NULL
             ORDER BY created_at DESC'
        );
        $requestStatement->execute();
        $requests = $requestStatement->fetchAll();
    }

    $feedback = $pdo->query(
        'SELECT f.id, f.message, f.created_at, f.admin_name, f.recipient_email, f.recipient_user_id, f.request_id, r.service_type, r.client_name, r.request_code
         FROM feedback_messages f
         LEFT JOIN service_requests r ON r.id = f.request_id
         ORDER BY f.created_at DESC'
    )->fetchAll();

    $auditLogs = clevis_get_audit_logs($pdo, 12);

    $months = [];
    $volumeMap = [];
    $volumeCursor = new DateTimeImmutable('first day of this month');
    for ($offset = 5; $offset >= 0; $offset--) {
        $month = $volumeCursor->sub(new DateInterval('P' . $offset . 'M'));
        $bucket = $month->format('Y-m');
        $months[] = [
            'bucket' => $bucket,
            'label' => $month->format('M Y'),
            'value' => 0,
        ];
        $volumeMap[$bucket] = count($months) - 1;
    }

    try {
        $volumeStatement = $pdo->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS bucket, COUNT(*) AS total
             FROM service_requests
             WHERE deleted_at IS NULL
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY bucket ASC"
        );
        foreach ($volumeStatement->fetchAll() as $row) {
            if (isset($volumeMap[$row['bucket']])) {
                $months[$volumeMap[$row['bucket']]]['value'] = (int)$row['total'];
            }
        }
    } catch (PDOException $exception) {
        // Keep the zeroed timeline if the database host rejects the grouped query.
    }

    $requestVolume = $months;
    $requestVolumeMax = max(1, ...array_map(static fn ($item) => (int)$item['value'], $requestVolume));

    try {
        $topServices = $pdo->query(
            'SELECT service_type, COUNT(*) AS total
             FROM service_requests
             WHERE deleted_at IS NULL
             GROUP BY service_type
             ORDER BY total DESC, service_type ASC
             LIMIT 5'
        )->fetchAll();
    } catch (PDOException $exception) {
        $topServices = [];
    }

    try {
        $completionSummary['total'] = (int)$pdo->query(
            'SELECT COUNT(*)
             FROM service_requests
             WHERE deleted_at IS NULL'
        )->fetchColumn();
        $completionSummary['completed'] = (int)$pdo->query(
            "SELECT COUNT(*)
             FROM service_requests
             WHERE deleted_at IS NULL
               AND request_status = 'Completed'"
        )->fetchColumn();
    } catch (PDOException $exception) {
        $completionSummary['total'] = 0;
        $completionSummary['completed'] = 0;
    }

    $completionRate = $completionSummary['total'] > 0
        ? round(($completionSummary['completed'] / $completionSummary['total']) * 100)
        : 0;

    $statusCounts = array_fill_keys(clevis_request_statuses(), 0);
    try {
        $statusStatement = $pdo->query(
            'SELECT request_status, COUNT(*) AS total
             FROM service_requests
             WHERE deleted_at IS NULL
             GROUP BY request_status'
        );
        foreach ($statusStatement->fetchAll() as $row) {
            $statusLabel = clevis_normalize_request_status((string)($row['request_status'] ?? 'New'));
            if (array_key_exists($statusLabel, $statusCounts)) {
                $statusCounts[$statusLabel] = (int)$row['total'];
            }
        }
    } catch (PDOException $exception) {
        // Keep the default zeroed counts if the grouped query is not available.
    }

    foreach (clevis_request_statuses() as $statusLabel) {
        $requestStatusSummary[] = [
            'label' => $statusLabel,
            'value' => $statusCounts[$statusLabel] ?? 0,
        ];
    }

    $analytics = [
        ['label' => 'Users', 'value' => $adminStats['users'], 'color' => '#58c3ff'],
        ['label' => 'Registrations', 'value' => $adminStats['registrations'], 'color' => '#ff8a3d'],
        ['label' => 'Logins', 'value' => $adminStats['logins'], 'color' => '#7ee2a8'],
        ['label' => 'Requests', 'value' => $adminStats['requests'], 'color' => '#c89bff'],
        ['label' => 'Feedback', 'value' => $adminStats['feedback'], 'color' => '#ffd36a'],
    ];

    $analyticsMax = max(1, ...array_map(static fn ($item) => (int)$item['value'], $analytics));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Area | Clevis Mark</title>
    <meta name="description" content="Hidden admin dashboard for reviewing requests and sending feedback to users.">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="admin-page">
    <div class="page-shell admin-shell">
        <header class="topbar">
            <a class="brand" href="admin.php">
                <span class="brand-mark" aria-hidden="true">CM</span>
                <span class="brand-name">Clevis Admin</span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-nav">
                <span class="menu-toggle-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>
            <button class="nav-overlay" type="button" aria-label="Close navigation" tabindex="-1"></button>
            <div class="topnav" id="site-nav">
                <a class="nav-home" href="index.php">Public Site</a>
                <a class="nav-verify" href="verify.php">Verify Receipt</a>
                <a class="nav-pdf" href="portfolio_pdf.php">PDF Portfolio</a>
                <form class="logout-form" action="auth_action.php" method="post">
                    <input type="hidden" name="action" value="logout">
                    <button class="link-button nav-logout" type="submit">Logout</button>
                </form>
            </div>
        </header>

        <main>
                <section class="admin-hero">
                    <div>
                        <p class="eyebrow">Admin Dashboard</p>
                        <h1>Review user requests and send feedback back into the system.</h1>
                        <p class="hero-text">
                            This dashboard is connected to the MySQL database. You can review user requirements, inspect submitted projects, and store feedback for each user.
                        </p>
                    </div>
                    <div class="admin-stats">
                        <article class="admin-stat">
                            <strong><?= number_format($adminStats['users']); ?></strong>
                            <span>Users</span>
                        </article>
                        <article class="admin-stat">
                            <strong><?= number_format($adminStats['registrations']); ?></strong>
                            <span>Registrations</span>
                        </article>
                        <article class="admin-stat">
                            <strong><?= number_format($adminStats['logins']); ?></strong>
                            <span>Logins</span>
                        </article>
                        <article class="admin-stat">
                            <strong><?= number_format($adminStats['requests']); ?></strong>
                            <span>Requests</span>
                        </article>
                        <article class="admin-stat">
                            <strong><?= number_format($adminStats['feedback']); ?></strong>
                            <span>Feedback items</span>
                        </article>
                    </div>
                </section>

                <section class="admin-panel admin-chart-panel">
                    <div class="section-heading">
                        <p class="eyebrow">Analysis</p>
                        <h2>System activity overview</h2>
                    </div>
                    <div class="admin-chart-wrap">
                        <svg class="admin-chart" viewBox="0 0 800 260" role="img" aria-label="Activity chart for users, registrations, logins, requests, and feedback">
                            <?php
                            $chartLeft = 110;
                            $chartTop = 28;
                            $barHeight = 30;
                            $gap = 16;
                            $chartWidth = 620;
                            foreach ($analytics as $index => $item):
                                $y = $chartTop + ($index * ($barHeight + $gap));
                                $width = $analyticsMax > 0 ? max(6, (int)round(($item['value'] / $analyticsMax) * $chartWidth)) : 6;
                            ?>
                                <text x="0" y="<?= $y + 20; ?>" class="admin-chart-label"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></text>
                                <rect x="<?= $chartLeft; ?>" y="<?= $y; ?>" width="<?= $width; ?>" height="<?= $barHeight; ?>" rx="15" fill="<?= htmlspecialchars($item['color'], ENT_QUOTES, 'UTF-8'); ?>"></rect>
                                <text x="<?= $chartLeft + $width + 14; ?>" y="<?= $y + 20; ?>" class="admin-chart-value"><?= (int)$item['value']; ?></text>
                            <?php endforeach; ?>
                        </svg>
                    </div>
                </section>

                <section class="admin-insights-grid">
                    <article class="admin-panel admin-insight-panel">
                        <div class="section-heading">
                            <p class="eyebrow">Trends</p>
                            <h2>Request volume over time</h2>
                        </div>
                        <div class="admin-bar-list">
                            <?php foreach ($requestVolume as $month): ?>
                                <?php $barWidth = max(8, (int)round(($month['value'] / $requestVolumeMax) * 100)); ?>
                                <div class="admin-bar-item">
                                    <div class="admin-bar-head">
                                        <span><?= htmlspecialchars($month['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <strong><?= (int)$month['value']; ?></strong>
                                    </div>
                                    <div class="admin-bar-track">
                                        <div class="admin-bar-fill" style="width: <?= $barWidth; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <article class="admin-panel admin-insight-panel">
                        <div class="section-heading">
                            <p class="eyebrow">Demand</p>
                            <h2>Most requested services</h2>
                        </div>
                        <div class="admin-service-list">
                            <?php if ($topServices !== []): ?>
                                <?php
                                    $topServiceMax = max(1, ...array_map(static fn ($item) => (int)$item['total'], $topServices));
                                ?>
                                <?php foreach ($topServices as $service): ?>
                                    <?php $serviceWidth = max(8, (int)round(((int)$service['total'] / $topServiceMax) * 100)); ?>
                                    <div class="admin-service-item">
                                        <div class="admin-bar-head">
                                            <span><?= htmlspecialchars($service['service_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <strong><?= (int)$service['total']; ?></strong>
                                        </div>
                                        <div class="admin-bar-track">
                                            <div class="admin-bar-fill alt" style="width: <?= $serviceWidth; ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="admin-muted">No request data is available yet.</p>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="admin-panel admin-insight-panel admin-completion-panel">
                        <div class="section-heading">
                            <p class="eyebrow">Completion</p>
                            <h2>Completion rate</h2>
                        </div>
                        <div class="completion-ring" aria-hidden="true" style="--completion-angle: <?= (int)round(($completionRate / 100) * 360); ?>deg;">
                            <span><?= (int)$completionRate; ?>%</span>
                        </div>
                        <p class="completion-summary">
                            <?= (int)$completionSummary['completed']; ?> of <?= (int)$completionSummary['total']; ?> active requests are marked completed.
                        </p>
                    </article>

                    <article class="admin-panel admin-insight-panel">
                        <div class="section-heading">
                            <p class="eyebrow">Workload</p>
                            <h2>Request status breakdown</h2>
                        </div>
                        <div class="admin-bar-list">
                            <?php
                                $statusMax = max(1, ...array_map(static fn ($item) => (int)$item['value'], $requestStatusSummary));
                            ?>
                            <?php foreach ($requestStatusSummary as $statusRow): ?>
                                <?php $statusWidth = max(8, (int)round(($statusRow['value'] / $statusMax) * 100)); ?>
                                <div class="admin-bar-item">
                                    <div class="admin-bar-head">
                                        <span><?= htmlspecialchars($statusRow['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <strong><?= (int)$statusRow['value']; ?></strong>
                                    </div>
                                    <div class="admin-bar-track">
                                        <div class="admin-bar-fill status" style="width: <?= $statusWidth; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>

                <?php if ($adminFlash): ?>
                    <div class="flash <?= htmlspecialchars($adminFlash['type'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($adminFlash['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($database_error): ?>
                    <div class="flash info">
                        <?= htmlspecialchars($database_error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <section class="admin-grid" id="users">
                    <div class="admin-panel">
                        <div class="section-heading">
                            <p class="eyebrow">Users</p>
                            <h2>Registered accounts</h2>
                        </div>

                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($users !== []): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars(clevis_format_east_africa_datetime($user['created_at'], 'M j, Y'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="admin-muted">No registered users yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="admin-panel" id="requests">
                        <div class="section-heading">
                            <p class="eyebrow">Requests</p>
                            <h2>User requirements and needs</h2>
                        </div>

                        <form class="admin-filter-form" method="get" action="admin.php#requests">
                            <label>
                                Search
                                <input type="search" name="request_search" placeholder="Client, email, or request code" value="<?= htmlspecialchars($requestSearch, ENT_QUOTES, 'UTF-8'); ?>">
                            </label>
                            <label>
                                Status
                                <select name="request_status">
                                    <option value="">All statuses</option>
                                    <?php foreach (clevis_request_statuses() as $statusOption): ?>
                                        <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?= $requestStatusFilter === $statusOption ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Service
                                <input type="text" name="request_service" placeholder="Web Design" value="<?= htmlspecialchars($requestServiceFilter, ENT_QUOTES, 'UTF-8'); ?>">
                            </label>
                            <div class="admin-filter-actions">
                                <button class="button primary" type="submit">Apply Filters</button>
                                <a class="button secondary" href="admin.php#requests">Reset</a>
                            </div>
                        </form>

                        <div class="admin-request-list">
                            <?php if ($requests !== []): ?>
                                <?php foreach ($requests as $request): ?>
                                    <?php
                                        $requestStatus = clevis_normalize_request_status((string)($request['request_status'] ?? 'New'));
                                        $requestStatusSlug = strtolower(str_replace(' ', '-', $requestStatus));
                                        $uploadedFiles = clevis_request_uploaded_files($request);
                                        $internalNotes = clevis_get_internal_notes($pdo, (int)$request['id'], 4);
                                    ?>
                                    <article class="admin-request-card">
                                        <div class="admin-request-meta">
                                            <strong><?= htmlspecialchars($request['client_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span><?= htmlspecialchars($request['service_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <p>
                                            <strong>Status:</strong>
                                            <span class="status-pill status-<?= htmlspecialchars($requestStatusSlug, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($requestStatus, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </p>
                                        <?php if (!empty($request['request_code'])): ?>
                                            <p><strong>Request Code:</strong> <span class="request-code-pill"><?= htmlspecialchars($request['request_code'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                                        <?php endif; ?>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($request['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Phone:</strong> <?= htmlspecialchars($request['phone'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Budget:</strong> <?= htmlspecialchars($request['budget_range'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Contact:</strong> <?= htmlspecialchars($request['preferred_contact'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Details:</strong> <?= htmlspecialchars($request['project_details'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php if ($uploadedFiles['brief'] !== [] || $uploadedFiles['logo'] !== [] || $uploadedFiles['references'] !== []): ?>
                                            <div class="admin-request-attachments">
                                                <strong>Attachments</strong>
                                                <?php if ($uploadedFiles['brief'] !== []): ?>
                                                    <div class="admin-request-file-group">
                                                        <span>Brief</span>
                                                        <?php foreach ($uploadedFiles['brief'] as $file): ?>
                                                            <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                                <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($uploadedFiles['logo'] !== []): ?>
                                                    <div class="admin-request-file-group">
                                                        <span>Logo</span>
                                                        <?php foreach ($uploadedFiles['logo'] as $file): ?>
                                                            <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                                <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($uploadedFiles['references'] !== []): ?>
                                                    <div class="admin-request-file-group">
                                                        <span>References</span>
                                                        <?php foreach ($uploadedFiles['references'] as $file): ?>
                                                            <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                                <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <p class="admin-muted">Submitted <?= htmlspecialchars(clevis_format_east_africa_datetime($request['created_at']), ENT_QUOTES, 'UTF-8'); ?></p>

                                        <form class="admin-status-form" action="admin_action.php" method="post">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="request_id" value="<?= (int)$request['id']; ?>">
                                            <label>
                                                Update status
                                                <select name="request_status">
                                                    <?php foreach (clevis_request_statuses() as $statusOption): ?>
                                                        <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?= $requestStatus === $statusOption ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <button class="button secondary full" type="submit">Save Status</button>
                                        </form>

                                        <form class="admin-feedback-form" action="admin_action.php" method="post">
                                            <input type="hidden" name="action" value="send_feedback">
                                            <input type="hidden" name="request_id" value="<?= (int)$request['id']; ?>">
                                            <label>
                                                Send to user
                                                <select name="recipient_user_id">
                                                    <option value="">Use request email</option>
                                                    <?php foreach ($users as $user): ?>
                                                        <?php
                                                            $selectedRecipient = '';
                                                            if (!empty($request['user_id']) && (int)$request['user_id'] === (int)$user['id']) {
                                                                $selectedRecipient = 'selected';
                                                            }
                                                        ?>
                                                        <option value="<?= (int)$user['id']; ?>" <?= $selectedRecipient; ?>>
                                                            <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label>
                                                Send feedback
                                                <textarea name="message" rows="4" placeholder="Write a response for this user..." required></textarea>
                                            </label>
                                            <button class="button primary full" type="submit">Send Feedback</button>
                                        </form>

                                        <div class="admin-note-block">
                                            <div class="admin-note-head">
                                                <strong>Internal Notes</strong>
                                                <span><?= count($internalNotes); ?> saved</span>
                                            </div>

                                            <form class="admin-note-form" action="admin_action.php" method="post">
                                                <input type="hidden" name="action" value="add_internal_note">
                                                <input type="hidden" name="request_id" value="<?= (int)$request['id']; ?>">
                                                <label>
                                                    Add private note
                                                    <textarea name="note_text" rows="3" placeholder="Write an internal note for the team..." required></textarea>
                                                </label>
                                                <button class="button secondary full" type="submit">Save Internal Note</button>
                                            </form>

                                            <?php if ($internalNotes !== []): ?>
                                                <div class="internal-note-list">
                                                    <?php foreach ($internalNotes as $note): ?>
                                                        <article class="internal-note-card">
                                                            <p><?= nl2br(htmlspecialchars($note['note_text'], ENT_QUOTES, 'UTF-8')); ?></p>
                                                            <p class="admin-muted">
                                                                <?= htmlspecialchars($note['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
                                                                on <?= htmlspecialchars(clevis_format_east_africa_datetime($note['created_at']), ENT_QUOTES, 'UTF-8'); ?>
                                                            </p>
                                                        </article>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="admin-muted">No private notes yet.</p>
                                            <?php endif; ?>
                                        </div>

                                        <form class="admin-delete-form" action="admin_action.php" method="post" onsubmit="return confirm('Delete this request and all related feedback? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete_request">
                                            <input type="hidden" name="request_id" value="<?= (int)$request['id']; ?>">
                                            <button class="button danger full" type="submit">Delete Request</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <article class="admin-request-card">
                                    <p class="admin-muted">No service requests have been submitted yet.</p>
                                </article>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="admin-panel admin-feedback-section" id="feedback">
                    <div class="section-heading">
                        <p class="eyebrow">Feedback Log</p>
                        <h2>Messages stored for users</h2>
                    </div>

                    <div class="feedback-list">
                        <?php if ($feedback !== []): ?>
                            <?php foreach ($feedback as $item): ?>
                                <article class="feedback-card">
                                    <div class="feedback-card-head">
                                        <strong><?= htmlspecialchars($item['client_name'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span>
                                            <?= htmlspecialchars($item['service_type'] ?? 'General', ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if (!empty($item['request_code'])): ?>
                                                | <?= htmlspecialchars($item['request_code'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8')); ?></p>
                                    <p class="admin-muted">
                                        From <?= htmlspecialchars($item['admin_name'], ENT_QUOTES, 'UTF-8'); ?> on <?= htmlspecialchars(clevis_format_east_africa_datetime($item['created_at']), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <article class="feedback-card feedback-empty">
                                <p>No feedback has been stored yet.</p>
                            </article>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="admin-panel admin-audit-section" id="audit">
                    <div class="section-heading">
                        <p class="eyebrow">Audit Log</p>
                        <h2>Admin activity history</h2>
                    </div>

                    <div class="feedback-list">
                        <?php if ($auditLogs !== []): ?>
                            <?php foreach ($auditLogs as $entry): ?>
                                <article class="feedback-card">
                                    <div class="feedback-card-head">
                                        <strong><?= htmlspecialchars($entry['action_type'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?= htmlspecialchars(clevis_format_east_africa_datetime($entry['created_at']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <p>
                                        <?= htmlspecialchars($entry['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($entry['client_name'])): ?>
                                            acted on <?= htmlspecialchars($entry['client_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($entry['request_code'])): ?>
                                            (<?= htmlspecialchars($entry['request_code'], ENT_QUOTES, 'UTF-8'); ?>)
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($entry['old_value']) || !empty($entry['new_value'])): ?>
                                        <p class="admin-muted">
                                            <?= !empty($entry['old_value']) ? 'From ' . htmlspecialchars($entry['old_value'], ENT_QUOTES, 'UTF-8') : ''; ?>
                                            <?= !empty($entry['old_value']) && !empty($entry['new_value']) ? ' ' : ''; ?>
                                            <?= !empty($entry['new_value']) ? 'to ' . htmlspecialchars($entry['new_value'], ENT_QUOTES, 'UTF-8') : ''; ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['details'])): ?>
                                        <p class="admin-muted"><?= htmlspecialchars($entry['details'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <article class="feedback-card feedback-empty">
                                <p>No admin actions have been logged yet.</p>
                            </article>
                        <?php endif; ?>
                    </div>
                </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
