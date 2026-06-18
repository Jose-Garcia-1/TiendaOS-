<?php
session_start();
include '../config/db.php';

/* 1. Captura y validación básica de datos del formulario de login */
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header("Location: ../login.php?error=Usuario o contraseña incorrectos");
    exit;
}

/* 2. Consulta a la base de datos y verificación de credenciales */
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

/* 3. Si el usuario existe y la contraseña hasheada coincide, se inicia la sesión */
if ($user && password_verify($password, $user['password'])) {
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['rol'] = $user['rol'];
    header("Location: ../views/catalogo.php");
    exit;
} else {
    /* 4. Redirección al login con mensaje de error si las credenciales fallan */
    header("Location: ../login.php?error=Usuario o contraseña incorrectos");
    exit;
}
?>