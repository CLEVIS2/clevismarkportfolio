<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/receipt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$input = [
    'client_name' => trim($_POST['client_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'service_type' => trim($_POST['service_type'] ?? ''),
    'budget_range' => trim($_POST['budget_range'] ?? ''),
    'preferred_contact' => trim($_POST['preferred_contact'] ?? ''),
    'project_details' => trim($_POST['project_details'] ?? ''),
];

$uploadRoot = __DIR__ . '/uploads/requests';
if (!is_dir($uploadRoot)) {
    @mkdir($uploadRoot, 0755, true);
}

$allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'png', 'jpg', 'jpeg', 'webp', 'svg', 'zip', 'rar'];
$maxUploadSize = 10 * 1024 * 1024;

$storeUploadedFile = static function (string $fieldName, string $prefix, string $requestCode) use ($uploadRoot, $allowedExtensions, $maxUploadSize): array {
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return ['path' => null, 'name' => null];
    }

    if (is_array($_FILES[$fieldName]['name'])) {
        return ['path' => null, 'name' => null];
    }

    $file = $_FILES[$fieldName];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'name' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
        throw new RuntimeException('One of the uploaded files could not be saved.');
    }

    if (($file['size'] ?? 0) > $maxUploadSize) {
        throw new RuntimeException('Uploaded files must be 10 MB or smaller.');
    }

    $originalName = basename((string)($file['name'] ?? 'file'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Unsupported file type uploaded.');
    }

    $safePrefix = preg_replace('/[^A-Z0-9-]+/', '', strtoupper($requestCode));
    $random = bin2hex(random_bytes(4));
    $storedName = $safePrefix . '-' . $prefix . '-' . $random . '.' . $extension;
    $storedPath = $uploadRoot . '/' . $storedName;

    if (!move_uploaded_file((string)$file['tmp_name'], $storedPath)) {
        throw new RuntimeException('Failed to store uploaded file.');
    }

    return [
        'path' => 'uploads/requests/' . $storedName,
        'name' => $originalName,
    ];
};

$_SESSION['old'] = $input;

$errors = [];

if ($input['client_name'] === '') {
    $errors[] = 'Client name is required.';
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if ($input['service_type'] === '') {
    $errors[] = 'Please select a service.';
}

if ($input['project_details'] === '') {
    $errors[] = 'Project details are required.';
}

if ($errors !== []) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => implode(' ', $errors),
    ];

    header('Location: index.php#request');
    exit;
}

if (!$pdo) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => $database_error ?? 'Database connection failed.',
    ];

    header('Location: index.php#request');
    exit;
}

$requestCode = null;
$savedRequest = null;

try {
    $statement = $pdo->prepare(
        'INSERT INTO service_requests (user_id, request_code, request_status, client_name, email, phone, service_type, budget_range, preferred_contact, project_details, brief_file_path, logo_file_path, reference_file_path, brief_file_name, logo_file_name, reference_file_name)
         VALUES (:user_id, :request_code, :request_status, :client_name, :email, :phone, :service_type, :budget_range, :preferred_contact, :project_details, :brief_file_path, :logo_file_path, :reference_file_path, :brief_file_name, :logo_file_name, :reference_file_name)'
    );

    $saved = false;

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $uploadedFiles = [];
        $prefix = clevis_request_code_prefix($input['client_name']);
        $suffix = strtoupper(bin2hex(random_bytes(3)));
        $requestCode = $prefix . '-' . $suffix;

        try {
            $pdo->beginTransaction();

            $statement->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':request_code' => $requestCode,
                ':request_status' => 'New',
                ':client_name' => $input['client_name'],
                ':email' => $input['email'],
                ':phone' => $input['phone'],
                ':service_type' => $input['service_type'],
                ':budget_range' => $input['budget_range'] !== '' ? $input['budget_range'] : null,
                ':preferred_contact' => $input['preferred_contact'] !== '' ? $input['preferred_contact'] : 'Email',
                ':project_details' => $input['project_details'],
                ':brief_file_path' => null,
                ':logo_file_path' => null,
                ':reference_file_path' => null,
                ':brief_file_name' => null,
                ':logo_file_name' => null,
                ':reference_file_name' => null,
            ]);

            $briefUpload = $storeUploadedFile('brief_file', 'brief', $requestCode);
            if ($briefUpload['path']) {
                $uploadedFiles[] = $uploadRoot . '/' . basename($briefUpload['path']);
            }

            $logoUpload = $storeUploadedFile('logo_file', 'logo', $requestCode);
            if ($logoUpload['path']) {
                $uploadedFiles[] = $uploadRoot . '/' . basename($logoUpload['path']);
            }

            $referencePaths = [];
            $referenceNames = [];
            if (!empty($_FILES['reference_files']) && is_array($_FILES['reference_files']['name'] ?? null)) {
                $referenceCount = count($_FILES['reference_files']['name']);
                for ($index = 0; $index < $referenceCount; $index++) {
                    if (($_FILES['reference_files']['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if (($_FILES['reference_files']['error'][$index] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$_FILES['reference_files']['tmp_name'][$index])) {
                        throw new RuntimeException('One of the reference files could not be saved.');
                    }

                    if (($_FILES['reference_files']['size'][$index] ?? 0) > $maxUploadSize) {
                        throw new RuntimeException('Reference files must be 10 MB or smaller.');
                    }

                    $originalName = basename((string)($_FILES['reference_files']['name'][$index] ?? 'file'));
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
                        throw new RuntimeException('Unsupported reference file type uploaded.');
                    }

                    $safePrefix = preg_replace('/[^A-Z0-9-]+/', '', strtoupper($requestCode));
                    $random = bin2hex(random_bytes(4));
                    $storedName = $safePrefix . '-ref-' . $index . '-' . $random . '.' . $extension;
                    $storedPath = $uploadRoot . '/' . $storedName;

                    if (!move_uploaded_file((string)$_FILES['reference_files']['tmp_name'][$index], $storedPath)) {
                        throw new RuntimeException('Failed to store a reference file.');
                    }

                    $uploadedFiles[] = $storedPath;
                    $referencePaths[] = 'uploads/requests/' . $storedName;
                    $referenceNames[] = $originalName;
                }
            }

            $updateStatement = $pdo->prepare(
                'UPDATE service_requests
                 SET brief_file_path = :brief_file_path,
                     logo_file_path = :logo_file_path,
                     reference_file_path = :reference_file_path,
                     brief_file_name = :brief_file_name,
                     logo_file_name = :logo_file_name,
                     reference_file_name = :reference_file_name
                 WHERE request_code = :request_code'
            );
            $updateStatement->execute([
                ':brief_file_path' => $briefUpload['path'],
                ':logo_file_path' => $logoUpload['path'],
                ':reference_file_path' => $referencePaths !== [] ? implode("\n", $referencePaths) : null,
                ':brief_file_name' => $briefUpload['name'],
                ':logo_file_name' => $logoUpload['name'],
                ':reference_file_name' => $referenceNames !== [] ? implode("\n", $referenceNames) : null,
                ':request_code' => $requestCode,
            ]);

            $pdo->commit();
            $saved = true;
            break;
        } catch (PDOException $statementException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            foreach (array_reverse($uploadedFiles) as $storedPath) {
                if (is_file($storedPath)) {
                    @unlink($storedPath);
                }
            }

            if ((string)$statementException->getCode() === '23000') {
                continue;
            }

            throw $statementException;
        } catch (Throwable $attemptException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            foreach (array_reverse($uploadedFiles) as $storedPath) {
                if (is_file($storedPath)) {
                    @unlink($storedPath);
                }
            }

            throw $attemptException;
        }
    }

    if (!$saved || $requestCode === null) {
        throw new PDOException('Unable to generate a unique request code.');
    }

    $requestStatement = $pdo->prepare(
        'SELECT id, user_id, request_code, request_status, client_name, email, phone, service_type, budget_range, preferred_contact, project_details, brief_file_path, logo_file_path, reference_file_path, brief_file_name, logo_file_name, reference_file_name, created_at
         FROM service_requests
         WHERE request_code = :request_code
         LIMIT 1'
    );
    $requestStatement->execute([':request_code' => $requestCode]);
    $savedRequest = $requestStatement->fetch();

    $emailSent = false;
    if ($savedRequest) {
        try {
            $emailSent = clevis_send_receipt_email($savedRequest, []);
        } catch (Throwable $mailException) {
            $emailSent = false;
        }
    }

    unset($_SESSION['old']);
    $_SESSION['last_request_code'] = $requestCode;
    $_SESSION['receipt_notice'] = $emailSent
        ? 'Your request has been received successfully and the receipt PDF has been emailed to you.'
        : 'Your request has been received successfully. Your receipt is ready below.';
} catch (Throwable $exception) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'The request could not be saved. Confirm that the database has been created and connected correctly.',
    ];

    header('Location: index.php#request');
    exit;
}

header('Location: receipt.php?request_code=' . urlencode($requestCode));
exit;
