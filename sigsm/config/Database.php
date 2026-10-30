<?php
class Database {
    private $servidor;
    private $nombreBd;
    private $nomCuenta;
    private $claveAcceso;
    public $conexion;

    public function __construct() {
        $this->servidor = getenv('DB_HOST') ?: 'db';
        $this->nombreBd = getenv('DB_NAME') ?: 'sigsm';
        $this->nomCuenta = getenv('DB_USER') ?: 'sigsm_user';
        $this->claveAcceso = getenv('DB_PASS') ?: 'sigsm_secure_password';
    }

    public function getConnection() {
        $this->conexion = null;
        try {
            $this->conexion = new PDO(
                "mysql:host=" . $this->servidor . ";dbname=" . $this->nombreBd . ";charset=utf8mb4",
                $this->nomCuenta,
                $this->claveAcceso
            );
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            $this->conexion->exec("SET NAMES utf8mb4");
            $this->conexion->exec("SET CHARACTER SET utf8mb4");
        } catch(PDOException $excepcion) {
            die("Error de conexión a la base de datos.");
        }
        return $this->conexion;
    }
}