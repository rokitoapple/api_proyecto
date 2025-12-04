<?php

require_once __DIR__ . '/../models/Favorito.php';
require_once __DIR__ . '/../models/Product.php';   // 👈 importar modelo de productos
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/User.php';

class FavoritoController {

    private $favoritoModel;
    private $userModel;
    private $productModel;

    public function __construct() {
        $this->favoritoModel = new Favorito();
        $this->userModel     = new User();
        $this->productModel  = new Product();   // 👈 inicializar modelo de productos
    }

    private function getAuthenticatedUser($token) {
        $usuario = $this->userModel->findByToken($token);
        if (!$usuario) {
            Response::json(['error' => 'Token inválido'], 401);
            exit;
        }
        return $usuario;
    }

    /** LISTAR FAVORITOS */
    public function obtener($token) {
        $usuario = $this->getAuthenticatedUser($token);
        $favoritos = $this->favoritoModel->obtenerTodos($usuario['id']);
        Response::json($favoritos, 200);
    }

    /** AGREGAR FAVORITO */
    public function agregar($token, $data) {
        $usuario = $this->getAuthenticatedUser($token);

        if (!isset($data['id_producto'])) {
            Response::json(['error' => 'id_producto es obligatorio'], 400);
            return;
        }

        $idProducto = (int)$data['id_producto'];

        // 👇 obtener producto y calcular precio con descuento
        $producto = $this->productModel->getById($idProducto);
        if (!$producto) {
            Response::json(['error' => 'Producto no encontrado'], 404);
            return;
        }

        $precioFinal = $producto['precio'];
        if (!empty($producto['descuento'])) {
            $precioFinal = $producto['precio'] - ($producto['precio'] * $producto['descuento'] / 100);
        }

        // 👇 guardar favorito con precio final
        $ok = $this->favoritoModel->agregar($usuario['id'], $idProducto, $precioFinal);

        $ok ? Response::json(['message' => 'Producto agregado a favoritos', 'precio_final' => $precioFinal], 200)
            : Response::json(['error' => 'No se pudo agregar'], 500);
    }

    public function eliminar($token, int $idProducto) {
        $usuario = $this->getAuthenticatedUser($token);
        $ok = $this->favoritoModel->eliminar($usuario['id'], $idProducto);
        $ok ? Response::json(['message' => 'Producto eliminado de favoritos'], 200)
            : Response::json(['error' => 'No se pudo eliminar'], 500);
    }

    public function verificar($token, int $idProducto) {
        $usuario = $this->getAuthenticatedUser($token);
        $es = $this->favoritoModel->esFavorito($usuario['id'], $idProducto);
        Response::json(['favorito' => $es], 200);
    }

    public function vaciar($token) {
        $usuario = $this->getAuthenticatedUser($token);
        $ok = $this->favoritoModel->vaciar($usuario['id']);
        $ok ? Response::json(['message' => 'Favoritos vaciados'], 200)
            : Response::json(['error' => 'No se pudo vaciar'], 500);
    }
}
