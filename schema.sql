CREATE DATABASE IF NOT EXISTS tienda_mascotas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tienda_mascotas;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255),
  google_id VARCHAR(255) DEFAULT NULL,
  telefono VARCHAR(50),
  direccion TEXT,
  rol ENUM('cliente','admin') DEFAULT 'cliente',
  voice_enrolled TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT
);

CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL,
  stock INT DEFAULT 0,
  imagen VARCHAR(255),
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT,
  total DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente','pagado','cancelado') DEFAULT 'pendiente',
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE detalles_compra (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT,
  producto_id INT,
  cantidad INT,
  precio_unitario DECIMAL(10,2),
  FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
);

CREATE TABLE boletas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT UNIQUE,
  numero_boleta VARCHAR(50),
  archivo_pdf VARCHAR(255),
  enviado_mail TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE
);

CREATE TABLE chatbot_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT DEFAULT NULL,
  session_id VARCHAR(255),
  entrada TEXT,
  respuesta TEXT,
  sentimiento ENUM('positivo','neutral','negativo') DEFAULT 'neutral',
  origen ENUM('voz','texto') DEFAULT 'texto',
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Seed admin user (replace password hash via README or create_admin.php)
INSERT INTO usuarios (nombre, email, password, rol, created_at) VALUES
('Administrador', 'admin@local.test', '{PASSWORD_HASH_PLACEHOLDER}', 'admin', NOW());

-- Seed categories
INSERT INTO categorias (nombre, descripcion) VALUES
 ('Collares', 'Collares para perros y gatos'),
 ('Juguetes', 'Juguetes resistentes y seguros'),
 ('Higiene', 'Productos de limpieza y cuidado'),
 ('Camas', 'Camas y descanso');

-- Seed products
INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, imagen) VALUES
 (1, 'Collar de cuero', 'Collar de cuero ajustable para perro', 39.90, 25, 'img/collar-de-cuero.jpg'),
 (1, 'Placa identificatoria', 'Placa grabada con el nombre de tu mascota', 19.90, 100, 'img/placa-identificatoria.jpg'),
 (2, 'Pelota con sonido', 'Pelota de goma con sonido para jugar', 14.50, 60, 'img/pelota-con-sonido.jpg'),
 (2, 'Cuerda mordedora', 'Cuerda resistente para morder y tirar', 24.90, 40, 'img/cuerda-mordedora.jpg'),
 (3, 'Shampoo neutro', 'Shampoo suave para piel sensible', 22.00, 35, 'img/shampoo-neutro.jpg'),
 (3, 'Toallas húmedas', 'Toallas para limpieza rápida', 12.90, 80, 'img/toallas-humedas.jpg'),
 (4, 'Cama acolchada M', 'Cama tamaño mediano, lavable', 89.00, 15, 'img/cama-acolchada-m.jpg'),
 (4, 'Manta suave', 'Manta polar para mascotas', 29.90, 50, 'img/manta-suave.jpg');
