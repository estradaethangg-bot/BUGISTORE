<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/usuarios_lib.php';

function login_respuesta(array $payload, int $status = 200) {
    http_response_code($status);
    echo json_encode([$payload], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($cn) || $cn->connect_errno) {
    login_respuesta(['error' => 'No se pudo conectar con la base de datos. Revisa config.php.'], 500);
}
$correo = trim((string)($_POST['correo_php'] ?? ''));
$pass = (string)($_POST['pass_php'] ?? '');
if ($correo === '' || $pass === '') login_respuesta(['error' => 'Debes escribir tu correo y contraseña.'], 422);
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) login_respuesta(['error' => 'Escribe un correo electrónico válido.'], 422);

try {
    $cols = columnas_usuarios($cn);
    $m = columnas_usuario_mapeadas($cols);
    $userCol = '`' . str_replace('`', '``', $m['username']) . '`';
    $passCol = '`' . str_replace('`', '``', $m['password']) . '`';
    $mailCol = '`' . str_replace('`', '``', $m['correo']) . '`';
    $sql = $cn->prepare("SELECT $userCol AS username_value, $passCol AS password_value FROM `usuarios` WHERE $mailCol = ? LIMIT 1");
    if (!$sql) throw new RuntimeException('No se pudo preparar la consulta de inicio de sesión.');
    $sql->bind_param('s', $correo);
    if (!$sql->execute()) throw new RuntimeException('No se pudo consultar el usuario.');
    $resultado = $sql->get_result();
    $usuario = $resultado ? $resultado->fetch_assoc() : null;
    $sql->close();
    if (!$usuario) login_respuesta(['error' => 'Usuario o contraseña incorrectos.'], 401);

    $stored = (string)$usuario['password_value'];
    $info = password_get_info($stored);
    $esHash = !empty($info['algo']);
    $valida = $esHash ? password_verify($pass, $stored) : hash_equals($stored, $pass);
    if (!$valida) login_respuesta(['error' => 'Usuario o contraseña incorrectos.'], 401);

    session_regenerate_id(true);
    $_SESSION['user'] = (string)$usuario['username_value'];
    $_SESSION['email'] = $correo;
    login_respuesta(['user' => (string)$usuario['username_value']]);
} catch (Throwable $e) {
    error_log('BUGISTORE login: ' . $e->getMessage());
    login_respuesta(['error' => 'No se pudo realizar el inicio de sesión. Verifica la estructura de la tabla usuarios.'], 500);
} finally {
    if (isset($cn) && $cn instanceof mysqli && !$cn->connect_errno) $cn->close();
}
