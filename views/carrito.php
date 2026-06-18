<?php
/* Vista del carrito de compras de TiendaOS.
   Verifica la sesión del usuario, obtiene los productos del carrito desde la base de datos
   y muestra el resumen para confirmar el pedido. */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php"); exit;
}
include '../config/db.php';

$usuario_id = $_SESSION['usuario_id'];
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Carrito — TiendaOS</title>
<style>
/* --- Configuración general y reset de estilos --- */
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
.container { max-width:900px; margin:0 auto; padding:40px 24px; }
h1 { font-size:1.8rem; color:#000000; margin-bottom:8px; }
.subtitle { color:#374151; margin-bottom:32px; }

/* --- Estado vacío del carrito --- */
.empty { 
    text-align:center; padding:80px 20px; 
    background:#ffffff; border:1px solid #d1d5db; border-radius:16px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.empty .icon { font-size:4rem; margin-bottom:16px; }
.empty p { color:#4b5563; margin-bottom:20px; }
.empty a {
    display: inline-block;
    padding: 12px 28px;
    background: #0ea5e9;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
    border: none;
}
.empty a:hover {
    background: #0284c7;
    transform: translateY(-2px);
}

/* --- Estructura de diseño de dos columnas --- */
.layout { display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start; }

/* --- Lista de productos en el carrito --- */
.items { display:flex; flex-direction:column; gap:12px; }
.item-card {
    background:#ffffff; border:1px solid #d1d5db; border-radius:12px;
    padding:20px; display:flex; align-items:center; gap:16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: 0.2s;
}
.item-card:hover { border-color:#0ea5e9; }

.item-icon { font-size:2rem; background:#f1f5f9; border-radius:8px; padding:12px; min-width:56px; text-align:center; }
.item-info { flex:1; }
.item-info h3 { color:#000000; font-size:0.95rem; margin-bottom:4px; }
.item-info .precio-unit { color:#4b5563; font-size:0.82rem; }

/* --- Controles de cantidad y acciones --- */
.item-controls { display:flex; align-items:center; gap:8px; }
.btn-qty {
    width:30px; height:30px; background:#f1f5f9; border:1px solid #d1d5db;
    border-radius:6px; color:#000000; font-size:1rem; cursor:pointer;
    display:flex; align-items:center; justify-content:center; transition:0.2s;
}
.btn-qty:hover { border-color:#0ea5e9; color:#0ea5e9; background:#f0f9ff; }
.qty-num { min-width:24px; text-align:center; font-weight:600; color:#000000; }
.btn-eliminar { 
    background:none; border:none; color:#94a3b8; cursor:pointer; font-size:1.1rem; margin-left:8px; transition:0.2s;
}
.btn-eliminar:hover { color:#ef4444; }
.item-subtotal { color:#0ea5e9; font-weight:700; font-size:1rem; min-width:80px; text-align:right; }

/* --- Resumen del pedido --- */
.resumen {
    background:#ffffff; border:1px solid #d1d5db; border-radius:14px;
    padding:28px; position:sticky; top:24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.resumen h2 { color:#000000; font-size:1.1rem; margin-bottom:20px; }
.resumen-fila { 
    display:flex; justify-content:space-between; margin-bottom:12px; 
    color:#4b5563; font-size:0.9rem; 
}
.resumen-fila.total { 
    color:#000000; font-weight:700; font-size:1.1rem; 
    border-top:1px solid #e5e7eb; padding-top:14px; margin-top:8px; 
}
.resumen-fila.total span:last-child { color:#16a34a; }

/* --- Botones de acción --- */
.btn-checkout {
    width:100%; margin-top:20px; padding:14px;
    background:#0ea5e9; border:none; border-radius:10px;
    color:white; font-size:1rem; font-weight:700; cursor:pointer;
    transition:0.3s;
}
.btn-checkout:hover { background:#0284c7; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(14,165,233,0.3); }

.btn-vaciar {
    width:100%; margin-top:10px; padding:10px;
    background:transparent; border:1px solid #d1d5db; border-radius:10px;
    color:#4b5563; font-size:0.85rem; cursor:pointer; transition:0.2s;
}
.btn-vaciar:hover { border-color:#ef4444; color:#ef4444; background:#fef2f2; }

/* --- Alerta flotante --- */
.alerta { 
    display:none; position:fixed; top:24px; right:24px; 
    padding:14px 20px; border-radius:10px; font-weight:500; font-size:0.9rem; 
    z-index:200; max-width:320px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.alerta.ok { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.alerta.error { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
</style>
</head>
<body>

<!-- Barra de navegación con enlaces a catálogo, historial y cierre de sesión -->
<nav class="navbar">
    <div class="logo">🛒 TiendaOS</div>
    <div class="nav-links">
        <a href="catalogo.php" class="btn-nav">🏪 Catálogo</a>
        <a href="historial.php" class="btn-nav">📦 Historial</a>
        <a href="../logout.php" class="btn-nav btn-logout">Cerrar sesión</a>
    </div>
</nav>

<div class="container">
    <h1>🛒 Mi Carrito</h1>
    <p class="subtitle">Revisa tus productos antes de confirmar</p>

    <!-- Mensaje y botón cuando el carrito está vacío -->
    <?php if (empty($items)): ?>
    <div class="empty">
        <div class="icon">🛒</div>
        <p>Tu carrito está vacío</p>
        <a href="catalogo.php">Ver productos</a>
    </div>
    <?php else: ?>

    <div class="layout">
        <!-- Lista de productos agregados al carrito -->
        <div class="items" id="lista-items">
        <?php
        $iconos = ['💻','🖱️','⌨️','🎧','🖥️','📱','🖨️','💾','📷','🖲️'];
        $i = 0;
        foreach ($items as $item): ?>
        <div class="item-card" id="item-<?= $item['producto_id'] ?>">
            <div class="item-icon"><?= $iconos[$i % count($iconos)] ?></div>
            <div class="item-info">
                <h3><?= htmlspecialchars($item['nombre']) ?></h3>
                <div class="precio-unit">S/ <?= number_format($item['precio'], 2) ?> c/u</div>
            </div>
            <div class="item-controls">
                <button class="btn-qty" onclick="cambiarCantidad(<?= $item['producto_id'] ?>, -1)">−</button>
                <span class="qty-num" id="qty-<?= $item['producto_id'] ?>"><?= $item['cantidad'] ?></span>
                <button class="btn-qty" onclick="cambiarCantidad(<?= $item['producto_id'] ?>, 1)">+</button>
            </div>
            <div class="item-subtotal" id="sub-<?= $item['producto_id'] ?>">
                S/ <?= number_format($item['subtotal'], 2) ?>
            </div>
            <button class="btn-eliminar" onclick="eliminarItem(<?= $item['producto_id'] ?>)" title="Eliminar">🗑️</button>
        </div>
        <?php $i++; endforeach; ?>
        </div>

        <!-- Resumen del pedido y botones de confirmación -->
        <div class="resumen">
            <h2>Resumen del pedido</h2>
            <?php foreach ($items as $item): ?>
            <div class="resumen-fila">
                <span><?= htmlspecialchars($item['nombre']) ?> x<?= $item['cantidad'] ?></span>
                <span>S/ <?= number_format($item['subtotal'], 2) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="resumen-fila total">
                <span>Total</span>
                <span id="total-final">S/ <?= number_format($total, 2) ?></span>
            </div>
            <button class="btn-checkout" onclick="procesarPedido()">✅ Confirmar pedido</button>
            <button class="btn-vaciar" onclick="vaciarCarrito()">🗑️ Vaciar carrito</button>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Alerta flotante para mensajes al usuario -->
<div class="alerta" id="alerta"></div>

<script>
/* Funciones para la interacción con el carrito: mostrar alertas, modificar cantidades, eliminar productos, vaciar y procesar pedido. */
function mostrarAlerta(msg, tipo) {
    var a = document.getElementById('alerta');
    a.className = 'alerta ' + tipo;
    a.textContent = msg;
    a.style.display = 'block';
    setTimeout(function() { a.style.display = 'none'; }, 3000);
}

function cambiarCantidad(producto_id, delta) {
    var qtyEl = document.getElementById('qty-' + producto_id);
    var nueva = parseInt(qtyEl.textContent) + delta;
    if (nueva < 0) return;

    var fd = new FormData();
    fd.append('producto_id', producto_id);
    fd.append('cantidad', nueva);

    fetch('../controllers/carrito_controller.php?accion=actualizar', { method:'POST', body:fd })
    .then(r => r.json())
    .then(function(data) {
        if (data.ok) {
            if (nueva === 0) {
                document.getElementById('item-' + producto_id).remove();
            } else {
                qtyEl.textContent = nueva;
            }
            location.reload();
        } else {
            mostrarAlerta('❌ ' + data.error, 'error');
        }
    });
}

function eliminarItem(producto_id) {
    var fd = new FormData();
    fd.append('producto_id', producto_id);
    fetch('../controllers/carrito_controller.php?accion=eliminar', { method:'POST', body:fd })
    .then(r => r.json())
    .then(function() { location.reload(); });
}

function vaciarCarrito() {
    if (!confirm('¿Vaciar todo el carrito?')) return;
    document.querySelectorAll('.item-card').forEach(function(card) {
        var id = card.id.replace('item-', '');
        var fd = new FormData();
        fd.append('producto_id', id);
        fetch('../controllers/carrito_controller.php?accion=eliminar', { method:'POST', body:fd });
    });
    setTimeout(function() { location.reload(); }, 500);
}

function procesarPedido() {
    fetch('../controllers/procesar_pedido.php?origen=carrito', { method:'POST' })
    .then(r => r.json())
    .then(function(data) {
        if (data.ok) {
            mostrarAlerta('✅ ' + data.mensaje, 'ok');
            setTimeout(function() { window.location.href = 'historial.php'; }, 2000);
        } else {
            mostrarAlerta('❌ ' + data.error, 'error');
        }
    });
}
</script>
</body>
</html>