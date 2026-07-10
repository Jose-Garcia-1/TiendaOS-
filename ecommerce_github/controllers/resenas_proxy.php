<?php
session_start();
include __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

/* 1. Validación de sesión y entorno */
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Sin sesión']); exit;
}

$method     = $_SERVER['REQUEST_METHOD'];
$accion     = $_GET['accion'] ?? '';
$usuario_id = intval($_SESSION['usuario_id']);

/* 2. Función auxiliar para verificar si el usuario compró el producto */
function usuarioComproProducto($pdo, $usuario_id, $producto_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM pedidos p
        JOIN detalle_pedido dp ON dp.pedido_id = p.id
        WHERE p.usuario_id = ? AND dp.producto_id = ?
    ");
    $stmt->execute([$usuario_id, $producto_id]);
    return $stmt->fetchColumn() > 0;
}

/* 3. Acciones de la API de reseñas: Verificar, Listar y Crear */
if ($accion === 'puede_resenar') {
    $producto_id = intval($_GET['producto_id'] ?? 0);
    $compro = usuarioComproProducto($pdo, $usuario_id, $producto_id);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM resenas WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$usuario_id, $producto_id]);
    $ya_reseno = $stmt->fetchColumn() > 0;
    echo json_encode([
        'puede'     => $compro && !$ya_reseno,
        'compro'    => $compro,
        'ya_reseno' => $ya_reseno
    ]);

} elseif ($accion === 'listar') {
    /* 4. Comunicación con el contenedor Docker de reseñas en Linux para obtener los datos. */
    $producto_id = intval($_GET['producto_id'] ?? 0);
    // CORREGIDO: Se reemplazó la IP antigua (172.31.45.212) por la IP privada actual (172.31.42.93)
    $json = @file_get_contents("http://172.31.42.93:3000/resenas/{$producto_id}");
    echo $json ?: '[]';

} elseif ($accion === 'crear' && $method === 'POST') {
    $body        = json_decode(file_get_contents('php://input'), true);
    $producto_id = intval($body['producto_id'] ?? 0);
    $calificacion= intval($body['calificacion'] ?? 0);
    $comentario  = trim($body['comentario'] ?? '');

    if (!usuarioComproProducto($pdo, $usuario_id, $producto_id)) {
        echo json_encode(['error' => 'Solo puedes reseñar productos que hayas comprado']); exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM resenas WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$usuario_id, $producto_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['error' => 'Ya escribiste una reseña para este producto']); exit;
    }

    /* 5. Envío de la nueva reseña al servicio de reseñas en Linux a través de cURL. */
    // CORREGIDO: Se reemplazó la IP antigua (172.31.45.212) por la IP privada actual (172.31.42.93)
    $ch = curl_init("http://172.31.42.93:3000/resenas");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'producto_id'  => $producto_id,
        'usuario_id'   => $usuario_id,
        'calificacion' => $calificacion,
        'comentario'   => $comentario
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $resp = curl_exec($ch);
    curl_close($ch);
    echo $resp ?: json_encode(['error' => 'Error al conectar con Docker']);

} else {
    echo json_encode(['error' => 'Acción no válida']);
}