<?php
// Configuración de la base de datos PostgreSQL
class Database {
    private $host = 'localhost';
    private $db_name = 'cartelera';
    private $username = 'postgres';
    private $password = 'C4n4r14s**'; // Cambiar por tu contraseña
    private $port = '5432';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>