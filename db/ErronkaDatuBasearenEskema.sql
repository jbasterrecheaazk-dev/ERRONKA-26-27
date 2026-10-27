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

-- 3. Taula: Produktuak (Artículos a la venta)
CREATE TABLE produktuak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izenburua VARCHAR(150) NOT NULL,
    deskribapena TEXT NOT NULL,
    prezioa DECIMAL(10, 2) NOT NULL,
    egoera ENUM('Mint', 'Near Mint', 'Good', 'Fair', 'Poor') NOT NULL, -- Goldmine standard
    irudia VARCHAR(255), -- Ruta de la imagen subida
    saltzaile_id INT NOT NULL,
    kategoria_id INT,
    salgai BOOLEAN DEFAULT TRUE, -- TRUE = disponible, FALSE = vendido
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