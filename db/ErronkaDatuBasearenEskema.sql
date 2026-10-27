-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS SoinuTruk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE SoinuTruk;

-- 1. Taula: Erabiltzaileak (Usuarios: pueden ser compradores y vendedores)
CREATE TABLE erabiltzaileak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izena VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    pasahitza VARCHAR(255) NOT NULL, -- Aquí guardaremos el hash de la contraseña
    rol ENUM('admin', 'erabiltzailea') DEFAULT 'erabiltzailea',
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Taula: Kategoriak (Categorías: Biniloak, CDak, Kasetak, Merchandising...)
CREATE TABLE kategoriak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izena VARCHAR(50) NOT NULL UNIQUE
);

-- Semillas de categorías por defecto
INSERT INTO kategoriak (izena) VALUES
('Biniloak'),
('CDak'),
('Kasetak'),
('Instrumentu musikala'),
('Merchandising'),
('Sondu ekipamendua'),
('Bestelakoa');

-- 3. Taula: Produktuak (Artículos a la venta)
CREATE TABLE produktuak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izenburua VARCHAR(150) NOT NULL,
    deskribapena TEXT NOT NULL,
    prezioa DECIMAL(10, 2) NOT NULL,
    egoera ENUM('Mint', 'Near Mint', 'Good', 'Fair', 'Poor') DEFAULT NULL,
    irudia VARCHAR(255),
    saltzaile_id INT NOT NULL,
    kategoria_id INT,
    artista VARCHAR(150) DEFAULT NULL,
    urtea SMALLINT DEFAULT NULL,
    salgai BOOLEAN DEFAULT TRUE,
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (saltzaile_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (kategoria_id) REFERENCES kategoriak(id) ON DELETE SET NULL
);

-- 4. Taula: Eskaerak (Pedidos/Transacciones)
CREATE TABLE eskaerak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    erosle_id INT NOT NULL,
    produktu_id INT NOT NULL,
    totala DECIMAL(10, 2) NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (erosle_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (produktu_id) REFERENCES produktuak(id) ON DELETE CASCADE
);

-- 5. Taula: Carrito (Items en el carrito de compra temporal)
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    erabiltzaile_id INT NOT NULL,
    produktu_id INT NOT NULL,
    kantitatea INT NOT NULL DEFAULT 1,
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (erabiltzaile_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (produktu_id) REFERENCES produktuak(id) ON DELETE CASCADE
);