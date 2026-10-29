<?php
class Documento {
    private $conn;

    public $id_documento;
    public $id_categoria;
    public $id_usuario_carga;
    public $titulo;
    public $url_archivo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listarNuevosHoy() {
        $query = '
            SELECT d.id_documento, d.titulo, d.fecha_carga,
                   c.nombre AS categoria,
                   u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            LEFT JOIN usuario u ON d.id_usuario_carga = u.id_usuario
            WHERE DATE(d.fecha_carga) = CURDATE()
            ORDER BY d.fecha_carga DESC
        ';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarRecientes($limit = 5) {
        $query = '
            SELECT d.id_documento, d.titulo, d.url_archivo, d.fecha_carga, c.nombre AS categoria
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            ORDER BY d.fecha_carga DESC
            LIMIT :limit
        ';
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listar() {
        $query = '
            SELECT d.id_documento, d.titulo, d.url_archivo, d.fecha_carga, d.id_categoria, c.nombre AS categoria
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            ORDER BY d.fecha_carga DESC
        ';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarCategorias() {
        $query = 'SELECT id_categoria, nombre FROM categoria_documento ORDER BY nombre ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarDocumentosPorCategoria($id_categoria) {
        $query = 'SELECT COUNT(*) as total FROM documento WHERE id_categoria = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_categoria, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function crear() {
        $query = 'INSERT INTO documento (id_categoria, id_usuario_carga, titulo, url_archivo) VALUES (:id_categoria, :id_usuario_carga, :titulo, :url_archivo)';
        $stmt = $this->conn->prepare($query);
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->url_archivo = preg_replace('/[^a-f0-9]/i', '', $this->url_archivo);
        $stmt->bindParam(':id_categoria', $this->id_categoria, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario_carga', $this->id_usuario_carga, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':url_archivo', $this->url_archivo);
        return $stmt->execute();
    }

    public function editar() {
        $query = 'UPDATE documento SET titulo = :titulo, id_categoria = :id_categoria WHERE id_documento = :id_documento';
        $stmt = $this->conn->prepare($query);
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':id_categoria', $this->id_categoria, PDO::PARAM_INT);
        $stmt->bindParam(':id_documento', $this->id_documento, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminar() {
        $query = 'DELETE FROM documento WHERE id_documento = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_documento, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function crearCategoria($nombre) {
        $query = 'INSERT INTO categoria_documento (nombre) VALUES (:nombre)';
        $stmt = $this->conn->prepare($query);
        $nombre = htmlspecialchars(strip_tags($nombre));
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }

    public function editarCategoria($id_categoria, $nombre) {
        $query = 'UPDATE categoria_documento SET nombre = :nombre WHERE id_categoria = :id';
        $stmt = $this->conn->prepare($query);
        $nombre = htmlspecialchars(strip_tags($nombre));
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':id', $id_categoria, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminarCategoria($id_categoria) {
        $query = 'DELETE FROM categoria_documento WHERE id_categoria = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_categoria, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerCategoriaPorId($id_categoria) {
        $query = 'SELECT id_categoria, nombre FROM categoria_documento WHERE id_categoria = :id LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_categoria, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}