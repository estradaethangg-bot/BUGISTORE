(function () {
  'use strict';
  const categoria = document.getElementById('conte')?.dataset.categoria;
  const container = document.getElementById('conte');
  if (!categoria || !container) return;

  const categorias = {
    carreras: 'Carreras',
    historia: 'Historia',
    battle: 'Battle Royale',
    estrategia: 'Estrategia',
    mas: 'Más juegos'
  };
  const money = value => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value) || 0);
  const html = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));

  // Lee primero el texto para distinguir JSON válido, respuesta vacía y errores PHP/Apache.
  async function leerJson(response, endpoint) {
    const texto = await response.text();
    if (!texto.trim()) {
      throw new Error(`${endpoint} devolvió una respuesta vacía (HTTP ${response.status}). Comprueba que PHP esté habilitado y revisa los errores de Apache/PHP.`);
    }
    let data;
    try {
      data = JSON.parse(texto);
    } catch (_) {
      const detalle = texto.replace(/\s+/g, ' ').slice(0, 160);
      throw new Error(`${endpoint} no devolvió JSON válido (HTTP ${response.status}). Respuesta del servidor: ${detalle}`);
    }
    return data;
  }

  async function api(action, payload = {}) {
    const response = await fetch(`api_carrito.php?accion=${encodeURIComponent(action)}`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json; charset=UTF-8' },
      body: JSON.stringify(payload)
    });
    const data = await leerJson(response, 'api_carrito.php');
    if (!response.ok || !data.ok) throw new Error(data.mensaje || `No se pudo procesar la solicitud (HTTP ${response.status}).`);
    return data;
  }

  function avisar(mensaje, tipo = 'info') {
    let box = document.getElementById('catalogoMensaje');
    if (!box) {
      box = document.createElement('div');
      box.id = 'catalogoMensaje';
      box.setAttribute('role', 'status');
      container.prepend(box);
    }
    box.className = `catalog-message ${tipo}`;
    box.textContent = mensaje;
    window.setTimeout(() => { if (box && box.textContent === mensaje) box.textContent = ''; }, 4500);
  }

  async function actualizarContador() {
    try {
      const response = await fetch('api_carrito.php?accion=obtener', { credentials: 'same-origin', cache: 'no-store' });
      const data = await leerJson(response, 'api_carrito.php');
      if (response.ok && data.ok && window.parent !== window) {
        window.parent.postMessage({ type: 'bugistore-cart-updated', count: data.count || 0 }, window.location.origin);
      }
    } catch (_) { /* La vista del catálogo sigue disponible aunque falle la conexión. */ }
  }

  function tarjeta(p) {
    const agotado = Number(p.stock) <= 0;
    return `
      <article class="cartas gran product-card">
        <u class="titul">${html(p.name)}</u>
        <img src="${html(p.image)}" class="port" alt="Portada de ${html(p.name)}" loading="lazy">
        <u class="ps4 tu">${html(p.platform || 'Videojuego')}</u>
        <u class="cost tu">${Number(p.price) === 0 ? 'Gratis' : money(p.price)}</u>
        
        <u class="boto dis tu">${agotado ? 'Agotado' : `Disponible: ${Number(p.stock)}`}</u>
        <a class="boto tu add-cart" href="#" data-id="${html(p.id)}" ${agotado ? 'aria-disabled="true"' : ''}>${agotado ? 'Agotado' : 'Agregar al carrito'}</a>
        
      </article>`;
  }

  async function iniciar() {
    container.innerHTML = `<div class="catalog-heading"><h1>${html(categorias[categoria] || 'Catálogo')}</h1><p>Elige tus videojuegos y agrégalos al carrito.</p></div><p class="loading-catalog">Cargando catálogo…</p>`;
    try {
      const response = await fetch('api_catalogo.php', { credentials: 'same-origin', cache: 'no-store' });
      const data = await leerJson(response, 'api_catalogo.php');
      if (!response.ok || !data.ok) throw new Error(data.mensaje || `No se pudo cargar el catálogo (HTTP ${response.status}).`);
      const productos = (data.productos || []).filter(p => p.category === categoria);
      const encabezado = `<div class="catalog-heading"><h1>${html(categorias[categoria] || 'Catálogo')}</h1><p></p></div>`;
      const tarjetas = productos.map(tarjeta).join('');
     container.innerHTML = encabezado + (tarjetas || '<p class="catalog-empty">No hay videojuegos en esta categoría.</p>');
      await actualizarContador();
    } catch (error) {
      container.innerHTML = `<div class="catalog-error"><h2>No se pudo cargar el catálogo</h2><p>${html(error.message)}</p><p>Abre el proyecto desde AppServ, XAMPP o el hosting (no directamente como archivo).</p><button type="button" id="reintentarCatalogo">Reintentar</button></div>`;
      document.getElementById('reintentarCatalogo')?.addEventListener('click', iniciar);
    }
  }

  container.addEventListener('click', async event => {
    const link = event.target.closest('a.add-cart');
    if (!link) return;
    event.preventDefault();
    if (link.getAttribute('aria-disabled') === 'true') return;
    const textoOriginal = link.textContent;
    link.textContent = 'Agregando…';
    link.style.pointerEvents = 'none';
    try {
      const data = await api('agregar', { id: link.dataset.id, cantidad: 1 });
      avisar(data.mensaje || 'Videojuego agregado.', 'success');
      if (window.parent !== window) window.parent.postMessage({ type: 'bugistore-cart-updated', count: data.resumen?.count || 0 }, window.location.origin);
    } catch (error) {
      avisar(error.message, 'error');
    } finally {
      link.textContent = textoOriginal;
      link.style.pointerEvents = '';
      await actualizarContador();
    }
  });

  iniciar();
})();
