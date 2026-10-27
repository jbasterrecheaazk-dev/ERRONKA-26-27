<?php
require_once __DIR__ . '/../config/Conexion.php';

class ErabiltzaileaModel
{
    private PDO $db;

    public function __construct()
    {
        $conexion = new Conexion();
        $this->db = $conexion->getConexion();
    }

    public function registrar(array $datos): bool
    {
        $sql = "INSERT INTO erabiltzaileak (izena, email, pasahitza, rol) VALUES (:izena, :email, :pasahitza, :rol)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':izena'     => $datos['izena'],
            ':email'     => $datos['email'],
            ':pasahitza' => $datos['pasahitza'],
            ':rol'       => $datos['rol'] ?? 'erabiltzailea',
        ]);
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM erabiltzaileak WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }
}
