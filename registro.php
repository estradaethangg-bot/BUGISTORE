<?php
declare(strict_types=1);
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/usuarios_lib.php';
header('Content-Type: text/plain; charset=utf-8');

function error_registro(string $message, int $status = 422) {
    http_response_code($status);
    echo 'ERROR: ' . $message;
    exit;
}

if (!isset($cn) || $cn->connect_errno) error_registro('No se pudo conectar con la base de datos. Revisa config.php.', 500);
$valores = [
    'nombre' => trim((string)($_POST['nombre_php'] ?? '')),
    'apellidoP' => trim((string)($_POST['ap1_php'] ?? '')),
    'apellidoM' => trim((string)($_POST['ap2_php'] ?? '')),
    'edad' => trim((string)($_POST['edad_php'] ?? '')),
    'genero' => trim((string)($_POST['genero_php'] ?? '')),
    'username' => trim((string)($_POST['user_php'] ?? '')),
    'correo' => trim((string)($_POST['correo_php'] ?? '')),
    'password' => (string)($_POST['pass_php'] ?? '')
];
foreach (['nombre','apellidoP','apellidoM','edad','username','correo','password'] as $required) {
    if ($valores[$required] === '') error_registro('Todos los campos obligatorios deben estar completos.');
}
if (!filter_var($valores['correo'], FILTER_VALIDATE_EMAIL)) error_registro('Escribe un correo electrónico válido.');
if (!ctype_digit($valores['edad']) || (int)$valores['edad'] < 1 || (int)$valores['edad'] > 120) error_registro('La edad debe ser un número entre 1 y 120.');
if (strlen($valores['password']) < 6) error_registro('La contraseña debe tener al menos 6 caracteres.');

try {
    $cols = columnas_usuarios($cn);
    $m = columnas_usuario_mapeadas($cols);
    $mailCol = '`' . str_replace('`', '``', $m['correo']) . '`';
    $userCol = '`' . str_replace('`', '``', $m['username']) . '`';
    $check = $cn->prepare("SELECT 1 FROM `usuarios` WHERE $mailCol = ? OR $userCol = ? LIMIT 1");
    if (!$check) throw new RuntimeException('No se pudo validar el usuario.');
    $check->bind_param('ss', $valores['correo'], $valores['username']);
    $check->execute();
    $existing = $check->get_result();
    $exists = $existing && $existing->num_rows > 0;
    $check->close();
    if ($exists) error_registro('El correo o nombre de usuario ya está registrado.', 409);

    $insertCols = [];
    $insertValues = [];
    foreach ($valores as $key => $value) {
        if (!empty($m[$key])) { $insertCols[] = '`' . str_replace('`', '``', $m[$key]) . '`'; $insertValues[] = $value; }
    }
    $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
    $sql = $cn->prepare('INSERT INTO `usuarios` (' . implode(',', $insertCols) . ') VALUES (' . $placeholders . ')');
    if (!$sql) throw new RuntimeException('No se pudo preparar el registro.');
    $types = str_repeat('s', count($insertValues));
    $sql->bind_param($types, ...$insertValues);
    if (!$sql->execute()) {
        if ((int)$cn->errno === 1062) error_registro('El correo o nombre de usuario ya está registrado.', 409);
        throw new RuntimeException('No se pudo guardar el registro.');
    }
    $sql->close();
    echo 'El registro se guardó correctamente';
} catch (Throwable $e) {
    error_log('BUGISTORE registro: ' . $e->getMessage());
    error_registro('No se pudo registrar la cuenta. Verifica las columnas de la tabla usuarios.', 500);
} finally {
    if (isset($cn) && $cn instanceof mysqli && !$cn->connect_errno) $cn->close();
}
