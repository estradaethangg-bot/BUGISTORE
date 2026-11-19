<?php
declare(strict_types=1);

function columnas_usuarios(mysqli $cn): array {
    $cols = [];
    $result = $cn->query('SHOW COLUMNS FROM `usuarios`');
    if ($result) {
        while ($row = $result->fetch_assoc()) $cols[] = (string)$row['Field'];
        $result->free();
    }
    return $cols;
}

function columna_usuario(array $cols, array $candidatas, bool $required = true): ?string {
    $map = [];
    foreach ($cols as $col) $map[strtolower($col)] = $col;
    foreach ($candidatas as $candidate) {
        if (isset($map[strtolower($candidate)])) return $map[strtolower($candidate)];
    }
    if ($required) throw new RuntimeException('La tabla usuarios no contiene una columna necesaria: ' . implode('/', $candidatas));
    return null;
}

function columnas_usuario_mapeadas(array $cols): array {
    return [
        'nombre' => columna_usuario($cols, ['nombre_u', 'nombre', 'nombres_u', 'nombres'], false),
        'apellidoP' => columna_usuario($cols, ['apellidoP_u', 'apellidoP', 'apellido_paterno', 'apellidop_u'], false),
        'apellidoM' => columna_usuario($cols, ['apellidoM_u', 'apellidoM', 'apellido_materno', 'apellidom_u'], false),
        'edad' => columna_usuario($cols, ['edad_u', 'edad'], false),
        'genero' => columna_usuario($cols, ['genero_u', 'genero', 'sexo_u', 'sexo'], false),
        'username' => columna_usuario($cols, ['username_u', 'username', 'usuario_u', 'usuario']),
        'correo' => columna_usuario($cols, ['correo_u', 'correo', 'email_u', 'email']),
        'password' => columna_usuario($cols, ['password_u', 'password', 'contrasena_u', 'contrasena', 'clave_u', 'clave'])
    ];
}
