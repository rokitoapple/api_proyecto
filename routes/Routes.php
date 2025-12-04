<?php
require_once __DIR__ . '/../utils/Response.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ==========================
   CORS
========================== */
$allowed = ['http://localhost:4200'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

/* Aseguramos que la respuesta OPTIONS devuelve headers JSON y no HTML */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

/* ==========================
   HELPERS
========================== */
function readBody() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

function getBearerToken() {
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (!empty($headers['Authorization']) &&
            preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $m))
            return $m[1];
    }
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) &&
        preg_match('/Bearer\s+(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $m))
        return $m[1];
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) &&
        preg_match('/Bearer\s+(\S+)/', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $m))
        return $m[1];
    return null;
}

/* ==========================
   CONTROLADORES
========================== */
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/CarritoController.php';
require_once __DIR__ . '/../controllers/CompraController.php';
require_once __DIR__ . '/../controllers/TicketController.php';
require_once __DIR__ . '/../controllers/FavoritoController.php';

$userController     = new UserController();
$productController  = new ProductController();
$carritoController  = new CarritoController();
$compraController   = new CompraController();
$ticketController   = new TicketController();
$favoritoController = new FavoritoController();

/* ==========================
   URI + METHOD
========================== */
$uri = $_SERVER['REQUEST_URI'];
$uri = str_replace('/api_proyecto/public', '', $uri);
$uri = strtok($uri, '?');
$uri = rtrim($uri, '/');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

/* ==========================
   RUTAS
========================== */
switch (true) {

    /* USERS */
    case $uri === '/users/login' && $method === 'POST':
        $userController->login(readBody());
        break;

    case $uri === '/users/register' && $method === 'POST':
        $userController->register(readBody());
        break;

    case $uri === '/users' && $method === 'GET':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $userController->getAll($token);
        break;

    /* PRODUCTS */
    case $uri === '/products' && $method === 'GET':
        $productController->getAll();
        break;

    case $uri === '/ofertas' && $method === 'GET':
        $productController->getOfertas();
        break;

    case $uri === '/products' && $method === 'POST':
        $productController->create();
        break;

    case preg_match('#^/products/(\d+)$#', $uri, $m) && $method === 'GET':
        $productController->getById((int)$m[1]);
        break;

    case preg_match('#^/products/(\d+)$#', $uri, $m) && $method === 'PUT':
        $productController->update((int)$m[1], readBody());
        break;

    case preg_match('#^/products/(\d+)$#', $uri, $m) && $method === 'DELETE':
        $productController->delete((int)$m[1]);
        break;

    /* CARRITO */
    case $uri === '/carrito' && $method === 'GET':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $carritoController->obtener($token);
        break;

    case $uri === '/carrito/agregar' && $method === 'POST':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $carritoController->agregar($token, readBody());
        break;

    case preg_match('#^/carrito/actualizar/(\d+)$#', $uri, $m) && $method === 'PUT':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $carritoController->actualizar($token, (int)$m[1], readBody());
        break;

    case preg_match('#^/carrito/eliminar/(\d+)$#', $uri, $m) && $method === 'DELETE':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $carritoController->eliminar($token, (int)$m[1]);
        break;

    case $uri === '/carrito/vaciar' && $method === 'DELETE':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $carritoController->vaciar($token);
        break;

    /* COMPRAS */
    case $uri === '/compras/finalizar' && $method === 'POST':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $compraController->finalizarCompra($token);
        break;

    case $uri === '/compras' && $method === 'GET':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $compraController->listarCompras($token);
        break;

    /* TICKETS */
    case preg_match('#^/ticket/compra/(\d+)$#', $uri, $m) && $method === 'GET':
        $ticketController->obtenerPorCompra((int)$m[1]);
        break;

    case preg_match('#^/ticket/([A-Za-z0-9\-]+)$#', $uri, $m) && $method === 'GET':
        $ticketController->descargar($m[1]);
        break;

    /* FAVORITOS */
    case $uri === '/favoritos' && $method === 'GET':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $favoritoController->obtener($token);
        break;

    case $uri === '/favoritos/agregar' && $method === 'POST':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $favoritoController->agregar($token, readBody());
        break;

    case preg_match('#^/favoritos/eliminar/(\d+)$#', $uri, $m) && $method === 'DELETE':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $favoritoController->eliminar($token, (int)$m[1]);
        break;

    case preg_match('#^/favoritos/verificar/(\d+)$#', $uri, $m) && $method === 'GET':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $favoritoController->verificar($token, (int)$m[1]);
        break;

    case $uri === '/favoritos/vaciar' && $method === 'DELETE':
        $token = getBearerToken();
        if (!$token) { Response::json(["error" => "Token faltante"], 401); return; }
        $favoritoController->vaciar($token);
        break;

    /* 404 */
    default:
        Response::json(["error" => "Ruta no encontrada", "ruta" => $uri, "method" => $method], 404);
        break;
}
