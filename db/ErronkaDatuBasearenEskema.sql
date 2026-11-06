-- Crear y seleccionar la base de datos
CREATE DATABASE IF NOT EXISTS SoinuTruk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE SoinuTruk;

-- Limpieza ordenada de tablas respetando claves ajenas (FK)
DROP TABLE IF EXISTS saskia;
DROP TABLE IF EXISTS eskaerak;
DROP TABLE IF EXISTS produktuak;
DROP TABLE IF EXISTS kategoriak;
DROP TABLE IF EXISTS erabiltzaileak;

-- 1. Taula: Erabiltzaileak (Usuarios: compradores y vendedores)
CREATE TABLE erabiltzaileak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izena VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    pasahitza VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'erabiltzailea') DEFAULT 'erabiltzailea',
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Taula: Kategoriak (Categorías de productos)
CREATE TABLE kategoriak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izena VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Semillas de categorías corregidas
INSERT INTO kategoriak (izena) VALUES
('Biniloak'),
('CDak'),
('Kasetak'),
('Instrumentu musikala'),
('Merchandising'),
('Soinu ekipamendua'),
('Bestelakoak');

-- 3. Taula: Produktuak (Artículos a la venta)
CREATE TABLE produktuak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izenburua VARCHAR(150) NOT NULL,
    deskribapena TEXT NOT NULL,
    prezioa DECIMAL(10, 2) NOT NULL,
    irudia VARCHAR(255),
    saltzaile_id INT NOT NULL,
    kategoria_id INT NULL,
    artista VARCHAR(150) DEFAULT NULL,
    urtea SMALLINT DEFAULT NULL,
    salgai BOOLEAN DEFAULT TRUE,
    onarpen_egoera ENUM('pendentea', 'onartua', 'baztertua') DEFAULT 'onartua',
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (saltzaile_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (kategoria_id) REFERENCES kategoriak(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Taula: Eskaerak (Pedidos/Transacciones de compra)
CREATE TABLE eskaerak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    erosle_id INT NOT NULL,
    produktu_id INT NULL,
    totala DECIMAL(10, 2) NOT NULL,
    egoera ENUM('pendentea', 'ordaindua', 'bidalia', 'osatua', 'ezeztatua') DEFAULT 'pendentea',
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (erosle_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (produktu_id) REFERENCES produktuak(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Taula: Saskia (Carrito de compra temporal)
CREATE TABLE saskia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    erabiltzaile_id INT NOT NULL,
    produktu_id INT NOT NULL,
    kantitatea INT NOT NULL DEFAULT 1,
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unq_erabiltzaile_produktu (erabiltzaile_id, produktu_id),
    FOREIGN KEY (erabiltzaile_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (produktu_id) REFERENCES produktuak(id) ON DELETE CASCADE
) ENGINE=InnoDB;