<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
if (!isset($cn) || $cn->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar con la base de datos.']);
    exit;
}
$id = filter_var($_POST['clave'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(422);
    echo json_encode(['error' => 'Clave de videojuego inválida.']);
    $cn->close();
    exit;
}
$stmt = $cn->prepare('SELECT * FROM `videojuegos` WHERE `videojuego_id` = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo preparar la consulta.']);
    $cn->close();
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_row() : null;
if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontró el videojuego.']);
} else {
    echo json_encode(['precio' => (float)($row[3] ?? 0)]);
}
$stmt->close();
$cn->close();
