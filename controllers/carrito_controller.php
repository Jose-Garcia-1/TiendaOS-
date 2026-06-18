<?php
session_start();
include '../config/db.php';
header('Content-Type: application/json');

/* 1. Validación de sesión y recuperación del ID del usuario */
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Sin sesión']); exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$accion     = $_GET['accion'] ?? '';

/* 2. Acciones para la gestión del carrito de compras (CRUD y contador) */
if ($accion === 'agregar') {
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $cantidad    = intval($_POST['cantidad']    ?? 1);

    /* Validación de stock antes de agregar el producto al carrito. */
    $stmt = $pdo->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();

    if (!$producto || $producto['stock'] <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Sin stock disponible']); exit;
    }
    if ($cantidad > $producto['stock']) {
        echo json_encode(['ok' => false, 'error' => "Solo hay {$producto['stock']} disponibles"]); exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO carrito (usuario_id, producto_id, cantidad)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE cantidad = cantidad + ?
    ");
    $stmt->execute([$usuario_id, $producto_id, $cantidad, $cantidad]);

    $stmt = $pdo->prepare("SELECT SUM(cantidad) FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $total = $stmt->fetchColumn();

    echo json_encode(['ok' => true, 'mensaje' => "'{$producto['nombre']}' agregado al carrito", 'total_carrito' => $total]);

} elseif ($accion === 'obtener') {
    /* Obtiene todos los productos del carrito con su subtotal calculado. */
    $stmt = $pdo->prepare("
        SELECT c.id, c.cantidad, p.id AS producto_id,
               p.nombre, p.precio, p.stock,
               (p.precio * c.cantidad) AS subtotal
        FROM carrito c
        JOIN productos p ON p.id = c.producto_id
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$usuario_id]);
    $items = $stmt->fetchAll();
    $total = array_sum(array_column($items, 'subtotal'));
    echo json_encode(['ok' => true, 'items' => $items, 'total' => $total]);

} elseif ($accion === 'actualizar') {
    /* Actualiza la cantidad de un producto en el carrito o lo elimina si la cantidad es 0. */
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $cantidad    = intval($_POST['cantidad']    ?? 1);

    if ($cantidad <= 0) {
        $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?")
            ->execute([$usuario_id, $producto_id]);
        echo json_encode(['ok' => true, 'mensaje' => 'Producto eliminado']);
    } else {
        $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $stock = $stmt->fetchColumn();
        if ($cantidad > $stock) {
            echo json_encode(['ok' => false, 'error' => "Solo hay {$stock} disponibles"]); exit;
        }
        $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?")
            ->execute([$cantidad, $usuario_id, $producto_id]);
        echo json_encode(['ok' => true]);
    }

} elseif ($accion === 'eliminar') {
    /* Elimina un producto específico del carrito del usuario. */
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?")
        ->execute([$usuario_id, $producto_id]);
    echo json_encode(['ok' => true]);

} elseif ($accion === 'contar') {
    /* Obtiene el número total de productos en el carrito del usuario. */
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    echo json_encode(['total' => intval($stmt->fetchColumn())]);

} else {
    echo json_encode(['error' => 'Acción no válida']);
}