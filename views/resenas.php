<?php
/* Vista de reseñas de productos de TiendaOS.
   Verifica la sesión del usuario, valida la existencia del producto
   y muestra el formulario para escribir reseñas, así como la lista de reseñas existentes. */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../config/db.php';

$producto_id = intval($_GET['id'] ?? 0);
if ($producto_id <= 0) {
    header("Location: catalogo.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch();
if (!$producto) {
    header("Location: catalogo.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reseñas — <?= htmlspecialchars($producto['nombre']) ?></title>
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

/* --- Contenedor principal --- */
.container { max-width:800px; margin:0 auto; padding:40px 24px; }

/* --- Encabezado del producto --- */
.producto-header {
    background:#ffffff; 
    border:1px solid #d1d5db; 
    border-radius:14px; 
    padding:28px; 
    margin-bottom:32px; 
    display:flex; 
    gap:20px; 
    align-items:center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.producto-icon { 
    font-size:3rem; 
    background:#f1f5f9; 
    border-radius:10px; 
    padding:16px; 
}
.producto-info h2 { 
    color:#000000; 
    font-size:1.3rem; 
    margin-bottom:6px; 
}
.producto-info .precio { 
    color:#0ea5e9; 
    font-size:1.5rem; 
    font-weight:700; 
}
.producto-info .stock { 
    color:#4b5563; 
    font-size:0.85rem; 
    margin-top:4px; 
}

/* --- Títulos de sección --- */
h3 { 
    color:#000000; 
    font-size:1.1rem; 
    margin-bottom:16px; 
}

/* --- Formulario de reseña --- */
.form-card { 
    background:#ffffff; 
    border:1px solid #d1d5db; 
    border-radius:14px; 
    padding:24px; 
    margin-bottom:28px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.form-card h3 { margin-bottom:20px; }

.estrellas { 
    display:flex; 
    gap:8px; 
    margin-bottom:16px; 
}
.estrella { 
    font-size:1.8rem; 
    cursor:pointer; 
    opacity:0.3; 
    transition:opacity 0.15s; 
    color:#fbbf24; 
}
.estrella.activa { opacity:1; }

textarea { 
    width:100%; 
    padding:12px 14px; 
    background:#ffffff; 
    border:1px solid #d1d5db; 
    border-radius:8px; 
    color:#000000; 
    font-size:0.9rem; 
    resize:vertical; 
    min-height:90px; 
    font-family:inherit; 
    transition:0.2s;
}
textarea:focus { 
    outline:none; 
    border-color:#0ea5e9; 
    box-shadow: 0 0 0 3px rgba(14,165,233,0.1);
}

/* --- Botón de publicar reseña --- */
.btn-enviar { 
    margin-top:14px; 
    padding:11px 28px; 
    background:#0ea5e9; 
    border:none; 
    border-radius:8px; 
    color:white; 
    font-weight:600; 
    font-size:0.95rem; 
    cursor:pointer; 
    transition:0.3s;
}
.btn-enviar:hover { 
    background:#0284c7; 
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(14,165,233,0.3);
}

/* --- Lista de reseñas --- */
.resenas-lista { display:flex; flex-direction:column; gap:14px; }
.resena-card { 
    background:#ffffff; 
    border:1px solid #d1d5db; 
    border-radius:12px; 
    padding:20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.resena-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.resena-user { color:#0ea5e9; font-weight:600; font-size:0.9rem; }
.resena-fecha { color:#4b5563; font-size:0.78rem; }
.resena-estrellas { color:#fbbf24; font-size:1.1rem; margin-bottom:8px; }
.resena-comentario { color:#374151; font-size:0.9rem; line-height:1.6; }

.sin-resenas { 
    text-align:center; 
    padding:40px; 
    color:#4b5563; 
    font-size:0.95rem; 
}

/* --- Alertas flotantes --- */
.alerta { 
    display:none; 
    padding:12px 16px; 
    border-radius:8px; 
    margin-bottom:16px; 
    font-size:0.875rem; 
}
.alerta.ok { 
    background:#d1fae5; 
    color:#065f46; 
    border:1px solid #a7f3d0; 
}
.alerta.error { 
    background:#fee2e2; 
    color:#b91c1c; 
    border:1px solid #fecaca; 
}

/* --- Badge del servicio Docker --- */
.docker-badge { 
    display:inline-flex; 
    align-items:center; 
    gap:6px; 
    background:#f1f5f9; 
    border:1px solid #d1d5db; 
    color:#0ea5e9; 
    border-radius:20px; 
    padding:4px 12px; 
    font-size:0.75rem; 
    margin-bottom:20px; 
}
</style>
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar">
    <div class="logo">🛒 TiendaOS</div>
    <div class="nav-links">
        <a href="catalogo.php" class="btn-nav">🏪 Catálogo</a>
        <a href="historial.php" class="btn-nav">📦 Historial</a>
        <a href="../logout.php" class="btn-nav btn-logout">Cerrar sesión</a>
    </div>
</nav>

<div class="container">

    <!-- Encabezado del producto -->
    <div class="producto-header">
        <div class="producto-icon">💻</div>
        <div class="producto-info">
            <h2><?= htmlspecialchars($producto['nombre']) ?></h2>
            <div class="precio">S/ <?= number_format($producto['precio'], 2) ?></div>
            <div class="stock">Stock disponible: <?= $producto['stock'] ?> unidades</div>
        </div>
    </div>

    <!-- Badge del servicio Docker -->
    <div class="docker-badge">🐳 Servicio de reseñas</div>

    <!-- Formulario para escribir una reseña -->
    <div class="form-card" id="form-resena">
        <h3>✍️ Escribe tu reseña</h3>
        <div class="alerta" id="alerta"></div>
        <div class="estrellas" id="estrellas">
            <span class="estrella" data-val="1">★</span>
            <span class="estrella" data-val="2">★</span>
            <span class="estrella" data-val="3">★</span>
            <span class="estrella" data-val="4">★</span>
            <span class="estrella" data-val="5">★</span>
        </div>
        <textarea id="comentario" placeholder="Cuéntanos tu experiencia con este producto..."></textarea>
        <button class="btn-enviar" onclick="enviarResena()">Publicar reseña</button>
    </div>

    <!-- Lista de reseñas del producto -->
    <h3>💬 Reseñas del producto</h3>
    <div class="resenas-lista" id="lista-resenas">
        <div class="sin-resenas">Cargando reseñas...</div>
    </div>

</div>

<script>
/* Lógica de interacción para la selección de calificación,
   validación de compra y gestión de reseñas vía el proxy. */
var calificacion = 0;
var productoId   = <?= $producto_id ?>;
var usuarioId    = <?= $usuario_id ?>;
var puedeResenar = false;

/* Verificación inicial: ¿El usuario compró el producto y ya escribió una reseña? */
fetch('../controllers/resenas_proxy.php?accion=puede_resenar&producto_id=' + productoId)
.then(function(r) { return r.json(); })
.then(function(data) {
    puedeResenar = data.puede;
    var formCard = document.getElementById('form-resena');
    if (!data.compro) {
        formCard.innerHTML = '<div style="color:#64748b;padding:20px;text-align:center">⚠️ Solo puedes reseñar productos que hayas comprado.</div>';
    } else if (data.ya_reseno) {
    formCard.innerHTML = '<div style="color:#065f46;padding:20px;text-align:center">✅ Ya escribiste una reseña para este producto.</div>';
}
});

/* Gestión del sistema de estrellas para la calificación */
document.querySelectorAll('.estrella').forEach(function(e) {
    e.addEventListener('click', function() {
        calificacion = parseInt(this.getAttribute('data-val'));
        document.querySelectorAll('.estrella').forEach(function(s) {
            s.classList.toggle('activa', parseInt(s.getAttribute('data-val')) <= calificacion);
        });
    });
});

/* Función para cargar la lista de reseñas desde el servicio Docker */
function cargarResenas() {
    fetch('../controllers/resenas_proxy.php?accion=listar&producto_id=' + productoId)
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var lista = document.getElementById('lista-resenas');
        if (!Array.isArray(data) || !data.length) {
            lista.innerHTML = '<div class="sin-resenas">Sé el primero en reseñar este producto 🌟</div>';
            return;
        }
        lista.innerHTML = data.map(function(r) {
            var estrellas = '★'.repeat(r.calificacion) + '☆'.repeat(5 - r.calificacion);
            var fecha = new Date(r.fecha_creacion).toLocaleDateString('es-PE');
            return '<div class="resena-card">' +
                '<div class="resena-header">' +
                    '<span class="resena-user">👤 ' + r.username + '</span>' +
                    '<span class="resena-fecha">' + fecha + '</span>' +
                '</div>' +
                '<div class="resena-estrellas">' + estrellas + '</div>' +
                '<div class="resena-comentario">' + (r.comentario || '') + '</div>' +
            '</div>';
        }).join('');
    });
}

/* Función para enviar una nueva reseña al servicio Docker */
function enviarResena() {
    if (!puedeResenar) return;
    var comentario = document.getElementById('comentario').value.trim();
    var alerta = document.getElementById('alerta');
    if (calificacion === 0) {
        alerta.className = 'alerta error';
        alerta.textContent = 'Selecciona una calificación';
        alerta.style.display = 'block';
        return;
    }
    fetch('../controllers/resenas_proxy.php?accion=crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ producto_id: productoId, calificacion: calificacion, comentario: comentario })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var alerta = document.getElementById('alerta');
        if (data.ok) {
            alerta.className = 'alerta ok';
            alerta.textContent = '✅ Reseña publicada';
            alerta.style.display = 'block';
            document.getElementById('comentario').value = '';
            calificacion = 0;
            document.querySelectorAll('.estrella').forEach(function(s) { s.classList.remove('activa'); });
            cargarResenas();
        } else {
            alerta.className = 'alerta error';
            alerta.textContent = '❌ ' + (data.error || 'Error desconocido');
            alerta.style.display = 'block';
        }
    });
}

/* Carga inicial de reseñas */
cargarResenas();
</script>
</body>
</html>