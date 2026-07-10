<?php
/* Vista del catálogo de productos de TiendaOS.
   Verifica la sesión del usuario y muestra el listado de productos disponibles,
   permitiendo filtrar por búsqueda o categoría. */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../config/db.php';

/* Lógica de búsqueda y filtrado de productos.
   Construye una consulta SQL dinámica basada en los parámetros recibidos por GET (q y cat). */
$busqueda  = trim($_GET['q']   ?? '');
$categoria = trim($_GET['cat'] ?? '');

$sql    = "SELECT * FROM productos WHERE stock > 0";
$params = [];

if ($busqueda) {
    $sql    .= " AND nombre LIKE ?";
    $params[] = "%{$busqueda}%";
}
if ($categoria) {
    $sql    .= " AND categoria = ?";
    $params[] = $categoria;
}
$sql .= " ORDER BY nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Obtención de categorías únicas para el filtro de categorías (pills). */
$cats = $pdo->query("SELECT DISTINCT categoria FROM productos WHERE stock > 0 ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

/* Determinación del rol del usuario para mostrar enlaces de administración si corresponde. */
$stmt_rol = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt_rol->execute([$_SESSION['usuario_id']]);
$rol_actual = $stmt_rol->fetchColumn();
$_SESSION['rol'] = $rol_actual;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Catálogo — TiendaOS</title>
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
.nav-links .user-info { color: #ffffff; font-size: 0.9rem; }
.nav-links .username { color: #38bdf8; font-weight: 600; }
.separator { color: #6b6f76; }

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
.btn-nav.admin:hover { border-color: #a78bfa; color: #a78bfa; }
.btn-nav.logout:hover { border-color: #ef4444 !important; color: #ef4444 !important; background: #ef4444 !important; }

.badge-carrito {
    background: #0ea5e9; color: white; border-radius: 20px;
    padding: 1px 7px; font-size: 0.72rem; margin-left: 4px; display: none;
}

/* --- Contenedor principal y encabezados --- */
.container { max-width:1200px; margin:0 auto; padding:40px 24px; }
h1 { font-size:1.6rem; color:#000000; margin-bottom:8px; }
.subtitle { color:#374151; font-size:0.9rem; margin-bottom:28px; }

/* --- Formulario de búsqueda y filtros --- */
.search-bar {
    display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;
}
.search-bar input {
    flex:1; min-width:200px; padding:10px 16px;
    background:#ffffff; border:1px solid #d1d5db;
    border-radius:8px; color:#000000; font-size:0.9rem;
}
.search-bar input:focus { outline:none; border-color:#0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.1); }
.search-bar select {
    padding:10px 16px; background:#ffffff; border:1px solid #d1d5db;
    border-radius:8px; color:#000000; font-size:0.9rem; cursor:pointer;
}
.search-bar select:focus { outline:none; border-color:#0ea5e9; }
.btn-buscar {
    padding:10px 20px; background:#0ea5e9; border:none;
    border-radius:8px; color:white; font-weight:600;
    cursor:pointer; font-size:0.9rem; transition:0.3s;
}
.btn-buscar:hover { background:#0284c7; transform: translateY(-2px); }
.btn-limpiar {
    padding:10px 16px; background:transparent; border:1px solid #d1d5db;
    border-radius:8px; color:#4b5563; text-decoration:none; font-size:0.9rem; transition:0.2s;
}
.btn-limpiar:hover { border-color:#ef4444; color:#ef4444; background:#fef2f2; }

/* --- Etiquetas de categorías (pills) --- */
.cats-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
.cat-pill {
    padding:6px 16px; border-radius:20px; font-size:0.8rem;
    text-decoration:none; border:1px solid #d1d5db; color:#4b5563;
    background:#ffffff; transition:all 0.2s;
}
.cat-pill:hover { border-color:#0ea5e9; color:#0ea5e9; }
.cat-pill.active { background:#0ea5e9; color:white; border-color:#0ea5e9; }

/* --- Información del resultado de la búsqueda --- */
.resultado-info { color:#4b5563; font-size:0.85rem; margin-bottom:20px; }
.resultado-info strong { color:#0ea5e9; }

/* --- Grid de productos --- */
.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:24px; }
.card {
    background:#ffffff; border:1px solid #d1d5db; border-radius:14px;
    padding:24px; display:flex; flex-direction:column; gap:12px;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.card:hover { border-color:#0ea5e9; transform:translateY(-4px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

.card-img {
    width:100%; height:160px; border-radius:10px; overflow:hidden;
    background:#f1f5f9; display:flex; align-items:center; justify-content:center;
}
.card-img img {
    width:100%; height:100%; object-fit:cover;
    transition:transform 0.3s;
}
.card:hover .card-img img { transform:scale(1.05); }

.card-icon { font-size:2.5rem; text-align:center; background:#f1f5f9; border-radius:10px; padding:16px; }
.card h3 { font-size:1rem; color:#000000; line-height:1.4; }
.card .precio { font-size:1.4rem; font-weight:700; color:#0ea5e9; }
.cat-tag {
    display:inline-block; font-size:0.7rem; padding:2px 8px;
    border-radius:20px; background:#f1f5f9; color:#374151;
    border:1px solid #e5e7eb; text-transform:uppercase; letter-spacing:0.05em;
}

/* --- Etiquetas de stock --- */
.stock-badge { display:inline-block; font-size:0.75rem; padding:3px 10px; border-radius:20px; font-weight:500; }
.stock-ok  { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.stock-low { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }

/* --- Botón de agregar al carrito --- */
.btn-comprar {
    margin-top:auto; padding:11px;
    background: #e5e7eb;
    color: #1f2937;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size:0.95rem; font-weight:600; cursor:pointer; width:100%;
    transition: all 0.2s;
}
.btn-comprar:hover {
    background: #d1d5db;
    transform: translateY(-2px);
}

/* --- Botón de ver reseñas --- */
.btn-resenas {
    display:block; text-align:center; padding:8px;
    background:transparent; border:1px solid #d1d5db;
    border-radius:8px; color:#4b5563; text-decoration:none;
    font-size:0.82rem; transition:all 0.2s;
}
.btn-resenas:hover { border-color:#0ea5e9; color:#0ea5e9; background:#f0f9ff; }

/* --- Estado cuando no hay productos --- */
.empty-state {
    text-align:center; padding:60px 20px;
    background:#ffffff; border:1px solid #d1d5db; border-radius:16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.empty-state .icon { font-size:3rem; margin-bottom:16px; }
.empty-state p { color:#4b5563; }

/* --- Modal de confirmación para agregar al carrito --- */
.overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.4); z-index:100;
    align-items:center; justify-content:center;
}
.overlay.active { display:flex; }
.modal {
    background:#ffffff; border:1px solid #d1d5db;
    border-radius:16px; padding:36px; width:100%; max-width:400px; text-align:center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.modal h2 { color:#000000; margin-bottom:10px; }
.modal p  { color:#4b5563; font-size:0.9rem; margin-bottom:24px; }
.precio-modal { color:#0ea5e9; font-size:1.5rem; font-weight:700; margin-bottom:24px; }
.modal-btns { display:flex; gap:12px; }
.btn-confirmar {
    flex:1; padding:12px; background:#0ea5e9;
    border:none; border-radius:8px; color:white; font-weight:600; cursor:pointer;
    transition:0.2s;
}
.btn-confirmar:hover { background:#0284c7; }
.btn-cancelar {
    flex:1; padding:12px; background:transparent;
    border:1px solid #d1d5db; border-radius:8px; color:#4b5563; font-weight:600; cursor:pointer;
    transition:0.2s;
}
.btn-cancelar:hover { border-color:#ef4444; color:#ef4444; background:#fef2f2; }

/* --- Alerta flotante --- */
.alerta {
    display:none; position:fixed; top:24px; right:24px;
    padding:14px 20px; border-radius:10px; font-weight:500;
    font-size:0.9rem; z-index:200; max-width:320px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.alerta.ok    { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.alerta.error { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
</style>
</head>
<body>

<!-- Barra de navegación con información del usuario y enlaces principales -->
<nav class="navbar">
    <div class="logo">🛒 TiendaOS</div>
    
    <div class="nav-links">
        <span class="user-info">Hola, <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <span class="separator">|</span>
        
        <a href="carrito.php" class="btn-nav btn-carrito">
            🛒 Carrito <span class="badge-carrito" id="badge-carrito">0</span>
        </a>
        
        <a href="historial.php" class="btn-nav">📦 Historial</a>
        
        <?php if ($rol_actual === 'admin'): ?>
            <a href="admin.php" class="btn-nav btn-admin">⚙️ Admin</a>
        <?php endif; ?>
        
        <a href="../logout.php" class="btn-nav btn-logout">Cerrar sesión</a>
    </div>
</nav>

<div class="container">
    <h1>Catálogo de Productos</h1>
    <p class="subtitle">Selecciona un producto para agregarlo al carrito</p>

    <!-- Formulario de búsqueda y filtro por categoría -->
    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="q"
                value="<?= htmlspecialchars($busqueda) ?>"
                placeholder="🔍 Buscar producto...">
            <select name="cat">
                <option value="">Todas las categorías</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?= $c ?>" <?= $categoria===$c?'selected':'' ?>>
                    <?= ucfirst($c) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-buscar">Buscar</button>
            <?php if ($busqueda || $categoria): ?>
            <a href="catalogo.php" class="btn-limpiar">✕ Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Etiquetas de navegación rápida por categoría -->
    <div class="cats-pills">
        <a href="catalogo.php" class="cat-pill <?= !$categoria?'active':'' ?>">Todos</a>
        <?php foreach ($cats as $c): ?>
        <a href="?cat=<?= urlencode($c) ?>" class="cat-pill <?= $categoria===$c?'active':'' ?>">
            <?= ucfirst($c) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Información sobre los resultados de la búsqueda/filtro -->
    <?php if ($busqueda || $categoria): ?>
    <p class="resultado-info">
        <?= count($productos) ?> producto(s)
        <?= $categoria ? "en <strong>".ucfirst($categoria)."</strong>" : '' ?>
        <?= $busqueda  ? "para <strong>\"".htmlspecialchars($busqueda)."\"</strong>" : '' ?>
    </p>
    <?php endif; ?>

    <!-- Grid de productos -->
    <?php if (empty($productos)): ?>
    <div class="empty-state">
        <div class="icon">🔍</div>
        <p>No se encontraron productos<?= $busqueda ? " para \"".htmlspecialchars($busqueda)."\"" : '' ?></p>
    </div>
    <?php else: ?>
    <div class="grid">
    <?php
    $iconos = ['💻','🖱️','⌨️','🎧','🖥️','📱','🖨️','💾','📷','🖲️'];
    $i = 0;
    foreach ($productos as $p):
        $badge = $p['stock'] <= 5
            ? "<span class='stock-badge stock-low'>⚠️ Solo {$p['stock']} disponibles</span>"
            : "<span class='stock-badge stock-ok'>✅ Stock: {$p['stock']}</span>";
    ?>
    <div class="card">
    <?php if (!empty($p['imagen_url'])): ?>
    <div class="card-img">
        <img src="<?= htmlspecialchars($p['imagen_url']) ?>"
             alt="<?= htmlspecialchars($p['nombre']) ?>"
             onerror="this.parentElement.innerHTML='<div class=\'card-icon\'><?= $iconos[$i % count($iconos)] ?></div>'">
    </div>
    <?php else: ?>
    <div class="card-icon"><?= $iconos[$i % count($iconos)] ?></div>
    <?php endif; ?>
    <span class="cat-tag"><?= htmlspecialchars($p['categoria'] ?? 'otros') ?></span>
    <h3><?= htmlspecialchars($p['nombre']) ?></h3>
    <?= $badge ?>
    <div class="precio">S/ <?= number_format($p['precio'], 2) ?></div>
    <button class="btn-comprar"
        data-id="<?= intval($p['id']) ?>"
        data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
        data-precio="<?= number_format($p['precio'], 2, '.', '') ?>">
        🛒 Agregar al carrito
    </button>
    <a href="resenas.php?id=<?= $p['id'] ?>" class="btn-resenas">⭐ Ver reseñas</a>
</div>
    <?php $i++; endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Modal de confirmación para agregar productos al carrito -->
<div class="overlay" id="overlay">
    <div class="modal">
        <h2>Agregar al carrito</h2>
        <p id="modal-nombre"></p>
        <div class="precio-modal" id="modal-precio"></div>
        <div class="modal-btns">
            <button class="btn-cancelar" id="btn-cancelar">Cancelar</button>
            <button class="btn-confirmar" id="btn-confirmar">Agregar</button>
        </div>
    </div>
</div>

<!-- Alerta flotante para mensajes al usuario -->
<div class="alerta" id="alerta"></div>

<script>
/* Lógica de interacción para el modal de agregar al carrito y actualización del badge del carrito */
var productoSeleccionado = null;

window.addEventListener('DOMContentLoaded', function() {

    document.querySelectorAll('.btn-comprar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            productoSeleccionado = this.getAttribute('data-id');
            document.getElementById('modal-nombre').textContent = this.getAttribute('data-nombre');
            document.getElementById('modal-precio').textContent = 'S/ ' + parseFloat(this.getAttribute('data-precio')).toFixed(2);
            document.getElementById('overlay').classList.add('active');
        });
    });

    document.getElementById('btn-cancelar').addEventListener('click', function() {
        document.getElementById('overlay').classList.remove('active');
        productoSeleccionado = null;
    });

    document.getElementById('btn-confirmar').addEventListener('click', function() {
        if (!productoSeleccionado) return;
        document.getElementById('overlay').classList.remove('active');

        var formData = new FormData();
        formData.append('producto_id', productoSeleccionado);
        formData.append('cantidad', 1);

        fetch('../controllers/carrito_controller.php?accion=agregar', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var alerta = document.getElementById('alerta');
            if (data.ok) {
                alerta.className = 'alerta ok';
                alerta.textContent = '✅ ' + data.mensaje;
                var badge = document.getElementById('badge-carrito');
                badge.textContent = data.total_carrito;
                badge.style.display = 'inline';
            } else {
                alerta.className = 'alerta error';
                alerta.textContent = '❌ ' + data.error;
            }
            alerta.style.display = 'block';
            setTimeout(function() { alerta.style.display = 'none'; }, 3000);
        });
        productoSeleccionado = null;
    });

    /* Carga inicial del badge del carrito con el contador actual */
    fetch('../controllers/carrito_controller.php?accion=contar')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.total > 0) {
            var badge = document.getElementById('badge-carrito');
            badge.textContent = data.total;
            badge.style.display = 'inline';
        }
    });

});
</script>
</body>
</html>