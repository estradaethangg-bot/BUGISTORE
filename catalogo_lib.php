<?php
declare(strict_types=1);
const BUGISTORE_CATALOGO_FILE = __DIR__ . '/catalogo.json';

function cargar_catalogo(): array {
    if (!is_file(BUGISTORE_CATALOGO_FILE)) return [];
    $raw = file_get_contents(BUGISTORE_CATALOGO_FILE);
    $items = json_decode($raw ?: '[]', true);
    if (!is_array($items)) return [];
    return array_values(array_filter($items, static function ($p) {
        return is_array($p) && isset($p['id'], $p['name'], $p['category'], $p['price'], $p['stock'], $p['image']);
    }));
}

function guardar_catalogo(array $items): bool {
    $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    try { $suffix = bin2hex(random_bytes(6)); } catch (Throwable $e) { $suffix = str_replace('.', '', uniqid('', true)); }
    $tmp = BUGISTORE_CATALOGO_FILE . '.' . $suffix . '.tmp';
    if (file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) return false;
    @chmod($tmp, 0640);
    $saved = rename($tmp, BUGISTORE_CATALOGO_FILE);
    if (!$saved) @unlink($tmp);
    return $saved;
}

function buscar_producto(array $items, string $id) {
    foreach ($items as $item) if (hash_equals((string)$item['id'], $id)) return $item;
    return null;
}

function escapar($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function categorias_catalogo(): array {
    return [
        'carreras' => 'Carreras',
        'historia' => 'Historia',
        'battle' => 'Battle Royale',
        'estrategia' => 'Estrategia',
        'mas' => 'Más juegos'
    ];
}

function imagenes_catalogo(): array {
    $root = __DIR__ . '/Styles/Recursos';
    $paths = [];
    foreach (['carreras','historia','battle','estrategia','mas'] as $folder) {
        $dir = $root . '/' . $folder;
        foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $file) {
            $paths[] = 'Styles/Recursos/' . $folder . '/' . basename($file);
        }
    }
    sort($paths, SORT_NATURAL | SORT_FLAG_CASE);
    return $paths;
}
