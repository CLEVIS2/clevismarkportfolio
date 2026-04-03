<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/receipt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

if (!clevis_is_admin()) {
    header('Location: index.php');
    exit;
}

$action = trim($_POST['action'] ?? '');

if ($action === 'add_internal_note') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $noteText = trim($_POST['note_text'] ?? '');

    if ($requestId <= 0 || $noteText === '') {
        clevis_set_admin_flash('error', 'Select a request and enter an internal note.');
        header('Location: admin.php#requests');
        exit;
    }

    if (!$pdo) {
        clevis_set_admin_flash('error', $database_error ?? 'Database connection failed.');
        header('Location: admin.php#requests');
        exit;
    }

    $requestStatement = $pdo->prepare(
        'SELECT id, client_name, request_code, request_status
         FROM service_requests
         WHERE id = :id
           AND deleted_at IS NULL
         LIMIT 1'
    );
    $requestStatement->execute([':id' => $requestId]);
    $request = $requestStatement->fetch();

    if (!$request) {
        clevis_set_admin_flash('error', 'That service request could not be found.');
        header('Location: admin.php#requests');
        exit;
    }

    $noteStatement = $pdo->prepare(
        'INSERT INTO internal_notes
            (request_id, admin_user_id, admin_name, note_text)
         VALUES
            (:request_id, :admin_user_id, :admin_name, :note_text)'
    );

    $noteStatement->execute([
        ':request_id' => $requestId,
        ':admin_user_id' => (int)($_SESSION['user_id'] ?? 0),
        ':admin_name' => (string)($_SESSION['user_name'] ?? 'Admin'),
        ':note_text' => $noteText,
    ]);

    clevis_log_admin_action($pdo, 'add_internal_note', [
        'request_id' => $requestId,
        'request_code' => $request['request_code'] ?? null,
        'details' => 'Internal note added for ' . ($request['client_name'] ?? 'request'),
    ]);

    clevis_set_admin_flash('success', 'Internal note added for ' . $request['client_name'] . '.');
    header('Location: admin.php#requests');
    exit;
}

if ($action === 'send_feedback') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $recipientUserId = trim($_POST['recipient_user_id'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($requestId <= 0 || $message === '') {
        clevis_set_admin_flash('error', 'Select a request and enter a feedback message.');
        header('Location: admin.php#requests');
        exit;
    }

    if (!$pdo) {
        clevis_set_admin_flash('error', $database_error ?? 'Database connection failed.');
        header('Location: admin.php#requests');
        exit;
    }

    try {
        $requestStatement = $pdo->prepare(
            'SELECT id, user_id, email, client_name, service_type, request_code, request_status
             FROM service_requests
             WHERE id = :id
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $requestStatement->execute([':id' => $requestId]);
        $request = $requestStatement->fetch();
    } catch (PDOException $exception) {
        $requestStatement = $pdo->prepare(
            'SELECT id, email, client_name, service_type, request_code, request_status
             FROM service_requests
             WHERE id = :id
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $requestStatement->execute([':id' => $requestId]);
        $request = $requestStatement->fetch();
        if (is_array($request)) {
            $request['user_id'] = null;
        }
    }

    if (!$request) {
        clevis_set_admin_flash('error', 'That service request could not be found.');
        header('Location: admin.php#requests');
        exit;
    }

    $recipientEmail = $request['email'];
    $recipientUserIdValue = null;

    if ($recipientUserId !== '') {
        $recipientLookup = $pdo->prepare('SELECT id, email FROM users WHERE id = :id LIMIT 1');
        $recipientLookup->execute([':id' => (int)$recipientUserId]);
        $recipient = $recipientLookup->fetch();

        if ($recipient) {
            $recipientUserIdValue = (int)$recipient['id'];
            $recipientEmail = $recipient['email'];
        }
    } elseif (!empty($request['user_id'])) {
        $recipientUserIdValue = (int)$request['user_id'];
    }

    $feedbackStatement = $pdo->prepare(
        'INSERT INTO feedback_messages
            (request_id, recipient_user_id, recipient_email, admin_user_id, admin_name, message)
         VALUES
            (:request_id, :recipient_user_id, :recipient_email, :admin_user_id, :admin_name, :message)'
    );

    $feedbackStatement->execute([
        ':request_id' => $request['id'],
        ':recipient_user_id' => $recipientUserIdValue,
        ':recipient_email' => $recipientEmail,
        ':admin_user_id' => (int)$_SESSION['user_id'],
        ':admin_name' => (string)($_SESSION['user_name'] ?? 'Admin'),
        ':message' => $message,
    ]);

    clevis_log_admin_action($pdo, 'send_feedback', [
        'request_id' => $request['id'],
        'request_code' => $request['request_code'] ?? null,
        'details' => 'Feedback sent for service: ' . ($request['service_type'] ?? 'General'),
    ]);

    $feedbackEmailBody = implode("\n", [
        'Hello ' . ($request['client_name'] ?? 'Client') . ',',
        '',
        'A new feedback message has been posted for your request.',
        'Request Code: ' . ($request['request_code'] ?? 'N/A'),
        'Service: ' . ($request['service_type'] ?? 'General'),
        'Current Status: ' . clevis_normalize_request_status((string)($request['request_status'] ?? 'New')),
        '',
        'Message:',
        $message,
        '',
        'Please return to the receipt page to view the latest updates.',
    ]);

    clevis_send_mail_message($recipientEmail, 'New feedback for ' . ($request['request_code'] ?? 'request'), $feedbackEmailBody);

    clevis_set_admin_flash('success', 'Feedback sent to ' . $request['client_name'] . '.');
    header('Location: admin.php#requests');
    exit;
}

if ($action === 'update_status') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $requestStatus = clevis_normalize_request_status((string)($_POST['request_status'] ?? ''));

    if ($requestId <= 0) {
        clevis_set_admin_flash('error', 'Select a request to update.');
        header('Location: admin.php#requests');
        exit;
    }

    if (!$pdo) {
        clevis_set_admin_flash('error', $database_error ?? 'Database connection failed.' );
        header('Location: admin.php#requests');
        exit;
    }

    $requestStatement = $pdo->prepare(
        'SELECT id, client_name, request_code, request_status
         FROM service_requests
         WHERE id = :id
           AND deleted_at IS NULL
         LIMIT 1'
    );
    $requestStatement->execute([':id' => $requestId]);
    $request = $requestStatement->fetch();

    if (!$request) {
        clevis_set_admin_flash('error', 'That service request could not be found.');
        header('Location: admin.php#requests');
        exit;
    }

    $currentStatus = clevis_normalize_request_status((string)($request['request_status'] ?? 'New'));

    if ($currentStatus === $requestStatus) {
        clevis_set_admin_flash('success', 'Request status is already "' . $requestStatus . '".');
        header('Location: admin.php#requests');
        exit;
    }

    $updateStatement = $pdo->prepare(
        'UPDATE service_requests
         SET request_status = :request_status
         WHERE id = :id'
    );
    $updateStatement->execute([
        ':request_status' => $requestStatus,
        ':id' => $requestId,
    ]);

    clevis_log_admin_action($pdo, 'update_status', [
        'request_id' => $requestId,
        'request_code' => $request['request_code'] ?? null,
        'old_value' => $currentStatus,
        'new_value' => $requestStatus,
        'details' => 'Status changed for ' . ($request['client_name'] ?? 'request'),
    ]);

    $statusEmailBody = implode("\n", [
        'Hello ' . ($request['client_name'] ?? 'Client') . ',',
        '',
        'Your request status has been updated.',
        'Request Code: ' . ($request['request_code'] ?? 'N/A'),
        'Previous Status: ' . $currentStatus,
        'New Status: ' . $requestStatus,
        '',
        'You can use your request code to check the latest receipt details.',
    ]);

    clevis_send_mail_message((string)($request['email'] ?? ''), 'Request status updated - ' . ($request['request_code'] ?? 'request'), $statusEmailBody);

    clevis_set_admin_flash('success', 'Updated request status to ' . $requestStatus . '.');
    header('Location: admin.php#requests');
    exit;
}

if ($action === 'delete_request') {
    $requestId = (int)($_POST['request_id'] ?? 0);

    if ($requestId <= 0) {
        clevis_set_admin_flash('error', 'Select a request to delete.');
        header('Location: admin.php#requests');
        exit;
    }

    if (!$pdo) {
        clevis_set_admin_flash('error', $database_error ?? 'Database connection failed.');
        header('Location: admin.php#requests');
        exit;
    }

    try {
        $requestStatement = $pdo->prepare(
            'SELECT id, client_name, request_code, request_status
             FROM service_requests
             WHERE id = :id
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $requestStatement->execute([':id' => $requestId]);
        $request = $requestStatement->fetch();

        if (!$request) {
            clevis_set_admin_flash('error', 'That service request could not be found.');
            header('Location: admin.php#requests');
            exit;
        }

        $pdo->beginTransaction();

        $softDelete = $pdo->prepare(
            'UPDATE service_requests
             SET deleted_at = CURRENT_TIMESTAMP,
                 deleted_by_admin_id = :deleted_by_admin_id
             WHERE id = :id
               AND deleted_at IS NULL'
        );
        $softDelete->execute([
            ':deleted_by_admin_id' => (int)($_SESSION['user_id'] ?? 0),
            ':id' => $requestId,
        ]);

        if ($softDelete->rowCount() === 0) {
            throw new RuntimeException('Request already archived or unavailable.');
        }

        clevis_log_admin_action($pdo, 'delete_request', [
            'request_id' => $requestId,
            'request_code' => $request['request_code'] ?? null,
            'old_value' => $request['request_status'] ?? null,
            'new_value' => 'Archived',
            'details' => 'Archived request for ' . ($request['client_name'] ?? 'client'),
        ]);

        $pdo->commit();

        clevis_set_admin_flash(
            'success',
            'Archived request "' . $request['client_name'] . '"'
            . (!empty($request['request_code']) ? ' (' . $request['request_code'] . ')' : '')
            . '.'
        );
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        clevis_set_admin_flash('error', 'The request could not be archived.');
    }

    header('Location: admin.php#requests');
    exit;
}

clevis_set_admin_flash('error', 'Unsupported admin action.');
header('Location: admin.php');
exit;
