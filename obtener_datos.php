<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
if (!isset($cn) || $cn->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar con la base de datos.']);
    exit;
}
$result = $cn->query('SELECT * FROM `videojuegos`');
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la tabla videojuegos.']);
    $cn->close();
    exit;
}
$datos = [];
while ($row = $result->fetch_row()) {
    $datos[] = ['id' => $row[0] ?? null, 'nombre' => $row[1] ?? '', 'precio' => $row[3] ?? 0];
}
$result->free();
$cn->close();
// Mantiene el formato que usaba fragment.js anteriormente.
$legacy = ['tam' => count($datos)];
foreach ($datos as $index => $item) {
    $n = $index + 1;
    $legacy['clave' . $n] = $item['id'];
    $legacy['modelo' . $n] = $item['nombre'];
}
echo json_encode([$legacy], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
