<?php
/**
 * Configuración básica de BUGISTORE.
 * En Hostinger usa los datos de MySQL y correo que te entrega el panel.
 * El carrito de demostración no requiere conexión a la base de datos.
 */
define('BUGISTORE_DB_HOST', getenv('BUGISTORE_DB_HOST') ?: 'localhost');
define('BUGISTORE_DB_USER', getenv('BUGISTORE_DB_USER') ?: 'root');
define('BUGISTORE_DB_PASS', getenv('BUGISTORE_DB_PASS') ?: '12345678');
define('BUGISTORE_DB_NAME', getenv('BUGISTORE_DB_NAME') ?: 'videojuegos');

// Correo del remitente. Déjalo vacío hasta configurar el envío de correo en el hosting.
define('BUGISTORE_MAIL_FROM', getenv('BUGISTORE_MAIL_FROM') ?: '');
define('BUGISTORE_MAIL_FROM_NAME', getenv('BUGISTORE_MAIL_FROM_NAME') ?: 'BUGISTORE');
