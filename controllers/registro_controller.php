<?php
session_start();
include '../config/db.php';

/* 1. Validación del método de solicitud y saneamiento de las entradas del usuario */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../registro.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

/* 2. Validación de datos: campos obligatorios, longitud de contraseña y formato de email */
if (empty($username) || empty($email) || empty($password)) {
    header("Location: ../registro.php?error=Todos los campos son obligatorios");
    exit;
}
if (strlen($password) < 6) {
    header("Location: ../registro.php?error=La contraseña debe tener al menos 6 caracteres");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../registro.php?error=Correo electrónico inválido");
    exit;
}

try {
    /* 3. Verificación de duplicados, hashing de contraseña e inserción en la BD */
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        header("Location: ../registro.php?error=El usuario o correo ya está registrado");
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hash, $email]);

    header("Location: ../login.php?success=Cuenta creada. Ya puedes iniciar sesión");
    exit;

} catch (PDOException $e) {
    /* 4. Manejo de errores y registro en el archivo de log del sistema (Concepto SO) */
    error_log("[ecommerce] Error en registro: " . $e->getMessage());
    header("Location: ../registro.php?error=Error del servidor, intenta de nuevo");
    exit;
}