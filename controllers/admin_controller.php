<?php
session_start();
include '../config/db.php';
header('Content-Type: application/json');

/* 1. Validación de sesión y permisos de administrador */
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Sin sesión']); exit;
}

$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();
if (!$user || $user['rol'] !== 'admin') {
    echo json_encode(['error' => 'Sin permisos']); exit;
}

/* 2. Obtención de la acción solicitada (GET o POST) */
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

/* 3. CRUD de productos (Agregar, Editar Stock, Editar Producto, Eliminar) */
if ($accion === 'agregar') {
    $nombre      = trim($_POST['nombre']      ?? '');
    $precio      = floatval($_POST['precio']  ?? 0);
    $stock       = intval($_POST['stock']     ?? 0);
    $categoria   = trim($_POST['categoria']   ?? 'otros');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $imagen      = trim($_POST['imagen']      ?? '');

    if (!$nombre || $precio <= 0 || $stock < 0) {
        echo json_encode(['ok' => false, 'error' => 'Datos inválidos']); exit;
    }

    $pdo->prepare("
        INSERT INTO productos (nombre, precio, stock, categoria, descripcion, imagen_url)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$nombre, $precio, $stock, $categoria,
                 $descripcion ?: null, $imagen ?: null]);

    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);

} elseif ($accion === 'editar_stock') {
    $id    = intval($_POST['id']    ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    if ($stock < 0) $stock = 0;
    $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?")->execute([$stock, $id]);
    echo json_encode(['ok' => true]);

} elseif ($accion === 'editar_producto') {
    $id          = intval($_POST['id']        ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $imagen      = trim($_POST['imagen']      ?? '');
    $precio      = floatval($_POST['precio']  ?? 0);

    $pdo->prepare("
        UPDATE productos SET descripcion = ?, imagen_url = ?, precio = ? WHERE id = ?
    ")->execute([$descripcion ?: null, $imagen ?: null, $precio, $id]);

    echo json_encode(['ok' => true]);

} elseif ($accion === 'eliminar') {
    $id = intval($_POST['id'] ?? 0);
    /* Validación de integridad referencial: no permitir eliminar productos con pedidos asociados. */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM detalle_pedido WHERE producto_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['ok' => false, 'error' => 'No se puede eliminar — tiene pedidos asociados']); exit;
    }
    $pdo->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);

/* 4. Gestión de roles de usuarios */
} elseif ($accion === 'cambiar_rol') {
    $id  = intval($_POST['id']  ?? 0);
    $rol = $_POST['rol'] ?? 'cliente';
    if (!in_array($rol, ['admin', 'cliente'])) {
        echo json_encode(['ok' => false, 'error' => 'Rol inválido']); exit;
    }
    if ($id === intval($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'No puedes cambiar tu propio rol']); exit;
    }
    $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?")->execute([$rol, $id]);
    echo json_encode(['ok' => true]);

/* 5. Monitoreo del Sistema Operativo (comunicación cruzada con servidor Windows) */
} elseif ($accion === 'recursos') {
    $url = "http://18.217.0.159/recursos_windows.php";
    $contenido = file_get_contents($url);
    echo $contenido;
    exit;

} else {
    echo json_encode(['error' => 'Acción no válida']);
}