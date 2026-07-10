<?php
/* Vista de registro de TiendaOS.
   Redirige a usuarios ya autenticados al catálogo y gestiona la visualización
   de mensajes de error o éxito provenientes del controlador. */
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: views/catalogo.php");
    exit;
}
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro — TiendaOS</title>
    <style>
/* --- Configuración general y reset de estilos --- */
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    font-family:'Segoe UI',sans-serif; 
    background:#f1f5f9; 
    min-height: 100vh; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
}

/* --- Estilos de la tarjeta contenedora del formulario --- */
.card {
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    padding: 40px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.logo { text-align: center; margin-bottom: 28px; }
.logo h1 { color: #0ea5e9; font-size: 1.8rem; }
.logo p  { color: #374151; font-size: 0.9rem; margin-top: 4px; }

/* --- Estilos de los campos del formulario --- */
label { display: block; color: #374151; font-size: 0.85rem; margin-bottom: 6px; margin-top: 18px; }
input {
    width: 100%; padding: 12px 14px;
    background: #ffffff; border: 1px solid #d1d5db;
    border-radius: 8px; color: #000000; font-size: 0.95rem;
    transition: 0.2s;
}
input:focus { 
    outline: none; 
    border-color: #0ea5e9; 
    box-shadow: 0 0 0 3px rgba(14,165,233,0.1); 
}

/* --- Estilos del botón principal de acción --- */
.btn {
    width: 100%; margin-top: 24px; padding: 13px;
    background: #0ea5e9; border: none; border-radius: 8px;
    color: white; font-size: 1rem; font-weight: 600; cursor: pointer;
    transition: 0.3s;
}
.btn:hover { 
    background: #0284c7; 
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(14,165,233,0.3);
}

/* --- Estilos para mensajes de error y éxito --- */
.msg-error { 
    background: #fee2e2; 
    color: #b91c1c; 
    border: 1px solid #fecaca; 
    border-radius: 8px; 
    padding: 10px 14px; 
    margin-bottom: 16px; 
    font-size: 0.875rem; 
}
.msg-success { 
    background: #d1fae5; 
    color: #065f46; 
    border: 1px solid #a7f3d0; 
    border-radius: 8px; 
    padding: 10px 14px; 
    margin-bottom: 16px; 
    font-size: 0.875rem; 
}

/* --- Estilos del enlace para redirigir al inicio de sesión --- */
.link { text-align: center; margin-top: 20px; color: #4b5563; font-size: 0.875rem; }
.link a { color: #0ea5e9; text-decoration: none; transition: 0.2s; }
.link a:hover { color: #0284c7; text-decoration: underline; }
</style>
</head>
<body>
<div class="card">
    <!-- Encabezado con el logo y subtítulo -->
    <div class="logo">
        <h1>🛒 TiendaOS</h1>
        <p>Crea tu cuenta</p>
    </div>

    <!-- Bloque de mensajes de estado (errores o éxito) -->
    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Formulario de registro que envía los datos al controlador -->
    <form action="controllers/registro_controller.php" method="POST">
        <label>Usuario</label>
        <input type="text" name="username" placeholder="tu_usuario" required>

        <label>Correo electrónico</label>
        <input type="email" name="email" placeholder="correo@email.com" required>

        <label>Contraseña</label>
        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>

        <button class="btn" type="submit">Crear cuenta</button>
    </form>

    <!-- Enlace para redirigir a la página de inicio de sesión -->
    <div class="link">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>
</div>
</body>
</html>