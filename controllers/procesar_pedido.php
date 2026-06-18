<?php
session_start();
include '../config/db.php';
header('Content-Type: application/json');

/* 1. Validación de sesión y recuperación del ID del usuario */
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión expirada']); exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$origen     = $_GET['origen'] ?? 'individual';

try {
    /* 2. Procesamiento de pedido desde el carrito de compras (múltiples productos) */
    if ($origen === 'carrito') {
        $stmt = $pdo->prepare("
            SELECT c.cantidad, p.id AS producto_id, p.nombre, p.precio, p.stock
            FROM carrito c
            JOIN productos p ON p.id = c.producto_id
            WHERE c.usuario_id = ?
        ");
        $stmt->execute([$usuario_id]);
        $items = $stmt->fetchAll();

        if (empty($items)) {
            echo json_encode(['ok' => false, 'error' => 'El carrito está vacío']); exit;
        }

        $pdo->beginTransaction();
        $total = 0;

        foreach ($items as $item) {
            /* 
               Mecanismo de sincronización (MutEx): SELECT FOR UPDATE bloquea la fila del producto 
               hasta que se complete la transacción, evitando condiciones de carrera 
               y asegurando la consistencia del stock en entornos concurrentes.
            */
            $stmt = $pdo->prepare("
                SELECT id, nombre, precio, stock
                FROM productos
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$item['producto_id']]);
            $producto = $stmt->fetch();

            if (!$producto || $producto['stock'] < $item['cantidad']) {
                $pdo->rollBack();
                echo json_encode([
                    'ok'    => false,
                    'error' => "Stock insuficiente para: {$item['nombre']}"
                ]); exit;
            }

            /* Actualización del stock y cálculo del total del pedido */
            $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")
                ->execute([$item['cantidad'], $item['producto_id']]);

            $total += $producto['precio'] * $item['cantidad'];
        }

        /* Creación del pedido y registro de sus detalles en la base de datos */
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, total, fecha) VALUES (?, ?, NOW())");
        $stmt->execute([$usuario_id, $total]);
        $pedido_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $stmt->execute([$pedido_id, $item['producto_id'], $item['cantidad'], $item['precio']]);
        }

        /* Vaciado del carrito del usuario después de confirmar el pedido */
        $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?")->execute([$usuario_id]);
        $pdo->commit();

        error_log("[TiendaOS] Pedido carrito #{$pedido_id} usuario:{$usuario_id} total:S/{$total}");

        echo json_encode([
            'ok'       => true,
            'mensaje'  => "Pedido #{$pedido_id} confirmado — S/ " . number_format($total, 2),
            'pedido_id'=> $pedido_id
        ]);

    } else {
        /* 3. Procesamiento de compra individual (desde el catálogo para un solo producto) */
        $producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;

        if ($producto_id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Producto inválido']); exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ? FOR UPDATE");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if (!$producto || $producto['stock'] <= 0) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => 'Producto no disponible']); exit;
        }

        $pdo->prepare("UPDATE productos SET stock = stock - 1 WHERE id = ?")->execute([$producto_id]);

        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, total, fecha) VALUES (?, ?, NOW())");
        $stmt->execute([$usuario_id, $producto['precio']]);
        $pedido_id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, 1, ?)")
            ->execute([$pedido_id, $producto_id, $producto['precio']]);

        $pdo->commit();

        error_log("[TiendaOS] Pedido individual #{$pedido_id} usuario:{$usuario_id} producto:{$producto['nombre']}");

        echo json_encode([
            'ok'      => true,
            'mensaje' => "Pedido #{$pedido_id} realizado. Compraste: {$producto['nombre']}"
        ]);
    }

} catch (Exception $e) {
    /* 4. Manejo de errores y reversión de la transacción en caso de fallo */
    $pdo->rollBack();
    error_log("[TiendaOS] Error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}