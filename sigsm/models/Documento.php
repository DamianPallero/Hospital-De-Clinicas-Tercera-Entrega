<?php
class Documento {
    private $conexion;

    public $docId;
    public $catId;
    public $cargaId;
    public $tituloDoc;
    public $urlArchivo;

    public function __construct($bd) {
        $this->conexion = $bd;
    }

    public function listarNuevosHoy() {
        $sql = '
            SELECT d.id_documento, d.titulo, d.fecha_carga,
                   c.nombre AS categoria,
                   u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            LEFT JOIN usuario u ON d.id_usuario_carga = u.id_usuario
            WHERE DATE(d.fecha_carga) = CURDATE()
            ORDER BY d.fecha_carga DESC
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function listarRecientes($tope = 5) {
        $sql = '
            SELECT d.id_documento, d.titulo, d.url_archivo, d.fecha_carga, c.nombre AS categoria
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            ORDER BY d.fecha_carga DESC
            LIMIT :limit
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->bindValue(':limit', (int)$tope, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function listar() {
        $sql = '
            SELECT d.id_documento, d.titulo, d.url_archivo, d.fecha_carga, d.id_categoria, c.nombre AS categoria
            FROM documento d
            LEFT JOIN categoria_documento c ON d.id_categoria = c.id_categoria
            ORDER BY d.fecha_carga DESC
        ';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function listarCategorias() {
        $sql = 'SELECT id_categoria, nombre FROM categoria_documento ORDER BY nombre ASC';
        $prep = $this->conexion->prepare($sql);
        $prep->execute();
        return $prep->fetchAll();
    }

    public function contarDocumentosPorCategoria($catId) {
        $sql = 'SELECT COUNT(*) as total FROM documento WHERE id_categoria = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        $prep->execute();
        $salida = $prep->fetch(PDO::FETCH_ASSOC);
        return $salida['total'] ?? 0;
    }

    public function crear() {
        $sql = 'INSERT INTO documento (id_categoria, id_usuario_carga, titulo, url_archivo, fecha_carga) VALUES (:id_categoria, :id_usuario_carga, :titulo, :url_archivo, NOW())';
        $prep = $this->conexion->prepare($sql);
        $this->tituloDoc = htmlspecialchars(strip_tags($this->tituloDoc));
        $this->urlArchivo = preg_replace('/[^a-f0-9]/i', '', $this->urlArchivo);
        $prep->bindParam(':id_categoria', $this->catId, PDO::PARAM_INT);
        $prep->bindParam(':id_usuario_carga', $this->cargaId, PDO::PARAM_INT);
        $prep->bindParam(':titulo', $this->tituloDoc);
        $prep->bindParam(':url_archivo', $this->urlArchivo);
        return $prep->execute();
    }

    public function editar() {
        $sql = 'UPDATE documento SET titulo = :titulo, id_categoria = :id_categoria WHERE id_documento = :id_documento';
        $prep = $this->conexion->prepare($sql);
        $this->tituloDoc = htmlspecialchars(strip_tags($this->tituloDoc));
        $prep->bindParam(':titulo', $this->tituloDoc);
        $prep->bindParam(':id_categoria', $this->catId, PDO::PARAM_INT);
        $prep->bindParam(':id_documento', $this->docId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function eliminar() {
        $sql = 'DELETE FROM documento WHERE id_documento = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $this->docId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function crearCategoria($nom) {
        $sql = 'INSERT INTO categoria_documento (nombre) VALUES (:nombre)';
        $prep = $this->conexion->prepare($sql);
        $nom = htmlspecialchars(strip_tags($nom));
        $prep->bindParam(':nombre', $nom);
        return $prep->execute();
    }

    public function editarCategoria($catId, $nom) {
        $sql = 'UPDATE categoria_documento SET nombre = :nombre WHERE id_categoria = :id';
        $prep = $this->conexion->prepare($sql);
        $nom = htmlspecialchars(strip_tags($nom));
        $prep->bindParam(':nombre', $nom);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function eliminarCategoria($catId) {
        $sql = 'DELETE FROM categoria_documento WHERE id_categoria = :id';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        return $prep->execute();
    }

    public function obtenerCategoriaPorId($catId) {
        $sql = 'SELECT id_categoria, nombre FROM categoria_documento WHERE id_categoria = :id LIMIT 1';
        $prep = $this->conexion->prepare($sql);
        $prep->bindParam(':id', $catId, PDO::PARAM_INT);
        $prep->execute();
        return $prep->fetch(PDO::FETCH_ASSOC);
    }
}