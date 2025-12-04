<?php

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';

class ProductController {

    private $productModel;
    private $userModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->userModel    = new User();
    }

    private function getAuthUser() {

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $token = null;

        if (!empty($headers['Authorization']) &&
            preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $m)) {
            $token = $m[1];
        }

        if (!$token && !empty($_SERVER['HTTP_AUTHORIZATION']) &&
            preg_match('/Bearer\s+(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
            $token = $m[1];
        }

        if (!$token) return false;

        return $this->userModel->findByToken($token);
    }

    private function requireAdmin() {
        $user = $this->getAuthUser();

        if (!$user || $user['rol'] !== 'admin') {
            Response::json(['mensaje' => 'No autorizado'], 403);
            exit;
        }

        return $user;
    }

    public function getAll() {
        $products = $this->productModel->getAll();
        Response::json($products);
    }

    public function getById($id) {
        $product = $this->productModel->getById($id);
        Response::json($product ?: ['mensaje' => 'No encontrado'], $product ? 200 : 404);
    }

    public function getOfertas() {
        $ofertas = $this->productModel->getOfertas();
        Response::json($ofertas);
    }

    public function create() {

        $this->requireAdmin();

        $nombre      = $_POST['nombre']      ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $precio      = $_POST['precio']      ?? 0;
        $stock       = $_POST['stock']       ?? 0;
        $descuento   = $_POST['descuento']   ?? 0;

        $imagenNombre = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {

            $directorio = __DIR__ . '/../public/uploads/';
            if (!is_dir($directorio)) mkdir($directorio, 0777, true);

            $imagenNombre = uniqid() . '_' . basename($_FILES['imagen']['name']);
            $rutaDestino = $directorio . $imagenNombre;

            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                return Response::json(['error' => 'Error al guardar la imagen'], 500);
            }
        }

        $ok = $this->productModel->create(
            $nombre,
            $descripcion,
            $precio,
            $imagenNombre,
            $stock,
            $descuento
        );

        Response::json(['success' => $ok], $ok ? 201 : 500);
    }

    public function update($id) {

        $this->requireAdmin();

        $productoActual = $this->productModel->getById($id);

        if (!$productoActual) {
            return Response::json(['error' => 'Producto no encontrado'], 404);
        }

        $data = $_POST;
        if (empty($data)) {
            parse_str(file_get_contents("php://input"), $data);
        }

        $nombre      = $data['nombre']      ?? $productoActual['nombre'];
        $descripcion = $data['descripcion'] ?? $productoActual['descripcion'];
        $precio      = $data['precio']      ?? $productoActual['precio'];
        $stock       = $data['stock']       ?? $productoActual['stock'];
        $descuento   = $data['descuento']   ?? $productoActual['descuento'];

        $imagenNombre = $productoActual['imagen'];

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {

            $directorio = __DIR__ . '/../public/uploads/';
            if (!is_dir($directorio)) mkdir($directorio, 0777, true);

            $imagenNombre = uniqid() . '_' . $_FILES['imagen']['name'];
            move_uploaded_file($_FILES['imagen']['tmp_name'], $directorio . $imagenNombre);
        }

        $ok = $this->productModel->update(
            $id,
            $nombre,
            $descripcion,
            $precio,
            $imagenNombre,
            $stock,
            $descuento
        );

        Response::json(['success' => $ok], $ok ? 200 : 500);
    }

    public function delete($id) {

        $this->requireAdmin();

        $ok = $this->productModel->delete($id);

        Response::json(['success' => $ok], $ok ? 200 : 500);
    }
}

?>
