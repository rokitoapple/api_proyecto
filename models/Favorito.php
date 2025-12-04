<?php
require_once __DIR__ . '/../config/database.php';

class Favorito {
    public $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    /** Agregar favorito con precio final */
    public function agregar(int $idUsuario, int $idProducto, float $precioFinal): bool {
        if (!$idUsuario || !$idProducto) {
            error_log("FAVORITOS ERROR: parámetros inválidos");
            return false;
        }

        $sql = "INSERT INTO favoritos (id_usuario, id_producto, precio_final)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE precio_final = VALUES(precio_final)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute([$idUsuario, $idProducto, $precioFinal])) {
            error_log("FAVORITOS ERROR INSERT: " . print_r($stmt->errorInfo(), true));
            return false;
        }
        return true;
    }

    /** Eliminar favorito */
    public function eliminar(int $idUsuario, int $idProducto): bool {
        $sql = "DELETE FROM favoritos WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idUsuario, $idProducto]);
    }

    /** Obtener todos los favoritos */
    public function obtenerTodos(int $idUsuario): array {
        $sql = "SELECT p.*, f.precio_final
                FROM products p
                INNER JOIN favoritos f ON f.id_producto = p.id
                WHERE f.id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idUsuario]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    /** Verificar si un producto ya es favorito */
    public function esFavorito(int $idUsuario, int $idProducto): bool {
        $sql = "SELECT COUNT(*) FROM favoritos WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idUsuario, $idProducto]);
        return $stmt->fetchColumn() > 0;
    }

    /** Vaciar todos los favoritos de un usuario */
    public function vaciar(int $idUsuario): bool {
        $sql = "DELETE FROM favoritos WHERE id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idUsuario]);
    }
}
