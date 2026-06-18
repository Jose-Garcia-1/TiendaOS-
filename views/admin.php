<?php
/* Panel de Administración de TiendaOS.
   Control de acceso: Verifica sesión activa y permisos de administrador.
   Carga de datos: Obtiene métricas, listados de productos, pedidos y usuarios. */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php"); exit;
}
include '../config/db.php';

/* Verificación del rol del usuario para garantizar permisos de administrador. */
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();
if (!$user || $user['rol'] !== 'admin') {
    header("Location: catalogo.php"); exit;
}

/* Obtención de estadísticas generales del sistema (usuarios, productos, pedidos, ventas) */
$total_usuarios  = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_productos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$total_pedidos   = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$total_ventas    = $pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos")->fetchColumn();

/* Obtención de la lista completa de productos, últimos pedidos y lista de usuarios */
$productos = $pdo->query("SELECT * FROM productos ORDER BY stock ASC")->fetchAll();

$pedidos = $pdo->query("
    SELECT p.id, p.total, p.fecha,
           'completado' AS estado,
           u.username,
           GROUP_CONCAT(pr.nombre SEPARATOR ', ') AS productos
    FROM pedidos p
    JOIN usuarios u ON u.id = p.usuario_id
    JOIN detalle_pedido dp ON dp.pedido_id = p.id
    JOIN productos pr ON pr.id = dp.producto_id
    GROUP BY p.id, p.total, p.fecha, u.username
    ORDER BY p.fecha DESC
    LIMIT 20
")->fetchAll();

$usuarios = $pdo->query("SELECT id, username, email, rol FROM usuarios ORDER BY id DESC")->fetchAll();

$seccion = $_GET['s'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Admin — TiendaOS</title>
<style>
   /* --- BOTÓN AGREGAR PRODUCTO --- */
.btn-agregar-producto {
    width: 25%;
    padding: 14px 20px;
    font-size: 1rem;
    background: #0ea5e9;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(14, 165, 233, 0.3);
}
.btn-agregar-producto:hover {
    background: #0284c7;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(14, 165, 233, 0.4);
}
.btn-agregar-producto:active {
    transform: translateY(0);
}
/* --- RESET Y BASE --- */
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    font-family:'Segoe UI',sans-serif; 
    background:#f1f5f9; 
    color:#000000;
    min-height:100vh; display:flex; 
}

/* --- SIDEBAR --- */
.sidebar {
    width: 220px; min-height: 100vh; 
    background: #787c83; 
    border-right: 1px solid #6b6f76; 
    padding: 24px 0; 
    position: fixed;
    display: flex; 
    flex-direction: column; 
}
.sidebar .brand { 
    color: #ffffff; 
    font-size: 1.2rem; 
    font-weight: 700; 
    padding: 0 20px 24px; 
    border-bottom: 1px solid #6b6f76; 
}

/* Contenedor del menú y parte inferior */
.sidebar nav, .sidebar .bottom {
    padding: 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.sidebar .bottom {
    margin-top: auto;
    border-top: 1px solid #6b6f76;
    padding-top: 16px;
}

/* Estilo único para los botones del menú y sección inferior */
.sidebar nav a, .sidebar .bottom a {
    display: flex; 
    align-items: center; 
    gap: 12px;
    padding: 14px 18px;
    color: #ffffff; 
    text-decoration: none;
    font-size: 0.9rem; 
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 10px;
    transition: all 0.2s;
}

/* Estado normal */
.sidebar nav a:not(.active), .sidebar .bottom a {
    border-color: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

/* Hover */
.sidebar nav a:hover, .sidebar .bottom a:hover {
    border-color: #38bdf8;
    color: #38bdf8;
    background: rgba(255, 255, 255, 0.08);
}

/* Estado ACTIVO */
.sidebar nav a.active {
    border-color: #38bdf8;
    color: #38bdf8;
    background: rgba(255, 255, 255, 0.08);
}

/* Hover del botón de cerrar sesión */
.sidebar .bottom a.btn-logout:hover {
    border-color: #f87171;
    color: #f87171;
    background: rgba(248, 113, 113, 0.1);
}

/* --- MAIN --- */
.main { margin-left:220px; flex:1; padding:32px; }
.page-title { font-size:1.6rem; color:#000000; margin-bottom:8px; }
.page-sub { color:#374151; font-size:0.9rem; margin-bottom:32px; }

/* --- STATS --- */
.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:32px; }
.stat-card { 
    background:#ffffff; 
    border: 1px solid #d1d5db;
    border-radius:12px; padding:20px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
}
.stat-card .num { font-size:1.8rem; font-weight:700; margin-bottom:4px; }
.stat-card .lbl { color:#374151; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; }

/* Colores para los números de las estadísticas */
.stat-card.blue .num { color:#0ea5e9; }
.stat-card.green .num { color:#10b981; }
.stat-card.purple .num { color:#8b5cf6; }
.stat-card.amber .num { color:#f59e0b; }

/* --- TABS --- */
.tabs { display:flex; gap:8px; margin-bottom:24px; }
.tab {
    padding:8px 20px; border-radius:8px; text-decoration:none;
    font-size:0.85rem; font-weight:500; color:#4b5563;
    border:1px solid #d1d5db; transition:all 0.2s; background:#ffffff;
}
.tab:hover, .tab.active { background:#0ea5e9; color:white; border-color:#0ea5e9; }

/* --- TABLE --- */
.table-card { 
    background:#ffffff; border:1px solid #d1d5db; border-radius:14px; 
    overflow:hidden; margin-bottom:24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.table-header { padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; }
.table-header h2 { color:#000000; font-size:1rem; }
table { width:100%; border-collapse:collapse; }
th { padding:12px 16px; text-align:left; color:#374151; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e5e7eb; }
td { padding:14px 16px; font-size:0.88rem; color:#000000; border-bottom:1px solid #f3f4f6; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#f9fafb; }

/* --- BADGES --- */
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:500; }
.badge-admin { background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe; }
.badge-cliente { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.badge-low { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.badge-ok { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }

/* --- FORM CRUD --- */
.form-card { 
    background:#ffffff; border:1px solid #d1d5db; border-radius:14px; padding:24px; 
    margin-bottom:24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.form-card h2 { color:#000000; margin-bottom:20px; font-size:1rem; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { color:#374151; font-size:0.82rem; }

/* Inputs, selects y textareas */
.form-group input, .form-group select, .form-group textarea {
    padding:10px 12px; background:#ffffff; border:1px solid #d1d5db;
    border-radius:8px; color:#000000; font-size:0.9rem; font-family:inherit;
    transition: border 0.2s; width:100%; resize:vertical;
}
.form-group textarea { min-height:80px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
    outline:none; border-color:#0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); 
}

/* --- BOTONES --- */
.btn-primary { 
    padding:10px 24px; background:#d1d5db; border:none; border-radius:8px; 
    color:black; font-weight:600; cursor:pointer; font-size:0.9rem; 
}
.btn-primary:hover { background:#aaacae; }

.btn-edit { 
    padding:6px 14px; background:transparent; border:1px solid #d1d5db; 
    border-radius:6px; color:#0ea5e9; cursor:pointer; font-size:0.8rem; margin-right:6px; 
}
.btn-edit:hover { background:#f3f4f6; }

.btn-danger { 
    padding:6px 14px; background:transparent; border:1px solid #fee2e2; 
    border-radius:6px; color:#ef4444; cursor:pointer; font-size:0.8rem; 
}
.btn-danger:hover { background:#fef2f2; }

/* --- ALERTAS --- */
.alerta { display:none; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:0.875rem; }
.alerta.ok { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.alerta.error { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

/* --- MODAL STOCK --- */
.modal-overlay {
    position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.4); display:none; 
    align-items: center; justify-content: center; z-index:9999;
}
.modal-content {
    background:#ffffff; border:1px solid #d1d5db; border-radius:14px;
    width:400px; max-width:90%; box-shadow:0 5px 20px rgba(0,0,0,0.15);
    color:#000000; animation:fadeIn 0.3s;
}
.modal-header { padding:16px 24px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; }
.modal-header h2 { margin:0; font-size:1.2rem; color:#000000; }
.close-modal { cursor:pointer; font-size:24px; color:#6b7280; }
.close-modal:hover { color:#000000; }

.modal-body { padding:24px; }
.modal-footer { padding:16px 24px; border-top:1px solid #e5e7eb; display:flex; gap:12px; justify-content:flex-end; }
.btn-secundary { background:#f3f4f6; border:none; color:#374151; padding:8px 20px; border-radius:8px; cursor:pointer; }
.btn-secundary:hover { background:#e5e7eb; }

@keyframes fadeIn { from{opacity:0;transform:scale(0.95)} to{opacity:1;transform:scale(1)} }
</style>
</head>
<body>

<!-- Barra lateral de navegación del panel de administración -->
<div class="sidebar">
    <div class="brand">⚙️ Admin Panel</div>
    <nav>
        <a href="?s=dashboard" class="<?= $seccion==='dashboard'?'active':'' ?>">📊 Dashboard</a>
        <a href="?s=productos" class="<?= $seccion==='productos'?'active':'' ?>">📦 Productos</a>
        <a href="?s=pedidos"   class="<?= $seccion==='pedidos'  ?'active':'' ?>">🧾 Pedidos</a>
        <a href="?s=usuarios"  class="<?= $seccion==='usuarios' ?'active':'' ?>">👥 Usuarios</a>
    </nav>
    <div class="bottom">
        <a href="catalogo.php">Volver a la tienda</a>
        <a href="../logout.php" class="btn-logout">Cerrar sesión</a>
    </div>
</div>

<div class="main">

<?php if ($seccion === 'dashboard'): ?>
    <!-- Sección principal: Dashboard y estadísticas de la tienda -->
    <div class="page-title">📊 Dashboard</div>
    <p class="page-sub">Resumen general de TiendaOS</p>

    <!-- Tarjetas de resumen con estadísticas clave del sistema -->
    <div class="stats">
        <div class="stat-card blue">
            <div class="num"><?= $total_usuarios ?></div>
            <div class="lbl">Usuarios registrados</div>
        </div>
        <div class="stat-card purple">
            <div class="num"><?= $total_productos ?></div>
            <div class="lbl">Productos activos</div>
        </div>
        <div class="stat-card amber">
            <div class="num"><?= $total_pedidos ?></div>
            <div class="lbl">Pedidos realizados</div>
        </div>
        <div class="stat-card green">
            <div class="num">S/ <?= number_format($total_ventas, 2) ?></div>
            <div class="lbl">Total en ventas</div>
        </div>
    </div>

    <!-- Tabla de últimos pedidos registrados en el sistema -->
    <div class="table-card">
        <div class="table-header"><h2>🧾 Últimos pedidos</h2></div>
        <table>
            <thead><tr><th>#</th><th>Cliente</th><th>Productos</th><th>Total</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($pedidos, 0, 5) as $p): ?>
            <tr>
                <td style="color:#38bdf8;font-weight:700">#<?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['username']) ?></td>
                <td style="color:#94a3b8;max-width:250px"><?= htmlspecialchars($p['productos']) ?></td>
                <td style="color:#000000;font-weight:600">S/ <?= number_format($p['total'], 2) ?></td>
                <td style="color:#475569;font-size:0.8rem"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Sección de monitoreo del sistema operativo. Conecta con el servidor Windows a través de peticiones HTTP. -->
    <div class="table-card" style="margin-top:24px;">
        <div class="table-header">
            <h2>🖥️ Monitoreo del Sistema Operativo</h2>
            <button class="btn-primary" onclick="actualizarRecursos()" style="padding:6px 16px; font-size:0.8rem;">⟳ Actualizar</button>
        </div>
        <div id="reporte-recursos" style="padding:20px; font-family:monospace; font-size:0.85rem; color:#000000; background:#f8fafc; border-radius:0 0 14px 14px;">
            <p style="color:#4b5563;">Haz clic en "Actualizar" para ver los recursos del servidor.</p>
        </div>
    </div>

<?php elseif ($seccion === 'productos'): ?>
    <!-- Sección de productos: Gestión de catálogo y CRUD -->
    <div class="page-title">📦 Gestión de Productos</div>
    <p class="page-sub">Agrega, edita o elimina productos del catálogo</p>

    <div class="alerta" id="alerta"></div>

    <!-- Formulario para agregar nuevos productos al catálogo -->
    <div class="form-card">
    <h2>➕ Agregar nuevo producto</h2>
    <div class="alerta" id="alerta"></div>
    <div class="form-grid">
        <div class="form-group">
            <label>Nombre del producto</label>
            <input type="text" id="np-nombre" placeholder="Ej: Laptop HP Pavilion">
        </div>
        <div class="form-group">
            <label>Precio (S/)</label>
            <input type="number" id="np-precio" placeholder="0.00" step="0.01">
        </div>
        <div class="form-group">
            <label>Stock inicial</label>
            <input type="number" id="np-stock" placeholder="0">
        </div>
        <div class="form-group">
            <label>Categoría</label>
            <select id="np-categoria">
                <option value="laptops">Laptops</option>
                <option value="mouse">Mouse</option>
                <option value="teclados">Teclados</option>
                <option value="audifonos">Audífonos</option>
                <option value="monitores">Monitores</option>
                <option value="almacenamiento">Almacenamiento</option>
                <option value="componentes">Componentes</option>
                <option value="perifericos">Periféricos</option>
                <option value="otros">Otros</option>
            </select>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
            <label>Descripción breve</label>
            <textarea id="np-descripcion" placeholder="Describe las características principales del producto..."></textarea>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
            <label>URL de imagen</label>
            <input type="text" id="np-imagen" placeholder="https://...">
        </div>
        <div class="form-group" style="justify-content:flex-end;">
            <button class="btn-agregar-producto" onclick="agregarProducto()">Agregar producto</button>
        </div>
    </div>
</div>

    <!-- Tabla para gestionar el listado de productos existentes -->
    <div class="table-card">
        <div class="table-header"><h2>Lista de productos</h2></div>
        <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
            <tbody id="tabla-productos">
            <?php foreach ($productos as $p): ?>
            <tr id="row-<?= $p['id'] ?>">
                <td style="color:#475569">#<?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['nombre']) ?></td>
                <td style="color:#38bdf8">S/ <?= number_format($p['precio'], 2) ?></td>
                <td>
                    <span class="badge <?= $p['stock'] <= 5 ? 'badge-low' : 'badge-ok' ?>">
                        <?= $p['stock'] ?> uds
                    </span>
                </td>
                <td>
    <button class="btn-edit" onclick="abrirModalStock(
    <?= $p['id'] ?>,
    '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>',
    <?= $p['stock'] ?>
)">✏️ Editar Stock</button>
    <button class="btn-danger" onclick="eliminarProducto(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>')">🗑️</button>
</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($seccion === 'pedidos'): ?>
    <!-- Sección de pedidos: Historial global de transacciones -->
    <div class="page-title">🧾 Todos los pedidos</div>
    <p class="page-sub">Historial completo de compras en la plataforma</p>

    <div class="table-card">
        <div class="table-header"><h2><?= count($pedidos) ?> pedidos registrados</h2></div>
        <table>
            <thead><tr><th>#</th><th>Cliente</th><th>Productos</th><th>Total</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
            <tr>
                <td style="color:#38bdf8;font-weight:700">#<?= $p['id'] ?></td>
                <td style="color:#a78bfa"><?= htmlspecialchars($p['username']) ?></td>
                <td style="color:#94a3b8;max-width:280px;font-size:0.82rem"><?= htmlspecialchars($p['productos']) ?></td>
                <td style="color:#000000;font-weight:600">S/ <?= number_format($p['total'], 2) ?></td>
                <td style="color:#475569;font-size:0.8rem"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($seccion === 'usuarios'): ?>
    <!-- Sección de usuarios: Administración de roles y cuentas -->
    <div class="page-title">👥 Gestión de Usuarios</div>
    <p class="page-sub">Administra los roles y cuentas de la plataforma</p>

    <div class="table-card">
        <div class="table-header"><h2><?= count($usuarios) ?> usuarios registrados</h2></div>
        <table>
            <thead><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td style="color:#475569">#<?= $u['id'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($u['username']) ?></td>
                <td style="color:#64748b;font-size:0.82rem"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge <?= $u['rol']==='admin'?'badge-admin':'badge-cliente' ?>"><?= $u['rol'] ?></span></td>
                <td>
                    <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                    <button class="btn-edit" onclick="cambiarRol(<?= $u['id'] ?>, '<?= $u['rol'] ?>')">
                        <?= $u['rol']==='admin' ? '→ cliente' : '→ admin' ?>
                    </button>
                    <?php else: ?>
                    <span style="color:#475569;font-size:0.8rem">Tú</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

</div>

<script>
/* Gestión de la interfaz del modal de stock (Apertura, cierre y guardado) */
function abrirModalStock(id, nombre, stockActual) {
    document.getElementById('edit-stock-id').value = id;
    document.getElementById('edit-stock-nombre').innerText = nombre;
    document.getElementById('edit-stock-actual').innerText = stockActual + ' uds';
    document.getElementById('edit-stock-nuevo').value = stockActual;
    document.getElementById('modalStock').style.display = 'flex';
}

function cerrarModalStock() {
    document.getElementById('modalStock').style.display = 'none';
}

function guardarStock() {
    var id = document.getElementById('edit-stock-id').value;
    var nuevoStock = parseInt(document.getElementById('edit-stock-nuevo').value);

    if (isNaN(nuevoStock) || nuevoStock < 0) {
        mostrarAlerta('❌ Ingresa un stock válido (número mayor o igual a 0)', 'error');
        return;
    }

    var fd = new FormData();
    fd.append('accion', 'editar_stock');
    fd.append('id', id);
    fd.append('stock', nuevoStock);

    fetch('../controllers/admin_controller.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(function(data) {
        if (data.ok) {
            cerrarModalStock();
            mostrarAlerta('✅ Stock actualizado correctamente', 'ok');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            mostrarAlerta('❌ ' + data.error, 'error');
        }
    });
}

/* Funciones para mostrar alertas y realizar el CRUD de productos */
function mostrarAlerta(msg, tipo) {
    var a = document.getElementById('alerta');
    if (!a) return;
    a.className = 'alerta ' + tipo;
    a.textContent = msg;
    a.style.display = 'block';
    setTimeout(function() { a.style.display = 'none'; }, 3000);
}

function agregarProducto() {
    var nombre      = document.getElementById('np-nombre').value.trim();
    var precio      = document.getElementById('np-precio').value;
    var stock       = document.getElementById('np-stock').value;
    var categoria   = document.getElementById('np-categoria').value;
    var descripcion = document.getElementById('np-descripcion').value.trim();
    var imagen      = document.getElementById('np-imagen').value.trim();

    if (!nombre || !precio || !stock) {
        mostrarAlerta('❌ Nombre, precio y stock son obligatorios', 'error'); return;
    }

    var fd = new FormData();
    fd.append('accion',      'agregar');
    fd.append('nombre',      nombre);
    fd.append('precio',      precio);
    fd.append('stock',       stock);
    fd.append('categoria',   categoria);
    fd.append('descripcion', descripcion);
    fd.append('imagen',      imagen);

    fetch('../controllers/admin_controller.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(function(data) {
        if (data.ok) {
            mostrarAlerta('✅ Producto agregado', 'ok');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            mostrarAlerta('❌ ' + data.error, 'error');
        }
    });
}

function eliminarProducto(id, nombre) {
    if (!confirm('¿Eliminar "' + nombre + '"?')) return;

    var fd = new FormData();
    fd.append('accion', 'eliminar');
    fd.append('id', id);

    fetch('../controllers/admin_controller.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(function(data) {
        if (data.ok) {
            document.getElementById('row-' + id).remove();
            mostrarAlerta('✅ Producto eliminado', 'ok');
        }
    });
}

function cambiarRol(id, rolActual) {
    var nuevoRol = rolActual === 'admin' ? 'cliente' : 'admin';
    if (!confirm('¿Cambiar rol a ' + nuevoRol + '?')) return;

    var fd = new FormData();
    fd.append('accion', 'cambiar_rol');
    fd.append('id', id);
    fd.append('rol', nuevoRol);

    fetch('../controllers/admin_controller.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(function(data) {
        if (data.ok) { location.reload(); }
    });
}

/* Petición asíncrona al controlador para obtener el reporte de recursos del sistema operativo */
function actualizarRecursos() {
    document.getElementById('reporte-recursos').innerHTML = '<p style="color:#4b5563;">Actualizando...</p>';
    fetch('../controllers/admin_controller.php?accion=recursos')
    .then(r => r.text())
    .then(data => {
        document.getElementById('reporte-recursos').innerHTML = data;
    })
    .catch(err => {
        document.getElementById('reporte-recursos').innerHTML = '<p style="color:#ef4444;">❌ Error al obtener recursos</p>';
    });
}
</script>

<!-- Modal para actualizar la cantidad de stock de un producto de forma rápida -->
<div id="modalStock" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📦 Actualizar Stock</h2>
            <span class="close-modal" onclick="cerrarModalStock()">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-stock-id">
            <div class="form-group" style="margin-bottom:10px;">
                <label style="color:#94a3b8;">Nombre del producto:</label>
                <div id="edit-stock-nombre" style="color:#f1f5f9; font-weight:600;"></div>
            </div>
            <div class="form-group" style="margin-bottom:10px;">
                <label style="color:#94a3b8;">Stock actual:</label>
                <div id="edit-stock-actual" style="color:#38bdf8; font-weight:600;"></div>
            </div>
            <div class="form-group">
                <label style="color:#94a3b8;">Nuevo stock:</label>
                <input type="number" id="edit-stock-nuevo" placeholder="0" style="width:100%; padding:10px; background:#ffffff; border:1px solid #d1d5db; border-radius:8px; color:#000000; font-size:1rem;">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secundary" onclick="cerrarModalStock()">Cancelar</button>
            <button class="btn-primary" onclick="guardarStock()">💾 Guardar Stock</button>
        </div>
    </div>
</div>
</body>
</html>