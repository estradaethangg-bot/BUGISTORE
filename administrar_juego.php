<?php
declare(strict_types=1);
require_once __DIR__ . '/catalogo_lib.php';
session_start();
if (empty($_SESSION['bugistore_csrf'])) $_SESSION['bugistore_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['bugistore_csrf'];
$items = cargar_catalogo();
$categorias = categorias_catalogo();
$imagenes = imagenes_catalogo();
$accion = (string)($_GET['accion'] ?? 'agregar');
$id = trim((string)($_GET['id'] ?? $_POST['id'] ?? ''));
$producto = $id !== '' ? buscar_producto($items, $id) : null;
$error = '';

function longitud_texto_admin(string $texto): int {
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}
function slug_producto(string $name): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $slug = strtolower((string)$ascii);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-') ?: 'videojuego';
}
function redirigir_categoria(string $cat) {
    $allowed = array_keys(categorias_catalogo());
    if (!in_array($cat, $allowed, true)) $cat = 'mas';
    $dest = $cat === 'battle' ? 'battl.html' : $cat . '.html';
    header('Location: ' . $dest);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $token)) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Recarga la página e inténtalo de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'eliminar_confirmado') {
        if (!$producto) {
            $error = 'No se encontró el videojuego que intentas eliminar.';
        } else {
            $items = array_values(array_filter($items, static function ($p) use ($producto) { return $p['id'] !== $producto['id']; }));
            if (guardar_catalogo($items)) redirigir_categoria((string)$producto['category']);
            $error = 'No se pudo guardar el catálogo. Verifica los permisos de escritura del archivo catalogo.json.';
        }
    } elseif (($_POST['accion'] ?? '') === 'guardar') {
        $modo = (string)($_POST['modo'] ?? 'agregar');
        $name = trim((string)($_POST['name'] ?? ''));
        $category = (string)($_POST['category'] ?? 'mas');
        $platform = trim((string)($_POST['platform'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $priceRaw = trim((string)($_POST['price'] ?? ''));
        $stockRaw = trim((string)($_POST['stock'] ?? ''));
        $image = (string)($_POST['image'] ?? '');
        $valid = true;
        if (longitud_texto_admin($name) < 2 || longitud_texto_admin($name) > 120) { $error = 'El nombre debe tener entre 2 y 120 caracteres.'; $valid = false; }
        elseif (!array_key_exists($category, $categorias)) { $error = 'Selecciona una categoría válida.'; $valid = false; }
        elseif (longitud_texto_admin($platform) < 2 || longitud_texto_admin($platform) > 100) { $error = 'Escribe la plataforma del videojuego.'; $valid = false; }
        elseif (!is_numeric($priceRaw) || (float)$priceRaw < 0 || (float)$priceRaw > 1000000) { $error = 'El precio debe ser un número entre 0 y 1,000,000.'; $valid = false; }
        elseif (filter_var($stockRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]) === false) { $error = 'La disponibilidad debe ser un entero entre 0 y 9,999.'; $valid = false; }
        elseif (!in_array($image, $imagenes, true)) { $error = 'Selecciona una portada de las disponibles.'; $valid = false; }
        elseif (longitud_texto_admin($description) > 500) { $error = 'La descripción no puede superar 500 caracteres.'; $valid = false; }

        if ($valid) {
            $newId = $id;
            if ($modo !== 'editar' || !$producto) {
                $base = $category . '-' . slug_producto($name);
                $newId = $base;
                $n = 2;
                while (buscar_producto($items, $newId)) { $newId = $base . '-' . $n; $n++; }
            }
            $registro = [
                'id' => $newId,
                'name' => $name,
                'category' => $category,
                'platform' => $platform,
                'price' => round((float)$priceRaw, 2),
                'stock' => (int)$stockRaw,
                'image' => $image,
                'description' => $description
            ];
            $found = false;
            foreach ($items as $index => $p) {
                if (($p['id'] ?? '') === $newId) { $items[$index] = $registro; $found = true; break; }
            }
            if (!$found) $items[] = $registro;
            if (guardar_catalogo($items)) redirigir_categoria($category);
            $error = 'No se pudo guardar el catálogo. Verifica que catalogo.json tenga permisos de escritura.';
        }
    }
}

if ($accion === 'editar' && !$producto) {
    http_response_code(404);
}
$modo = ($accion === 'editar' && $producto) ? 'editar' : 'agregar';
$val = $producto ?: [
    'name' => '', 'category' => (string)($_GET['categoria'] ?? 'mas'), 'platform' => 'PlayStation / Xbox / PC',
    'price' => '499', 'stock' => 10, 'image' => $imagenes[0] ?? '', 'description' => ''
];
if (!array_key_exists((string)$val['category'], $categorias)) $val['category'] = 'mas';
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#111827"><title><?= $modo === 'editar' ? 'Modificar videojuego' : 'Agregar videojuego' ?> | BUGISTORE</title>
<style>
*{box-sizing:border-box}body{margin:0;padding:24px;background:#0f172a;color:#e5e7eb;font:16px/1.5 system-ui,Segoe UI,sans-serif}.panel{max-width:740px;margin:0 auto;background:#172033;border:1px solid #334155;border-radius:16px;padding:24px;box-shadow:0 15px 35px #0004}h1{margin-top:0;color:#fff}.sub{color:#cbd5e1}.field{display:flex;flex-direction:column;gap:6px;margin:14px 0}.field input,.field select,.field textarea{width:100%;padding:11px 12px;background:#0b1220;color:#f8fafc;border:1px solid #475569;border-radius:8px;font:inherit}.field textarea{min-height:80px;resize:vertical}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:20px}.btn{display:inline-block;border:0;border-radius:9px;padding:11px 16px;background:#2563eb;color:white;text-decoration:none;font:inherit;font-weight:700;cursor:pointer}.btn.secondary{background:#475569}.btn.danger{background:#b91c1c}.error{background:#7f1d1d;padding:12px;border-radius:8px}.note{font-size:13px;color:#cbd5e1}@media(max-width:560px){body{padding:10px}.panel{padding:16px}.grid{grid-template-columns:1fr}}
</style></head><body><main class="panel">
<h1><?= $modo === 'editar' ? 'Modificar videojuego' : 'Agregar videojuego' ?></h1><p class="sub">Los cambios se guardan en el catálogo de demostración y se reflejan en la categoría.</p>
<?php if ($error !== ''): ?><p class="error" role="alert"><?= escapar($error) ?></p><?php endif; ?>
<?php if ($accion === 'eliminar' && $producto): ?>
  <p>¿Seguro que quieres eliminar <strong><?= escapar($producto['name']) ?></strong> del catálogo?</p>
  <p class="note">Esta acción no se puede deshacer. Los pedidos anteriores de prueba se conservan.</p>
  <form method="post"><input type="hidden" name="csrf" value="<?= escapar($csrf) ?>"><input type="hidden" name="id" value="<?= escapar($producto['id']) ?>"><input type="hidden" name="accion" value="eliminar_confirmado"><div class="actions"><button class="btn danger" type="submit">Sí, eliminar</button><a class="btn secondary" href="<?= escapar(($producto['category'] === 'battle') ? 'battl.html' : $producto['category'] . '.html') ?>">Cancelar</a></div></form>
<?php elseif ($accion === 'editar' && !$producto): ?>
  <p class="error">No se encontró el videojuego. Tal vez ya fue eliminado.</p><a class="btn secondary" href="inicio.html">Volver a la tienda</a>
<?php else: ?>
<form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="guardar"><input type="hidden" name="modo" value="<?= escapar($modo) ?>"><input type="hidden" name="id" value="<?= escapar($id) ?>">
  <label class="field">Nombre del videojuego<input name="name" value="<?= escapar($val['name']) ?>" maxlength="120" required></label>
  <div class="grid"><label class="field">Categoría<select name="category" required><?php foreach ($categorias as $key => $label): ?><option value="<?= escapar($key) ?>" <?= $val['category'] === $key ? 'selected' : '' ?>><?= escapar($label) ?></option><?php endforeach; ?></select></label>
  <label class="field">Plataforma<input name="platform" value="<?= escapar($val['platform']) ?>" maxlength="100" placeholder="PlayStation / Xbox / PC" required></label>
  <label class="field">Precio (MXN)<input name="price" type="number" min="0" max="1000000" step="0.01" value="<?= escapar($val['price']) ?>" required></label>
  <label class="field">Unidades disponibles<input name="stock" type="number" min="0" max="9999" step="1" value="<?= escapar($val['stock']) ?>" required></label></div>
  <label class="field">Portada<select name="image" required><?php foreach ($imagenes as $img): ?><option value="<?= escapar($img) ?>" <?= $val['image'] === $img ? 'selected' : '' ?>><?= escapar(basename($img)) ?> — <?= escapar(dirname($img)) ?></option><?php endforeach; ?></select></label>
  <label class="field">Descripción (opcional)<textarea name="description" maxlength="500"><?= escapar($val['description']) ?></textarea></label>
  <p class="note">Precios y cantidades son de demostración. No se realiza ningún cobro.</p>
  <div class="actions"><button type="submit" class="btn"><?= $modo === 'editar' ? 'Guardar cambios' : 'Agregar videojuego' ?></button><a class="btn secondary" href="<?= escapar(($val['category'] === 'battle') ? 'battl.html' : $val['category'] . '.html') ?>">Cancelar</a></div>
</form>
<?php endif; ?>
</main></body></html>
