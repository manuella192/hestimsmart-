<?php
// 3) login-adminB.php
session_start();
require_once __DIR__ . '/db.php';

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: login-admin.html");
    exit;
}

try {
    $st = $conn->prepare("SELECT id, NOM, PRENOM, EMAIL, MDP FROM admin WHERE EMAIL = ? LIMIT 1");
    $st->execute([$email]);
    $admin = $st->fetch();

    if (!$admin || $admin['MDP'] !== $password) {
        // Simple (cohérent avec ton système actuel). Si tu veux hash, on le fera après.
        header("Location: login-admin.html");
        exit;
    }

    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_nom'] = $admin['NOM'];
    $_SESSION['admin_prenom'] = $admin['PRENOM'];
    $_SESSION['admin_email'] = $admin['EMAIL'];

    header("Location: portail_admin.php");
    exit;

} catch (Throwable $e) {
    header("Location: login-admin.html");
    exit;
}
