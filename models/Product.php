<?php
require_once __DIR__ . '/../core/Model.php';

class Product extends Model {

    public function getAll() {
        $stmt = $this->db->query("
            SELECT id, nombre, descripcion, precio, imagen, stock, descuento
            FROM products
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT id, nombre, descripcion, precio, imagen, stock, descuento
            FROM products
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * SOLO productos con descuento > 0
     */
    public function getOfertas() {
        $stmt = $this->db->query("
            SELECT id, nombre, descripcion, precio, imagen, stock, descuento
            FROM products
            WHERE descuento > 0
              AND stock > 0
            ORDER BY id DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($nombre, $descripcion, $precio, $imagen, $stock, $descuento = 0) {
        $stmt = $this->db->prepare("
            INSERT INTO products (nombre, descripcion, precio, imagen, stock, descuento)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$nombre, $descripcion, $precio, $imagen, $stock, $descuento]);
    }

    public function update($id, $nombre, $descripcion, $precio, $imagen, $stock, $descuento = 0) {
        $stmt = $this->db->prepare("
            UPDATE products
            SET nombre=?, descripcion=?, precio=?, imagen=?, stock=?, descuento=?
            WHERE id=?
        ");
        return $stmt->execute([$nombre, $descripcion, $precio, $imagen, $stock, $descuento, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id=?");
        return $stmt->execute([$id]);
    }
}

?>
