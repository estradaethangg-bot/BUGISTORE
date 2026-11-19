(function () {
  'use strict';
  const lista = document.getElementById('cartItems');
  const empty = document.getElementById('cartEmpty');
  const total = document.getElementById('cartTotal');
  const count = document.getElementById('cartCount');
  const countSummary = document.getElementById('cartCountSummary');
  const message = document.getElementById('cartMessage');
  const clearButton = document.getElementById('clearCart');
  const checkoutForm = document.getElementById('checkoutForm');
  const checkoutButton = document.getElementById('checkoutButton');
  const receipt = document.getElementById('purchaseReceipt');
  if (!lista) return;

  const money = value => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value) || 0);
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  let current = { items: [], count: 0, total: 0 };

  async function request(action, payload = {}) {
    const response = await fetch(`api_carrito.php?accion=${encodeURIComponent(action)}`, {
      method: 'POST', credentials: 'same-origin', cache: 'no-store',
      headers: { 'Content-Type': 'application/json; charset=UTF-8' },
      body: JSON.stringify(payload)
    });
    let data;
    try { data = await response.json(); } catch (_) { throw new Error('El servidor no devolvió una respuesta válida. Comprueba que PHP esté funcionando.'); }
    if (!response.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo completar la operación.');
    return data;
  }

  function showMessage(text, type = 'info') {
    message.textContent = text;
    message.className = `cart-message ${type}`;
    message.hidden = !text;
  }

  function updateParentCount(n) {
    if (window.parent !== window) window.parent.postMessage({ type: 'bugistore-cart-updated', count: Number(n) || 0 }, window.location.origin);
  }

  function render(data) {
    current = data;
    count.textContent = `${data.count || 0} artículo${Number(data.count) === 1 ? '' : 's'}`;
    if (countSummary) countSummary.textContent = String(data.count || 0);
    total.textContent = money(data.total || 0);
    empty.hidden = (data.items || []).length > 0;
    lista.innerHTML = (data.items || []).map(item => `
      <article class="cart-item" data-id="${escapeHtml(item.id)}">
        <img class="cart-item-image" src="${escapeHtml(item.image)}" alt="Portada de ${escapeHtml(item.name)}">
        <div class="cart-item-info"><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.platform || '')}</p><p class="unit-price">${Number(item.price) === 0 ? 'Gratis' : money(item.price)} <span>por unidad</span></p></div>
        <div class="cart-item-quantity"><label for="qty-${escapeHtml(item.id)}">Cantidad</label><input id="qty-${escapeHtml(item.id)}" type="number" min="1" max="${Math.max(1, Number(item.stock))}" value="${Number(item.quantity)}" class="quantity-input"><button type="button" class="update-item" data-id="${escapeHtml(item.id)}">Actualizar</button></div>
        <div class="cart-item-price"><strong>${money(item.subtotal)}</strong><button type="button" class="remove-item" data-id="${escapeHtml(item.id)}">Eliminar</button></div>
      </article>`).join('');
    clearButton.disabled = !(data.items || []).length;
    checkoutButton.disabled = !(data.items || []).length;
    updateParentCount(data.count || 0);
  }

  async function load() {
    try {
      const response = await fetch('api_carrito.php?accion=obtener', { credentials: 'same-origin', cache: 'no-store' });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo leer el carrito.');
      render(data);
    } catch (error) {
      showMessage(`${error.message} Abre el proyecto desde AppServ/XAMPP o tu hosting.`, 'error');
    }
  }

  lista.addEventListener('click', async event => {
    const remove = event.target.closest('.remove-item');
    const update = event.target.closest('.update-item');
    if (!remove && !update) return;
    const id = (remove || update).dataset.id;
    try {
      if (remove) {
        const data = await request('eliminar', { id });
        showMessage(data.mensaje, 'success');
        render(data.resumen);
      } else {
        const row = update.closest('.cart-item');
        const qty = Number(row.querySelector('.quantity-input').value);
        const data = await request('actualizar', { id, cantidad: qty });
        showMessage(data.mensaje, 'success');
        render(data.resumen);
      }
    } catch (error) { showMessage(error.message, 'error'); }
  });

  clearButton.addEventListener('click', async () => {
    if (!window.confirm('¿Quieres vaciar el carrito completo?')) return;
    try {
      const data = await request('vaciar');
      render(data.resumen);
      showMessage(data.mensaje, 'success');
    } catch (error) { showMessage(error.message, 'error'); }
  });

  checkoutForm.addEventListener('submit', async event => {
    event.preventDefault();
    if (!checkoutForm.reportValidity()) return;
    checkoutButton.disabled = true;
    checkoutButton.textContent = 'Procesando…';
    receipt.hidden = true;
    try {
      const form = new FormData(checkoutForm);
      const data = await request('checkout', { nombre: form.get('nombre'), correo: form.get('correo') });
      const order = data.pedido;
      let mailNotice;
      if (order.correo_estado === 'enviado') {
        mailNotice = `El servidor aceptó el envío del comprobante a ${order.correo}. Revisa también la carpeta de spam.`;
      } else if (order.correo_estado === 'error_envio') {
        mailNotice = 'El pedido quedó registrado, pero el servidor no pudo enviar el correo. Revisa la configuración de correo en config.php y en tu hosting.';
      } else {
        mailNotice = 'El pedido quedó registrado, pero el envío de correo aún no está configurado. Configura BUGISTORE_MAIL_FROM en config.php y el servicio de correo de tu hosting.';
      }
      const detallePedido = (order.items || []).map(item => `<li>${escapeHtml(item.name)} × ${Number(item.quantity)} — ${money(item.subtotal)}</li>`).join('');
      receipt.innerHTML = `<h2>¡Compra de prueba confirmada!</h2><p><strong>Folio:</strong> ${escapeHtml(order.folio)}</p><p><strong>Fecha:</strong> ${escapeHtml(order.fecha)}</p><ul>${detallePedido}</ul><p><strong>Total simulado:</strong> ${money(order.total)} MXN</p><p>${escapeHtml(mailNotice)}</p><p class="simulation-note">No se realizó ningún cobro real.</p>`;
      receipt.hidden = false;
      showMessage(data.mensaje, 'success');
      checkoutForm.reset();
      await load();
    } catch (error) {
      showMessage(error.message, 'error');
    } finally {
      checkoutButton.disabled = !(current.items || []).length;
      checkoutButton.textContent = 'Confirmar compra simulada';
    }
  });

  load();
})();
