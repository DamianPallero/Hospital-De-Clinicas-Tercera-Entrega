<?php
class Encuesta {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listarActivas() {
        $stmt = $this->conn->prepare('SELECT `id_encuesta`, `titulo` FROM `encuesta` WHERE `estado` = 1 ORDER BY `titulo` ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarTodas() {
        $stmt = $this->conn->prepare('SELECT `id_encuesta`, `titulo`, `estado` FROM `encuesta` ORDER BY `estado` DESC, `titulo` ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare('SELECT `id_encuesta`, `titulo`, `estado` FROM `encuesta` WHERE `id_encuesta` = :id LIMIT 1');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function crear($titulo) {
        $stmt = $this->conn->prepare('INSERT INTO `encuesta` (`titulo`) VALUES (:titulo)');
        $stmt->bindParam(':titulo', $titulo);
        return $stmt->execute();
    }

    public function toggleEstado($id) {
        $stmt = $this->conn->prepare('UPDATE `encuesta` SET `estado` = IF(`estado` = 1, 0, 1) WHERE `id_encuesta` = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerPreguntas($id_encuesta) {
        $stmt = $this->conn->prepare('SELECT `id_pregunta`, `texto_pregunta`, `tipo_respuesta` FROM `pregunta_encuesta` WHERE `id_encuesta` = :id ORDER BY `id_pregunta` ASC');
        $stmt->bindParam(':id', $id_encuesta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function agregarPregunta($id_encuesta, $texto, $tipo) {
        $stmt = $this->conn->prepare('INSERT INTO `pregunta_encuesta` (`id_encuesta`, `texto_pregunta`, `tipo_respuesta`) VALUES (:id_encuesta, :texto, :tipo)');
        $stmt->bindParam(':id_encuesta', $id_encuesta, PDO::PARAM_INT);
        $stmt->bindParam(':texto', $texto);
        $stmt->bindParam(':tipo', $tipo);
        return $stmt->execute();
    }

    public function guardarRespuestas($respuestas) {
        $stmt = $this->conn->prepare('INSERT INTO `respuesta_encuesta` (`id_pregunta`, `respuesta_texto`) VALUES (:id_pregunta, :respuesta_texto)');
        foreach ($respuestas as $id_pregunta => $valor) {
            $stmt->bindValue(':id_pregunta', (int)$id_pregunta, PDO::PARAM_INT);
            $stmt->bindValue(':respuesta_texto', $valor, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    public function obtenerResumenRespuestas() {
        $query = 'SELECT `e`.`titulo` AS `encuesta_titulo`, `p`.`id_pregunta`, `p`.`texto_pregunta`, `p`.`tipo_respuesta`, `r`.`respuesta_texto`, COUNT(*) as `total` ' .
                 'FROM `respuesta_encuesta` `r` ' .
                 'INNER JOIN `pregunta_encuesta` `p` ON `r`.`id_pregunta` = `p`.`id_pregunta` ' .
                 'INNER JOIN `encuesta` `e` ON `p`.`id_encuesta` = `e`.`id_encuesta` ' .
                 'GROUP BY `e`.`titulo`, `p`.`id_pregunta`, `p`.`texto_pregunta`, `p`.`tipo_respuesta`, `r`.`respuesta_texto` ' .
                 'ORDER BY `e`.`titulo` ASC, `p`.`id_pregunta` ASC';
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return []; 
        }
    }
}