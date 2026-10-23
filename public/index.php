<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('/^\/api\/v1/', '', $path);

// Data store (en memoria para demo)
$db = [
    'categories' => [
        ['id' => '1', 'name' => 'Novela'],
        ['id' => '2', 'name' => 'Infantil'],
        ['id' => '3', 'name' => 'Técnico'],
        ['id' => '4', 'name' => 'Poesía'],
        ['id' => '5', 'name' => 'Biografía'],
    ],
    'books' => [
        ['id' => 'b1', 'title' => 'Cien años de soledad', 'author' => 'García Márquez', 'isbn' => '9780307474728', 'category' => ['id' => '1', 'name' => 'Novela'], 'total_copies' => 4, 'available_copies' => 3, 'cover_key' => null],
        ['id' => 'b2', 'title' => 'El Quijote', 'author' => 'Cervantes', 'isbn' => '9788491110385', 'category' => ['id' => '1', 'name' => 'Novela'], 'total_copies' => 5, 'available_copies' => 5, 'cover_key' => null],
    ],
    'members' => [
        ['id' => 'm1', 'name' => 'Juan Pérez', 'document' => '12345678'],
        ['id' => 'm2', 'name' => 'María González', 'document' => '87654321'],
    ],
    'loans' => [
        ['id' => 'l1', 'member_id' => 'm1', 'user_id' => 'u1', 'loaned_at' => '2026-10-15T10:00:00Z', 'items' => [
            ['id' => 'li1', 'book_id' => 'b1', 'book_title' => 'Cien años de soledad', 'isbn' => '9780307474728', 'category_name' => 'Novela', 'due_at' => '2026-10-29T10:00:00Z', 'returned_at' => null]
        ]],
    ],
];

// Routing
if ($path === '/health' && $method === 'GET') {
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($path === '/auth/login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data['username'] === 'admin' && $data['password'] === 'admin') {
        echo json_encode(['access_token' => 'test_token_' . time(), 'token_type' => 'Bearer', 'expires_in' => 28800, 'role' => 'admin']);
    } else {
        http_response_code(401);
        echo json_encode(['code' => 'INVALID_CREDENTIALS', 'message' => 'Las credenciales no son válidas.', 'details' => []]);
    }
    exit;
}

if ($path === '/users' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['role']) && $data['role'] === 'admin') {
        http_response_code(422);
        echo json_encode(['code' => 'VALIDATION_ERROR', 'message' => 'No se puede crear admin por API.', 'details' => []]);
        exit;
    }

    $username = strtolower(trim($data['username']));
    $userExists = current(array_filter($db['members'] ?? [], fn($u) => ($u['username'] ?? '') === $username));

    if ($userExists) {
        http_response_code(409);
        echo json_encode(['code' => 'USERNAME_TAKEN', 'message' => 'El usuario ya existe.', 'details' => []]);
        exit;
    }

    $new = [
        'id' => 'u'.uniqid(),
        'username' => $username,
        'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
        'role' => $data['role'] ?? 'librarian'
    ];

    http_response_code(201);
    echo json_encode([
        'id' => $new['id'],
        'username' => $new['username'],
        'role' => $new['role']
    ]);
    exit;
}

if ($path === '/categories' && $method === 'GET') {
    usort($db['categories'], fn($a, $b) => strcmp($a['name'], $b['name']));
    echo json_encode(['items' => $db['categories']]);
    exit;
}

if ($path === '/books' && $method === 'GET') {
    $q = $_GET['q'] ?? '';
    $cat = $_GET['category_id'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $size = min(50, max(1, (int)($_GET['size'] ?? 20)));

    $filtered = array_filter($db['books'], function($b) use ($q, $cat) {
        if ($q && stripos($b['title'], $q) === false && stripos($b['author'], $q) === false) return false;
        if ($cat && $b['category']['id'] !== $cat) return false;
        return true;
    });

    $total = count($filtered);
    $items = array_slice(array_values($filtered), ($page-1)*$size, $size);
    echo json_encode(['items' => $items, 'page' => $page, 'size' => $size, 'total' => $total]);
    exit;
}

if (preg_match('/^\/books\/([a-z0-9]+)$/', $path, $m) && $method === 'GET') {
    $book = current(array_filter($db['books'], fn($b) => $b['id'] === $m[1]));
    if (!$book) { http_response_code(404); echo json_encode(['code' => 'NOT_FOUND', 'message' => 'Libro no encontrado.', 'details' => []]); }
    else { echo json_encode($book); }
    exit;
}

if ($path === '/books' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (strlen($data['isbn']) !== 13) { http_response_code(422); echo json_encode(['code' => 'VALIDATION_ERROR', 'message' => 'ISBN inválido.', 'details' => []]); exit; }

    $cat = current(array_filter($db['categories'], fn($c) => $c['id'] === $data['category_id']));
    $new = ['id' => 'b'.uniqid(), 'title' => $data['title'], 'author' => $data['author'], 'isbn' => $data['isbn'], 'category' => $cat, 'total_copies' => $data['total_copies'], 'available_copies' => $data['total_copies'], 'cover_key' => null];
    http_response_code(201);
    echo json_encode($new);
    exit;
}

if ($path === '/members' && $method === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $size = min(50, max(1, (int)($_GET['size'] ?? 20)));
    $total = count($db['members']);
    $items = array_slice($db['members'], ($page-1)*$size, $size);
    echo json_encode(['items' => $items, 'page' => $page, 'size' => $size, 'total' => $total]);
    exit;
}

if ($path === '/members' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $new = ['id' => 'm'.uniqid(), 'name' => $data['name'], 'document' => $data['document']];
    http_response_code(201);
    echo json_encode($new);
    exit;
}

if ($path === '/loans' && $method === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $size = min(50, max(1, (int)($_GET['size'] ?? 20)));
    $total = count($db['loans']);
    $items = array_slice($db['loans'], ($page-1)*$size, $size);
    echo json_encode(['items' => $items, 'page' => $page, 'size' => $size, 'total' => $total]);
    exit;
}

if (preg_match('/^\/loans\/([a-z0-9]+)$/', $path, $m) && $method === 'GET') {
    $loan = current(array_filter($db['loans'], fn($l) => $l['id'] === $m[1]));
    if (!$loan) { http_response_code(404); echo json_encode(['code' => 'NOT_FOUND', 'message' => 'Préstamo no encontrado.', 'details' => []]); }
    else { echo json_encode($loan); }
    exit;
}

if ($path === '/loans' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $member = current(array_filter($db['members'], fn($m) => $m['id'] === $data['member_id']));
    if (!$member) { http_response_code(404); echo json_encode(['code' => 'NOT_FOUND', 'message' => 'Socio no encontrado.', 'details' => []]); exit; }

    $openCount = count(array_filter($db['loans'], function($l) use ($data) {
        if ($l['member_id'] !== $data['member_id']) return false;
        $ret = count(array_filter($l['items'], fn($i) => $i['returned_at'] !== null));
        return $ret < count($l['items']);
    }));

    if ($openCount >= 3) { http_response_code(422); echo json_encode(['code' => 'MEMBER_LIMIT_EXCEEDED', 'message' => 'Máximo 3 libros sin devolver.', 'details' => []]); exit; }

    $items = [];
    $ids = [];
    foreach ($data['items'] as $it) {
        if (in_array($it['book_id'], $ids)) { http_response_code(422); echo json_encode(['code' => 'DUPLICATE_BOOK_IN_LOAN', 'message' => 'Libro duplicado.', 'details' => []]); exit; }
        $book = current(array_filter($db['books'], fn($b) => $b['id'] === $it['book_id']));
        if (!$book || $book['available_copies'] <= 0) { http_response_code(409); echo json_encode(['code' => 'BOOK_NOT_AVAILABLE', 'message' => 'Sin ejemplares.', 'details' => []]); exit; }

        $d = new DateTime('now', new DateTimeZone('UTC'));
        $d->modify('+14 days');
        $items[] = ['id' => 'li'.uniqid(), 'book_id' => $book['id'], 'book_title' => $book['title'], 'isbn' => $book['isbn'], 'category_name' => $book['category']['name'], 'due_at' => $d->format('Y-m-d\TH:i:s\Z'), 'returned_at' => null];
        $ids[] = $it['book_id'];
    }

    $new = ['id' => 'l'.uniqid(), 'member_id' => $data['member_id'], 'user_id' => 'u1', 'loaned_at' => date('Y-m-d\TH:i:s\Z'), 'items' => $items];
    $db['loans'][] = $new;
    http_response_code(201);
    echo json_encode($new);
    exit;
}

if (preg_match('/^\/loans\/([a-z0-9]+)\/items\/([a-z0-9]+)\/return$/', $path, $m) && $method === 'POST') {
    $loan = current(array_filter($db['loans'], fn($l) => $l['id'] === $m[1]));
    if (!$loan) { http_response_code(404); echo json_encode(['code' => 'NOT_FOUND', 'message' => 'Préstamo no encontrado.', 'details' => []]); exit; }

    $item = current(array_filter($loan['items'], fn($i) => $i['id'] === $m[2]));
    if (!$item) { http_response_code(404); echo json_encode(['code' => 'NOT_FOUND', 'message' => 'Línea no encontrada.', 'details' => []]); exit; }
    if ($item['returned_at']) { http_response_code(409); echo json_encode(['code' => 'ITEM_ALREADY_RETURNED', 'message' => 'Ya devuelto.', 'details' => []]); exit; }

    $item['returned_at'] = date('Y-m-d\TH:i:s\Z');
    echo json_encode(['message' => 'Devolución registrada.']);
    exit;
}

if ($path === '/reports/most-lent' && $method === 'GET') {
    $start = $_GET['start_date'] ?? '';
    $end = $_GET['end_date'] ?? '';

    if (!$start || !$end) {
        http_response_code(422);
        echo json_encode(['code' => 'VALIDATION_ERROR', 'message' => 'Fechas requeridas.', 'details' => []]);
        exit;
    }

    $startDate = new DateTime($start, new DateTimeZone('UTC'));
    $endDate = new DateTime($end, new DateTimeZone('UTC'));

    if ($endDate < $startDate) {
        http_response_code(422);
        echo json_encode(['code' => 'INVALID_DATE_RANGE', 'message' => 'Fin anterior al inicio.', 'details' => []]);
        exit;
    }

    $report = [];
    foreach ($db['loans'] as $loan) {
        $loanDate = new DateTime($loan['loaned_at'], new DateTimeZone('UTC'));
        if ($loanDate >= $startDate && $loanDate <= $endDate) {
            foreach ($loan['items'] as $item) {
                $key = $item['book_title'] . '|' . $item['category_name'];
                if (!isset($report[$key])) {
                    $report[$key] = ['book_title' => $item['book_title'], 'category_name' => $item['category_name'], 'count' => 0];
                }
                $report[$key]['count']++;
            }
        }
    }

    usort($report, fn($a, $b) => $b['count'] <=> $a['count']);
    echo json_encode(['items' => array_values($report)]);
    exit;
}

if ($path === '/overdue-items' && $method === 'GET') {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $overdue = [];

    foreach ($db['loans'] as $loan) {
        $member = current(array_filter($db['members'], fn($m) => $m['id'] === $loan['member_id']));
        foreach ($loan['items'] as $item) {
            if ($item['returned_at']) continue;
            $dueDate = new DateTime($item['due_at'], new DateTimeZone('UTC'));
            if ($dueDate < $now) {
                $daysLate = $now->diff($dueDate)->days;
                $overdue[] = [
                    'book_title' => $item['book_title'],
                    'member_name' => $member['name'],
                    'due_at' => $item['due_at'],
                    'days_late' => $daysLate
                ];
            }
        }
    }

    usort($overdue, fn($a, $b) => $b['days_late'] <=> $a['days_late']);
    echo json_encode(['items' => $overdue]);
    exit;
}

http_response_code(404);
echo json_encode(['code' => 'NOT_FOUND', 'message' => 'Endpoint no encontrado.', 'details' => []]);
