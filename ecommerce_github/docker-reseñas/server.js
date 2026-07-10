const express = require('express');
const cors = require('cors');
const { MongoClient, ObjectId } = require('mongodb');

const app = express();
app.use(cors());
app.use(express.json());

/* Conexión a MongoDB nativo en el servidor Linux */
const url = 'mongodb://localhost:27017';
const dbName = 'ecommerce';
let db;

MongoClient.connect(url)
  .then(client => {
    db = client.db(dbName);
    console.log('✅ Conectado a MongoDB desde Docker');
  })
  .catch(err => console.error('❌ Error conectando a MongoDB:', err));

/* GET /resenas/:producto_id */
app.get('/resenas/:producto_id', async (req, res) => {
  const producto_id = parseInt(req.params.producto_id);
  try {
    const collection = db.collection('resenas');
    const results = await collection.find({ producto_id }).sort({ fecha_creacion: -1 }).toArray();
    res.json(results);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

/* POST /resenas */
app.post('/resenas', async (req, res) => {
  const { producto_id, usuario_id, calificacion, comentario } = req.body;
  if (!producto_id || !usuario_id || !calificacion) {
    return res.status(400).json({ error: 'Faltan campos' });
  }
  try {
    const collection = db.collection('resenas');
    const result = await collection.insertOne({
      producto_id,
      usuario_id,
      calificacion,
      comentario: comentario || '',
      fecha_creacion: new Date()
    });
    res.json({ ok: true, id: result.insertedId });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/health', (req, res) => {
  res.json({ status: 'ok', service: 'resenas-docker-mongo' });
});

app.listen(3000, () => console.log('🚀 Servicio reseñas en puerto 3000 (MongoDB)'));