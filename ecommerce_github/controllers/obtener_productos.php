<?php
/* 1. Incluir la configuración de la conexión a la base de datos. */
include __DIR__ . '/../config/db.php'; 

try {
    /* 2. Ejecutar la consulta para obtener todos los registros de la tabla productos. */
    $stmt = $pdo->query("SELECT * FROM productos");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    /* 3. Manejo de errores básico en caso de fallo en la conexión o la consulta. */
    echo "Error al obtener productos: " . $e->getMessage();
}
?>