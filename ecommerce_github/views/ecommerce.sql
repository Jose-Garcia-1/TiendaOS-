CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL
);

CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL
);
ALTER TABLE productos ADD COLUMN descripcion TEXT DEFAULT NULL;

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE detalle_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

CREATE TABLE historial_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    calificacion INT CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

INSERT INTO productos (nombre, precio, stock) VALUES
('Laptop Lenovo IdeaPad', 2499.99, 10),
('Mouse Logitech G305', 149.90, 25),
('Teclado Mecánico Redragon', 199.90, 15),
('Audífonos Sony WH-1000XM4', 899.90, 8),
('Monitor LG 24" Full HD', 699.90, 5);

INSERT INTO productos (nombre, precio, stock) VALUES
('Laptop Lenovo IdeaPad', 2499.99, 10),
('Mouse Logitech G305', 149.90, 25),
('Teclado Mecánico Redragon K552', 199.90, 15),
('Audífonos Sony WH-1000XM4', 899.90, 8),
('Monitor LG 24" Full HD', 699.90, 5),
('SSD Kingston 1TB NVMe', 299.90, 20),
('Memoria RAM Corsair 16GB DDR4', 189.90, 18),
('Webcam Logitech C920', 399.90, 12),
('Disco Duro Seagate 2TB', 249.90, 10),
('Tarjeta Gráfica GTX 1650', 1299.90, 4);

DELETE p1 FROM productos p1
INNER JOIN productos p2
WHERE p1.id > p2.id AND p1.nombre = p2.nombre;

CREATE TABLE IF NOT EXISTS carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    agregado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    UNIQUE KEY uq_carrito (usuario_id, producto_id)
);

ALTER TABLE usuarios ADD COLUMN rol ENUM('cliente','admin') DEFAULT 'cliente';

-- Convierte tu usuario actual en admin
UPDATE usuarios SET rol = 'admin' WHERE username = 'whoislolo';

ALTER TABLE productos ADD COLUMN categoria VARCHAR(50) DEFAULT 'otros';

UPDATE productos SET categoria = 'laptops'    WHERE nombre LIKE '%Laptop%';
UPDATE productos SET categoria = 'mouse'      WHERE nombre LIKE '%Mouse%';
UPDATE productos SET categoria = 'teclados'   WHERE nombre LIKE '%Teclado%';
UPDATE productos SET categoria = 'audifonos'  WHERE nombre LIKE '%Audfono%' OR nombre LIKE '%Sony%';
UPDATE productos SET categoria = 'monitores'  WHERE nombre LIKE '%Monitor%';
UPDATE productos SET categoria = 'almacenamiento' WHERE nombre LIKE '%SSD%' OR nombre LIKE '%Disco%';
UPDATE productos SET categoria = 'componentes' WHERE nombre LIKE '%RAM%' OR nombre LIKE '%Tarjeta%';
UPDATE productos SET categoria = 'perifericos' WHERE nombre LIKE '%Webcam%';

ALTER TABLE productos ADD COLUMN imagen_url VARCHAR(500) DEFAULT NULL;
UPDATE productos SET imagen_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSMRkoaNEq9vrMaHAbpYnUT9szPfsOz2OElJQ&s' WHERE nombre = 'Teclado Mecánico Redragon';
UPDATE productos SET imagen_url = 'https://http2.mlstatic.com/D_NQ_NP_701641-MLA100047315677_122025-O.webp' WHERE nombre LIKE '%K552%';
UPDATE productos SET imagen_url = 'https://http2.mlstatic.com/D_NQ_NP_733026-MLB47473913836_092021-O.webp' WHERE nombre LIKE '%C920%';
UPDATE productos SET imagen_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT0P2Ra_k8naB5uOzJNvmBu52HxIQ1_8lI9Lg&s' WHERE nombre LIKE '%G502%';
UPDATE productos SET imagen_url = 'https://production-tailoy-repo-magento-statics.s3.amazonaws.com/imagenes/872x872/productos/i/m/o/mouse-inal-gaming-logitech-g305-negro-90177-default-1.jpg' WHERE nombre LIKE '%G305%';
UPDATE productos SET imagen_url = 'https://www.kabifperu.com/imagenes/prod-06022021201022-monitor-lg-led-24-24mk430h-b-deta.png' WHERE nombre LIKE '%Monitor LG%';
UPDATE productos SET imagen_url = 'https://media.falabella.com/tottusPE/43184817_1/w=800,h=800,fit=pad' WHERE nombre LIKE '%IdeaPad%';
UPDATE productos SET imagen_url = 'https://m.media-amazon.com/images/I/51OXdEFdzRL._AC_UF894,1000_QL80_.jpg' WHERE nombre LIKE '%Legion%';
UPDATE productos SET imagen_url = 'https://oechsle.vteximg.com.br/arquivos/ids/24725925-1000-1000/Image-1.jpg?v=639095282575900000' WHERE nombre LIKE '%Corsair%';
UPDATE productos SET imagen_url = 'https://http2.mlstatic.com/D_Q_NP_782666-MLU78827169798_092024-O.webp' WHERE nombre LIKE '%GTX 1650%';
UPDATE productos SET imagen_url = 'https://media.falabella.com/falabellaPE/126067662_01/public' WHERE nombre LIKE '%Sony%';
UPDATE productos SET imagen_url = 'https://rymportatiles.com.pe/cdn/shop/files/9259.2.png?v=1742337921&width=1214' WHERE nombre LIKE '%SSD Kingston%';
UPDATE productos SET imagen_url = 'https://www.kabifperu.com/imagenes/prod-06032021230514-hdd-seagate-2tb-st2000dm006-verde-64mb-7200rpm-computer-deta.jpg' WHERE nombre LIKE '%Seagate%';