<?php
/* Vista del historial de compras del usuario.
   Verifica la sesión, obtiene los pedidos del usuario desde la base de datos
   y muestra las estadísticas y el listado de pedidos realizados. */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../config/db.php';

$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("
    SELECT p.id, p.fecha, p.total,
           GROUP_CONCAT(pr.nombre SEPARATOR '||') AS productos,
           GROUP_CONCAT(dp.producto_id SEPARATOR '||') AS producto_ids,
           SUM(dp.cantidad) AS total_items
    FROM pedidos p
    JOIN detalle_pedido dp ON dp.pedido_id = p.id
    JOIN productos pr ON pr.id = dp.producto_id
    WHERE p.usuario_id = ?
    GROUP BY p.id, p.fecha, p.total
    ORDER BY p.fecha DESC
");
$stmt->execute([$usuario_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial — TiendaOS</title>
<style>
/* --- Reset y configuración base --- */
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    font-family:'Segoe UI',sans-serif; 
    background:#f1f5f9; 
    color:#000000; 
    min-height:100vh; 
}

/* --- Estilos de la barra de navegación --- */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background: #787c83;
    border-bottom: 1px solid #6b6f76;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.navbar .logo { 
    color: #ffffff; 
    font-size: 1.4rem; 
    font-weight: 700; 
}
.nav-links {
    display: flex;
    align-items: center;
    gap: 12px;
}
.btn-nav {
    padding: 8px 20px;
    border: 1px solid #ffffff;
    border-radius: 8px;
    color: #1e293b;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    background: #ffffff;
    transition: all 0.3s ease;
}
.btn-nav:hover {
    background: #f1f5f9;
    border-color: #38bdf8;
    color: #0ea5e9;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.btn-logout {
    border-color: #fca5a5;
    color: #dc2626;
}
.btn-logout:hover {
    border-color: #ef4444 !important;
    color: #ffffff !important;
    background: #ef4444 !important;
}

/* --- Contenedor principal y encabezados --- */
.container { max-width:1000px; margin:0 auto; padding:48px 24px; }

.page-header { margin-bottom:40px; }
.page-header h1 { font-size:2rem; color:#000000; margin-bottom:8px; }
.page-header p { color:#374151; font-size:1rem; }
.page-header p strong { color:#0ea5e9; }

/* --- Estadísticas del historial --- */
.stats-bar {
    display:flex; gap:16px; margin-bottom:36px;
}
.stat {
    background:#ffffff; 
    border: 1px solid #d1d5db;
    border-radius:12px;
    padding:20px 28px; flex:1; text-align:center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.stat .num { font-size:1.8rem; font-weight:700; color:#0ea5e9; }
.stat .label { color:#374151; font-size:0.8rem; margin-top:4px; text-transform:uppercase; letter-spacing:0.05em; }

/* --- Estado vacío --- */
.empty {
    text-align:center; padding:80px 20px;
    background:#ffffff; border:1px solid #d1d5db; border-radius:16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.empty .icon { font-size:4rem; margin-bottom:16px; }
.empty p { color:#4b5563; font-size:1rem; }
.empty a {
    display:inline-block; margin-top:20px; padding:12px 28px;
    background:#0ea5e9; color:white; border-radius:8px;
    text-decoration:none; font-weight:600; transition:0.3s;
}
.empty a:hover { background:#0284c7; transform: translateY(-2px); }

/* --- Lista de pedidos --- */
.pedidos { display:flex; flex-direction:column; gap:16px; }

.pedido-card {
    background:#ffffff; 
    border:1px solid #d1d5db;
    border-radius:14px; padding:24px 28px;
    display:flex; align-items:center; gap:24px;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.pedido-card:hover { 
    border-color:#0ea5e9; 
    transform:translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.pedido-num {
    font-size:1.5rem; font-weight:800; color:#0ea5e9;
    min-width:60px; text-align:center;
    background:#f1f5f9; border-radius:10px; padding:12px 8px;
}

.pedido-info { flex:1; }
.pedido-nombre {
    font-size:1rem; color:#000000; font-weight:600;
    margin-bottom:6px; line-height:1.5;
}
.pedido-fecha { color:#4b5563; font-size:0.82rem; }

.pedido-meta { display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
.pedido-total { font-size:1.4rem; font-weight:700; color:#16a34a; }
.pedido-items {
    background:#f1f5f9; color:#374151;
    border:1px solid #e5e7eb; border-radius:20px;
    padding:4px 14px; font-size:0.78rem;
}
.pedido-estado {
    background:#d1fae5; color:#065f46;
    border:1px solid #a7f3d0; border-radius:20px;
    padding:3px 12px; font-size:0.75rem; font-weight:500;
}

/* --- Botón de reseñas --- */
.btn-resena {
    display:flex; align-items:center; justify-content:center; gap:6px;
    padding:6px 14px; margin-top:6px;
    background:#ffffff;
    color:#0ea5e9; border:1px solid #d1d5db;
    border-radius:20px; text-decoration:none;
    font-size:0.78rem; font-weight:600; 
    transition:all 0.2s;
}
.btn-resena:hover {
    background:#f0f9ff; border-color:#0ea5e9; color:#0284c7;
    transform: translateY(-1px);
}
</style>
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar">
    <div class="logo">🛒 TiendaOS</div>
    <div class="nav-links">
        <a href="catalogo.php" class="btn-nav">🏪 Catálogo</a>
        <a href="../logout.php" class="btn-nav btn-logout">Cerrar sesión</a>
    </div>
</nav>

<div class="container">

    <!-- Encabezado de la página -->
    <div class="page-header">
        <h1>📦 Historial de compras</h1>
        <p>Pedidos realizados por <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </div>

    <?php if (empty($pedidos)): ?>
        <!-- Mensaje cuando el usuario no tiene pedidos -->
        <div class="empty">
            <div class="icon">🛒</div>
            <p>Aún no has realizado ninguna compra.</p>
            <a href="catalogo.php">Ir al catálogo</a>
        </div>
    <?php else:
        $total_gastado = array_sum(array_column($pedidos, 'total'));
        $total_items   = array_sum(array_column($pedidos, 'total_items'));
    ?>

    <!-- Estadísticas del historial -->
    <div class="stats-bar">
        <div class="stat">
            <div class="num"><?= count($pedidos) ?></div>
            <div class="label">Pedidos realizados</div>
        </div>
        <div class="stat">
            <div class="num"><?= $total_items ?></div>
            <div class="label">Productos comprados</div>
        </div>
        <div class="stat">
            <div class="num">S/ <?= number_format($total_gastado, 2) ?></div>
            <div class="label">Total gastado</div>
        </div>
    </div>

    <!-- Listado de pedidos -->
    <div class="pedidos">
        <?php foreach ($pedidos as $p):
    $prods    = explode('||', $p['productos']);
    $prod_ids = explode('||', $p['producto_ids']);
?>
<div class="pedido-card">
    <div class="pedido-num">#<?= $p['id'] ?></div>
    <div class="pedido-info">
        <div class="pedido-nombre">
            <?php foreach ($prods as $idx => $prod): ?>
                🔹 <?= htmlspecialchars(trim($prod)) ?><br>
            <?php endforeach; ?>
        </div>
        <div class="pedido-fecha">📅 <?= date('d/m/Y \a \l\a\s H:i', strtotime($p['fecha'])) ?></div>
    </div>
    <div class="pedido-meta">
        <div class="pedido-total">S/ <?= number_format($p['total'], 2) ?></div>
        <div class="pedido-items"><?= $p['total_items'] ?> item(s)</div>
        <div class="pedido-estado">✅ Completado</div>
        <?php foreach ($prod_ids as $idx => $pid): ?>
<a href="resenas.php?id=<?= intval($pid) ?>" class="btn-resena">
    ⭐ <?= htmlspecialchars(trim($prods[$idx])) ?>
</a>
<?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

</body>
</html>