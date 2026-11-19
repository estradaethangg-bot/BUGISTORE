<?php
// Conexión compartida por el inicio de sesión y el registro.
require_once __DIR__ . '/config.php';
mysqli_report(MYSQLI_REPORT_OFF);
$cn = @new mysqli(BUGISTORE_DB_HOST, BUGISTORE_DB_USER, BUGISTORE_DB_PASS, BUGISTORE_DB_NAME);
if ($cn->connect_errno) {
    error_log('BUGISTORE: no se pudo conectar con MySQL: ' . $cn->connect_error);
} else {
    $cn->set_charset('utf8mb4');
}
