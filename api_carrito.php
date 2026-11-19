<?php
declare(strict_types=1);

require_once __DIR__ . '/catalogo_lib.php';
require_once __DIR__ . '/config.php';
date_default_timezone_set('America/Mexico_City');

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    // Compatibilidad con PHP 7.0–7.2; estas versiones no admiten la opción SameSite en un array.
    session_set_cookie_params(0, '/', '', $isHttps, true);
}
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function longitud_texto(string $texto): int {
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function respuesta(array $data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function entrada(): array {
    $raw = file_get_contents('php://input');
    if ($raw !== false && trim($raw) !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) return $json;
    }
    return $_POST;
}

function resumen_carrito(array $catalogo): array {
    $map = [];
    foreach ($catalogo as $p) $map[(string)$p['id']] = $p;
    $sessionCart = $_SESSION['bugistore_cart'] ?? [];
    if (!is_array($sessionCart)) $sessionCart = [];
    foreach (array_keys($sessionCart) as $storedId) {
        if (!isset($map[$storedId])) unset($_SESSION['bugistore_cart'][$storedId]);
    }
    $cart = $_SESSION['bugistore_cart'] ?? [];
    $items = [];
    $count = 0;
    $total = 0.0;
    foreach ($cart as $id => $cantidad) {
        if (!isset($map[$id])) continue;
        $p = $map[$id];
        $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$cantidad) continue;
        $precio = max(0, (float)$p['price']);
        $subtotal = round($precio * $cantidad, 2);
        $items[] = [
            'id' => (string)$p['id'], 'name' => (string)$p['name'],
            'category' => (string)$p['category'], 'platform' => (string)($p['platform'] ?? ''),
            'image' => (string)$p['image'], 'price' => $precio, 'quantity' => $cantidad,
            'stock' => max(0, (int)$p['stock']), 'subtotal' => $subtotal
        ];
        $count += $cantidad;
        $total += $subtotal;
    }
    return ['items' => $items, 'count' => $count, 'total' => round($total, 2)];
}

function guardar_pedido_archivo(array $pedido): bool {
    $dir = __DIR__ . '/storage';
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) return false;
    $path = $dir . '/pedidos.json';
    $fp = @fopen($path, 'c+');
    if (!$fp) return false;
    $ok = false;
    if (flock($fp, LOCK_EX)) {
        rewind($fp);
        $raw = stream_get_contents($fp);
        $orders = json_decode($raw ?: '[]', true);
        if (!is_array($orders)) $orders = [];
        $orders[] = $pedido;
        $encoded = json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded !== false) {
            rewind($fp);
            $ok = ftruncate($fp, 0) && fwrite($fp, $encoded . PHP_EOL) !== false && fflush($fp);
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    @chmod($path, 0640);
    return $ok;
}

function enviar_comprobante(array $pedido): string {
    if (trim(BUGISTORE_MAIL_FROM) === '' || !filter_var(BUGISTORE_MAIL_FROM, FILTER_VALIDATE_EMAIL)) {
        return 'no_configurado';
    }
    $lineas = [];
    foreach ($pedido['items'] as $item) {
        $lineas[] = sprintf("- %s x %d | %s", $item['name'], $item['quantity'], '$' . number_format($item['subtotal'], 2, '.', ','));
    }
    $mensaje = "Hola {$pedido['nombre']},\n\n";
    $mensaje .= "Gracias por probar BUGISTORE. Esta es la confirmación de tu compra SIMULADA.\n";
    $mensaje .= "No se realizó ningún cobro real.\n\nFolio: {$pedido['folio']}\nFecha: {$pedido['fecha']}\n\n";
    $mensaje .= "Productos:\n" . implode("\n", $lineas) . "\n\n";
    $mensaje .= "Total simulado: $" . number_format($pedido['total'], 2, '.', ',') . " MXN\n\n";
    $mensaje .= "Este comprobante sirve para validar el flujo de prueba de la tienda.\nEquipo BUGISTORE";
    $fromName = str_replace(["\r", "\n", '"'], '', BUGISTORE_MAIL_FROM_NAME);
    $subject = 'BUGISTORE - comprobante simulado ' . $pedido['folio'];
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromName . ' <' . BUGISTORE_MAIL_FROM . '>',
        'Reply-To: ' . BUGISTORE_MAIL_FROM,
        'X-Mailer: PHP/' . PHP_VERSION
    ];
    return @mail($pedido['correo'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $mensaje, implode("\r\n", $headers)) ? 'enviado' : 'error_envio';
}

$action = (string)($_GET['accion'] ?? $_POST['accion'] ?? 'obtener');
$data = entrada();
$catalogo = cargar_catalogo();

if (!isset($_SESSION['bugistore_cart']) || !is_array($_SESSION['bugistore_cart'])) {
    $_SESSION['bugistore_cart'] = [];
}
$cart =& $_SESSION['bugistore_cart'];

switch ($action) {
    case 'obtener':
        respuesta(['ok' => true] + resumen_carrito($catalogo));

    case 'agregar':
        $id = trim((string)($data['id'] ?? ''));
        $p = buscar_producto($catalogo, $id);
        if (!$p) respuesta(['ok' => false, 'mensaje' => 'El videojuego ya no existe en el catálogo. Actualiza la página.'], 404);
        $stock = max(0, (int)$p['stock']);
        if ($stock < 1) respuesta(['ok' => false, 'mensaje' => 'Este videojuego está agotado.'], 409);
        $cantidadAgregar = filter_var($data['cantidad'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if (!$cantidadAgregar) respuesta(['ok' => false, 'mensaje' => 'La cantidad debe ser un número entre 1 y 99.'], 422);
        $actual = (int)($cart[$id] ?? 0);
        if ($actual + $cantidadAgregar > $stock) respuesta(['ok' => false, 'mensaje' => 'No hay suficientes unidades disponibles.'], 409);
        $cart[$id] = $actual + $cantidadAgregar;
        respuesta(['ok' => true, 'mensaje' => 'Videojuego agregado al carrito.', 'resumen' => resumen_carrito($catalogo)]);

    case 'actualizar':
        $id = trim((string)($data['id'] ?? ''));
        $p = buscar_producto($catalogo, $id);
        if (!$p || !isset($cart[$id])) respuesta(['ok' => false, 'mensaje' => 'El videojuego no está en el carrito.'], 404);
        $cantidad = filter_var($data['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if (!$cantidad) respuesta(['ok' => false, 'mensaje' => 'La cantidad debe ser un número entre 1 y 99.'], 422);
        if ($cantidad > (int)$p['stock']) respuesta(['ok' => false, 'mensaje' => 'La cantidad supera las unidades disponibles.'], 409);
        $cart[$id] = $cantidad;
        respuesta(['ok' => true, 'mensaje' => 'Carrito actualizado.', 'resumen' => resumen_carrito($catalogo)]);

    case 'eliminar':
        $id = trim((string)($data['id'] ?? ''));
        unset($cart[$id]);
        respuesta(['ok' => true, 'mensaje' => 'Producto eliminado del carrito.', 'resumen' => resumen_carrito($catalogo)]);

    case 'vaciar':
        $cart = [];
        respuesta(['ok' => true, 'mensaje' => 'Carrito vaciado.', 'resumen' => resumen_carrito($catalogo)]);

    case 'checkout':
        $nombre = trim((string)($data['nombre'] ?? ''));
        $correo = trim((string)($data['correo'] ?? ''));
        if ($nombre === '' || longitud_texto($nombre) > 120) respuesta(['ok' => false, 'mensaje' => 'Escribe tu nombre (máximo 120 caracteres).'], 422);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || longitud_texto($correo) > 254) respuesta(['ok' => false, 'mensaje' => 'Escribe un correo electrónico válido.'], 422);
        $resumen = resumen_carrito($catalogo);
        if (!$resumen['items']) respuesta(['ok' => false, 'mensaje' => 'Tu carrito está vacío.'], 409);
        foreach ($resumen['items'] as $item) {
            if ($item['quantity'] > $item['stock']) respuesta(['ok' => false, 'mensaje' => 'La disponibilidad cambió para ' . $item['name'] . '. Actualiza el carrito.'], 409);
        }
        try {
            $folio = 'BGS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } catch (Throwable $e) {
            $folio = 'BGS-' . date('YmdHis') . '-' . strtoupper(substr(hash('sha256', uniqid('', true)), 0, 6));
        }
        $pedido = [
            'folio' => $folio,
            'fecha' => date('Y-m-d H:i:s'),
            'nombre' => $nombre,
            'correo' => $correo,
            'items' => $resumen['items'],
            'total' => $resumen['total'],
            'estado' => 'SIMULACION_CONFIRMADA',
            'pago_real' => false
        ];
        if (!guardar_pedido_archivo($pedido)) {
            respuesta(['ok' => false, 'mensaje' => 'No se pudo guardar el pedido. Verifica que la carpeta storage tenga permisos de escritura en el servidor.'], 500);
        }
        // Descontar existencias del catálogo demo tras una compra simulada.
        $idsComprados = [];
        foreach ($resumen['items'] as $item) $idsComprados[$item['id']] = $item['quantity'];
        foreach ($catalogo as &$p) {
            if (isset($idsComprados[$p['id']]) && (int)$p['stock'] > 0) {
                $p['stock'] = max(0, (int)$p['stock'] - (int)$idsComprados[$p['id']]);
            }
        }
        unset($p);
        $existenciasActualizadas = guardar_catalogo($catalogo);
        $estadoCorreo = enviar_comprobante($pedido);
        $cart = [];
        respuesta([
            'ok' => true,
            'mensaje' => $existenciasActualizadas ? 'Compra de prueba confirmada. No se realizó ningún cobro real.' : 'El pedido de prueba quedó registrado, pero no se pudieron actualizar las existencias. Revisa los permisos de catalogo.json.',
            'pedido' => ['folio' => $folio, 'fecha' => $pedido['fecha'], 'nombre' => $nombre, 'correo' => $correo, 'total' => $resumen['total'], 'items' => $resumen['items'], 'correo_estado' => $estadoCorreo, 'existencias_actualizadas' => $existenciasActualizadas]
        ]);

    default:
        respuesta(['ok' => false, 'mensaje' => 'Acción no válida.'], 400);
}
