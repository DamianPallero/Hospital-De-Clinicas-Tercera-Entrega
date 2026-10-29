<?php
class Traslado {
    private $conn;

    public $id_traslado;
    public $id_conductor;
    public $id_enfermero;
    public $id_vehiculo;
    public $origen;
    public $destino;
    public $fecha_hora_salida;
    public $fecha_hora_llegada;
    public $estado_traslado;
    public $necesita_oxigeno;
    public $tipo_aislamiento;
    public $requiere_ascensor_escalera;
    public $observaciones;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listarTodos() {
        $query = '
            SELECT t.id_traslado, t.origen, t.destino, t.fecha_hora_salida, t.fecha_hora_llegada,
                   t.estado_traslado, t.necesita_oxigeno, t.tipo_aislamiento,
                   t.requiere_ascensor_escalera, t.observaciones,
                   c.nombre AS conductor_nombre, c.apellido AS conductor_apellido,
                   e.nombre AS enfermero_nombre, e.apellido AS enfermero_apellido,
                   v.patente, v.tipo AS vehiculo_tipo
            FROM traslado t
            LEFT JOIN conductor c ON t.id_conductor = c.id_conductor
            LEFT JOIN enfermero e ON t.id_enfermero = e.id_enfermero
            LEFT JOIN vehiculo v ON t.id_vehiculo = v.id_vehiculo
            ORDER BY t.fecha_hora_salida DESC
        ';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPendientes() {
        $query = '
            SELECT t.id_traslado, t.origen, t.destino, t.fecha_hora_salida,
                   t.estado_traslado, t.necesita_oxigeno, t.tipo_aislamiento,
                   t.requiere_ascensor_escalera, t.observaciones
            FROM traslado t
            WHERE t.estado_traslado = "Pendiente"
            ORDER BY t.fecha_hora_salida ASC
        ';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = '
            SELECT t.*, 
                   c.nombre AS conductor_nombre, c.apellido AS conductor_apellido,
                   e.nombre AS enfermero_nombre, e.apellido AS enfermero_apellido,
                   v.patente, v.tipo AS vehiculo_tipo
            FROM traslado t
            LEFT JOIN conductor c ON t.id_conductor = c.id_conductor
            LEFT JOIN enfermero e ON t.id_enfermero = e.id_enfermero
            LEFT JOIN vehiculo v ON t.id_vehiculo = v.id_vehiculo
            WHERE t.id_traslado = :id LIMIT 1
        ';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear() {
        $query = 'INSERT INTO traslado (origen, destino, fecha_hora_salida, estado_traslado, necesita_oxigeno, tipo_aislamiento, requiere_ascensor_escalera, observaciones) 
                  VALUES (:origen, :destino, :fecha_hora_salida, "Pendiente", :necesita_oxigeno, :tipo_aislamiento, :requiere_ascensor_escalera, :observaciones)';
        $stmt = $this->conn->prepare($query);

        $this->origen = htmlspecialchars(strip_tags($this->origen));
        $this->destino = htmlspecialchars(strip_tags($this->destino));
        $this->tipo_aislamiento = htmlspecialchars(strip_tags($this->tipo_aislamiento));
        $this->requiere_ascensor_escalera = htmlspecialchars(strip_tags($this->requiere_ascensor_escalera));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones ?? ''));

        $stmt->bindParam(':origen', $this->origen);
        $stmt->bindParam(':destino', $this->destino);
        $stmt->bindParam(':fecha_hora_salida', $this->fecha_hora_salida);
        $stmt->bindParam(':necesita_oxigeno', $this->necesita_oxigeno, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_aislamiento', $this->tipo_aislamiento);
        $stmt->bindParam(':requiere_ascensor_escalera', $this->requiere_ascensor_escalera);
        $stmt->bindParam(':observaciones', $this->observaciones);

        return $stmt->execute();
    }

    public function asignarRecursos() {
        $query = 'UPDATE traslado SET id_conductor = :id_conductor, id_enfermero = :id_enfermero, id_vehiculo = :id_vehiculo, estado_traslado = "Asignado" WHERE id_traslado = :id_traslado';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_conductor', $this->id_conductor, PDO::PARAM_INT);
        $stmt->bindParam(':id_enfermero', $this->id_enfermero, PDO::PARAM_INT);
        $stmt->bindParam(':id_vehiculo', $this->id_vehiculo, PDO::PARAM_INT);
        $stmt->bindParam(':id_traslado', $this->id_traslado, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function actualizarEstado($nuevo_estado) {
        $estados_validos = ['Pendiente', 'Asignado', 'En Ruta', 'Completado', 'Cancelado'];
        if (!in_array($nuevo_estado, $estados_validos, true)) {
            return false;
        }

        $query = 'UPDATE traslado SET estado_traslado = :estado';
        if ($nuevo_estado === 'Completado') {
            $query .= ', fecha_hora_llegada = NOW()';
        }
        $query .= ' WHERE id_traslado = :id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $this->id_traslado, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function listarConductores() {
        $stmt = $this->conn->prepare('SELECT id_conductor, nombre, apellido FROM conductor WHERE estado = "Activo" ORDER BY apellido');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarEnfermeros() {
        $stmt = $this->conn->prepare('SELECT id_enfermero, nombre, apellido FROM enfermero WHERE estado = "Activo" ORDER BY apellido');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarVehiculos() {
        $stmt = $this->conn->prepare('SELECT id_vehiculo, patente, tipo FROM vehiculo WHERE estado = "Disponible" ORDER BY tipo');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}