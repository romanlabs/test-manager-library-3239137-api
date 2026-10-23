<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('/^\/api\/v1/', '', $path);

// Datos en memoria (para demo)
$categories = [
    ['id' => '1', 'name' => 'Novela'],
    ['id' => '2', 'name' => 'Infantil'],
    ['id' => '3', 'name' => 'Técnico'],
    ['id' => '4', 'name' => 'Poesía'],
    ['id' => '5', 'name' => 'Biografía'],
];

$books = [
    ['id' => 'b1', 'title' => 'Cien años de soledad', 'author' => 'García Márquez', 'isbn' => '9780307474728', 'category' => ['id' => '1', 'name' => 'Novela'], 'total_copies' => 4, 'available_copies' => 3, 'cover_key' => null],
    ['id' => 'b2', 'title' => 'El Quijote', 'author' => 'Cervantes', 'isbn' => '9788491110385', 'category' => ['id' => '1', 'name' => 'Novela'], 'total_copies' => 5, 'available_copies' => 5, 'cover_key' => null],
];

$members = [
    ['id' => 'm1', 'name' => 'Juan Pérez', 'document' => '12345678'],
    ['id' => 'm2', 'name' => 'María González', 'document' => '87654321'],
];

// Routes
if ($path === '/health' && $method === 'GET') {
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($path === '/auth/login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data['username'] === 'admin' && $data['password'] === 'admin') {
        echo json_encode([
            'access_token' => 'test_token_' . time(),
            'token_type' => 'Bearer',
            'expires_in' => 28800,
            'role' => 'admin'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'code' => 'INVALID_CREDENTIALS',
            'message' => 'Las credenciales no son válidas.',
            'details' => []
        ]);
    }
    exit;
}

if ($path === '/categories' && $method === 'GET') {
    echo json_encode([
        'items' => array_values(array_sort_by($categories, 'name'))
    ]);
    exit;
}

if ($path === '/books' && $method === 'GET') {
    $q = $_GET['q'] ?? '';
    $category_id = $_GET['category_id'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $size = min(50, max(1, (int)($_GET['size'] ?? 20)));

    $filtered = $books;

    if ($q) {
        $filtered = array_filter($filtered, function($b) use ($q) {
            return stripos($b['title'], $q) !== false || stripos($b['author'], $q) !== false;
        });
    }

    if ($category_id) {
        $filtered = array_filter($filtered, function($b) use ($category_id) {
            return $b['category']['id'] === $category_id;
        });
    }

    $total = count($filtered);
    $items = array_slice(array_values($filtered), ($page - 1) * $size, $size);

    echo json_encode([
        'items' => $items,
        'page' => $page,
        'size' => $size,
        'total' => $total
    ]);
    exit;
}

if (preg_match('/^\/books\/([a-z0-9]+)$/', $path, $matches) && $method === 'GET') {
    $id = $matches[1];
    $book = array_find($books, function($b) use ($id) { return $b['id'] === $id; });

    if (!$book) {
        http_response_code(404);
        echo json_encode(['code' => 'NOT_FOUND', 'message' => 'El libro no existe.', 'details' => []]);
    } else {
        echo json_encode($book);
    }
    exit;
}

if ($path === '/books' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validaciones básicas
    if (strlen($data['isbn']) !== 13) {
        http_response_code(422);
        echo json_encode(['code' => 'VALIDATION_ERROR', 'message' => 'ISBN debe tener 13 dígitos.', 'details' => []]);
        exit;
    }

    $newBook = [
        'id' => 'b' . uniqid(),
        'title' => $data['title'],
        'author' => $data['author'],
        'isbn' => $data['isbn'],
        'category' => array_find($categories, function($c) use ($data) { return $c['id'] === $data['category_id']; }),
        'total_copies' => $data['total_copies'],
        'available_copies' => $data['total_copies'],
        'cover_key' => null
    ];

    http_response_code(201);
    echo json_encode($newBook);
    exit;
}

if ($path === '/members' && $method === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $size = min(50, max(1, (int)($_GET['size'] ?? 20)));

    $total = count($members);
    $items = array_slice($members, ($page - 1) * $size, $size);

    echo json_encode([
        'items' => $items,
        'page' => $page,
        'size' => $size,
        'total' => $total
    ]);
    exit;
}

if ($path === '/members' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $newMember = [
        'id' => 'm' . uniqid(),
        'name' => $data['name'],
        'document' => $data['document']
    ];

    http_response_code(201);
    echo json_encode($newMember);
    exit;
}

// Helpers
function array_find($array, $callback) {
    foreach ($array as $item) {
        if ($callback($item)) return $item;
    }
    return null;
}

function array_sort_by(&$array, $key, $order = 'ASC') {
    usort($array, function($a, $b) use ($key, $order) {
        $cmp = strcmp($a[$key], $b[$key]);
        return $order === 'ASC' ? $cmp : -$cmp;
    });
    return $array;
}

// 404
http_response_code(404);
echo json_encode([
    'code' => 'NOT_FOUND',
    'message' => 'Endpoint no encontrado.',
    'details' => []
]);
