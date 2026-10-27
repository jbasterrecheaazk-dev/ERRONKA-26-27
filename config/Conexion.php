<?php
class Conexion
{
    private string $host = 'localhost';
    private string $db   = 'SoinuTruk';
    private string $user = 'root';
    private string $pass = '';
    private ?PDO $pdo = null;

    public function getConexion(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        return $this->pdo;
    }
}
