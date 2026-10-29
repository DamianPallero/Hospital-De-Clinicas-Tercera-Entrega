<?php
class Usuario {
    private $conn;

    public $id_usuario;
    public $id_rol;
    public $nombre;
    public $apellido;
    public $email;
    public $password_hash;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = 'SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.activo, r.id_rol, r.nombre_rol 
                  FROM usuario u 
                  INNER JOIN rol r ON u.id_rol = r.id_rol 
                  ORDER BY u.apellido ASC, u.nombre ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = 'SELECT id_usuario, id_rol, nombre, apellido, email, activo FROM usuario WHERE id_usuario = :id LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarRoles() {
        $query = 'SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear() {
        $query = 'INSERT INTO usuario (id_rol, nombre, apellido, email, password_hash) VALUES (:id_rol, :nombre, :apellido, :email, :password_hash)';
        $stmt = $this->conn->prepare($query);
        
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        
        $stmt->bindParam(':id_rol', $this->id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        
        return $stmt->execute();
    }

    public function editar() {
        $query = 'UPDATE usuario SET id_rol = :id_rol, nombre = :nombre, apellido = :apellido, email = :email WHERE id_usuario = :id_usuario';
        $stmt = $this->conn->prepare($query);
        
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        
        $stmt->bindParam(':id_rol', $this->id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function toggleEstado() {
        $query = 'UPDATE usuario SET activo = IF(activo = 1, 0, 1) WHERE id_usuario = :id_usuario';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function cambiarPassword($nuevaPassword) {
        $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $query = 'UPDATE usuario SET password_hash = :hash WHERE id_usuario = :id_usuario';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }
}