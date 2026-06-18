const express = require('express');
const mysql   = require('mysql2');
const cors    = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

/* Configuración del pool de conexiones a MySQL.
   El pool gestiona automáticamente las reconexiones y limita el número
   de conexiones simultáneas para optimizar el rendimiento del servicio. */
const pool = mysql.createPool({
  host              : 'host.docker.internal',
  port              : 3306,
  user              : 'root',
  password          : '',
  database          : 'ecommerce_db',
  waitForConnections: true,
  connectionLimit   : 10,
  queueLimit        : 0
});

/* Verificación de la conexión a la base de datos al momento de iniciar el servicio. */
pool.getConnection((err, conn) => {
  if (err) console.error('Error MySQL:', err.message);
  else { console.log('Conectado a MySQL desde Docker'); conn.release(); }
});

/* Endpoint GET para obtener la lista de reseñas asociadas a un producto específico.
   Incluye el nombre de usuario de quien escribió cada reseña. */
app.get('/resenas/:producto_id', (req, res) => {
  const producto_id = parseInt(req.params.producto_id);
  pool.query(
    `SELECT r.id, r.calificacion, r.comentario, r.fecha_creacion, u.username
     FROM resenas r
     JOIN usuarios u ON u.id = r.usuario_id
     WHERE r.producto_id = ?
     ORDER BY r.fecha_creacion DESC`,
    [producto_id],
    (err, results) => {
      if (err) return res.status(500).json({ error: err.message });
      res.json(results);
    }
  );
});

/* Endpoint POST para crear una nueva reseña.
   Valida que los campos requeridos estén presentes antes de insertar el registro. */
app.post('/resenas', (req, res) => {
  const { producto_id, usuario_id, calificacion, comentario } = req.body;
  if (!producto_id || !usuario_id || !calificacion) {
    return res.status(400).json({ error: 'Faltan campos' });
  }
  pool.query(
    `INSERT INTO resenas (producto_id, usuario_id, calificacion, comentario) VALUES (?, ?, ?, ?)`,
    [producto_id, usuario_id, calificacion, comentario || ''],
    (err, result) => {
      if (err) return res.status(500).json({ error: err.message });
      res.json({ ok: true, id: result.insertId });
    }
  );
});

/* Endpoint para verificar el estado de funcionamiento del servicio. */
app.get('/health', (req, res) => {
  res.json({ status: 'ok', service: 'resenas-docker' });
});

/* Inicialización del servidor en el puerto 3000. */
app.listen(3000, () => console.log('Servicio de reseñas corriendo en puerto 3000'));