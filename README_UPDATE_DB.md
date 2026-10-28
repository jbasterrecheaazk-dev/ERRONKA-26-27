# Actualización de la base de datos

Para resolver el error "Column not found: 1054 Unknown column 'artista' in 'field list'", es necesario actualizar la estructura de la tabla `produktuak` en la base de datos.

Ejecute el siguiente script SQL en su base de datos MySQL (por ejemplo, usando phpMyAdmin, MySQL Workbench o la línea de comandos):

```sql
-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS SoinuTruk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE SoinuTruk;

-- 1. Tabla: Erabiltzaileak (Usuarios)
CREATE TABLE IF NOT EXISTS erabiltzaileak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izena VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    pasahitza VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'erabiltzailea') DEFAULT 'erabiltzailea',
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla: Kategoriak (Categorías)
CREATE TABLE IF NOT EXISTS kategoriak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    izena VARCHAR(50) NOT NULL UNIQUE
);

-- Insertar categorías por defecto (si no existen)
INSERT IGNORE INTO kategoriak (izena) VALUES
('Biniloak'),
('CDak'),
('Kasetak'),
('Instrumentu musikala'),
('Merchandising'),
('Sondu ekipamendua'),
('Bestekoa');

-- 3. Tabla: Produktuak (Artículos a la venta)
-- Primero verificamos si la tabla existe y tiene la columna artista
-- Si no, la añadimos. Esta aproximación es segura para ejecutar múltiples veces.
SET @db_name = 'SoinuTruk';
SET @table_name = 'produktuak';
SET @column_name = 'artista';
SET @column_def = 'VARCHAR(150) DEFAULT NULL';

SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name 
  AND TABLE_NAME = @table_name 
  AND COLUMN_NAME = @column_name;

IF @col_exists = 0 THEN
    ALTER TABLE produktuak ADD COLUMN artista VARCHAR(150) DEFAULT NULL AFTER kategoria_id;
END IF;

-- También aseguramos que las columnas urtea y egokia existan (por si acaso)
SET @column_name2 = 'urtea';
SET @column_def2 = 'SMALLINT DEFAULT NULL';
SELECT COUNT(*) INTO @col_exists2 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name 
  AND TABLE_NAME = @table_name 
  AND COLUMN_NAME = @column_name2;
IF @col_exists2 = 0 THEN
    ALTER TABLE produktuak ADD COLUMN urtea SMALLINT DEFAULT NULL AFTER artista;
END IF;

SET @column_name3 = 'egoera_egokia';
SET @column_def3 = 'ENUM(\'Mint\',\'Near Mint\',\'Good\',\'Fair\',\'Poor\') DEFAULT NULL';
SELECT COUNT(*) INTO @col_exists3 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @db_name 
  AND TABLE_NAME = @table_name 
  AND COLUMN_NAME = @column_name3;
IF @col_exists3 = 0 THEN
    ALTER TABLE produktuak ADD COLUMN egoera_egokia ENUM('Mint','Near Mint','Good','Fair','Poor') DEFAULT NULL AFTER egoera;
END IF;

-- 4. Tabla: Eskaerak (Pedidos/Transacciones)
CREATE TABLE IF NOT EXISTS eskaera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    erosle_id INT NOT NULL,
    produktu_id INT NOT NULL,
    totala DECIMAL(10, 2) NOT NULL,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (erosle_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (produktu_id) REFERENCES produktuak(id) ON DELETE CASCADE
);

-- 5. Tabla: Carrito (Items en el carrito de compra temporal)
CREATE TABLE IF NOT EXISTS carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    erabiltzaile_id INT NOT NULL,
    produktu_id INT NOT NULL,
    kantitatea INT NOT NULL DEFAULT 1,
    sortze_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (erabiltzaile_id) REFERENCES erabiltzaileak(id) ON DELETE CASCADE,
    FOREIGN KEY (produktu_id) REFERENCES produktuak(id) ON DELETE CASCADE
);
```

**Nota:** Este script utiliza sentencias condicionales para añadir columnas solo si no existen, por lo que es seguro ejecutarlo múltiples veces sin perder datos existentes.

Después de ejecutar este script, vuelva a intentar publicar un artículo desde el formulario.