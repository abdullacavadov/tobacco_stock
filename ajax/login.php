<?php

require '../inc/db.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

try {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        throw new Exception('İstifadəçi adı daxil edilməyib');
    }

    if ($password === '') {
        throw new Exception('Şifrə daxil edilməyib');
    }

    $stmt = $pdo->prepare("
        SELECT
            id,
            fullname,
            username,
            password,
            role,
            active
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->execute([
        $username
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('İstifadəçi adı və ya şifrə yanlışdır');
    }

    if (!$user['active']) {
        throw new Exception('Bu istifadəçi deaktiv edilib');
    }

    if (!password_verify($password, $user['password'])) {
        throw new Exception('İstifadəçi adı və ya şifrə yanlışdır');
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    $stmt = $pdo->prepare("
        UPDATE users
        SET last_login = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $user['id']
    ]);

    echo json_encode([
        'success' => true
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}