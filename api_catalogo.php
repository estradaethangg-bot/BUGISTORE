<?php
declare(strict_types=1);

/* API del catálogo: siempre responde JSON, incluso si ocurre un error PHP. */
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    require_once __DIR__ . '/catalogo_lib.php';

    if (!is_file(__DIR__ . '/catalogo.json') || !is_readable(__DIR__ . '/catalogo.json')) {
        throw new RuntimeException('catalogo.json no existe o el servidor no tiene permiso para leerlo.');
    }

    $productos = cargar_catalogo();
    usort($productos, static function ($a, $b) {
        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });

    $json = json_encode(
        array('ok' => true, 'productos' => $productos),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false) {
        throw new RuntimeException('No se pudo convertir el catálogo a JSON. Código: ' . json_last_error());
    }

    echo $json;
} catch (Throwable $e) {
    error_log('BUGISTORE api_catalogo.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'ok' => false,
        'mensaje' => 'PHP no pudo cargar el catálogo. Comprueba que Apache ejecute PHP, que catalogo.json exista y que tenga permisos de lectura.'
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
