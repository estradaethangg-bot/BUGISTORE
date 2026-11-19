'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const $ = id => document.getElementById(id);
  const clave = $('txt_clave');
  const nombre = $('txt_nombre');
  const genero = $('txt_genero');
  const precio = $('txt_precio');
  const disponibilidad = $('txt_disp');
  const buscarBtn = $('bt_buscar');
  let catalogo = [];
  let productoActual = null;
  function moneda(value) {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value) || 0);
  }
  function mensaje(text, type = 'info') {
    if (window.Swal && Swal.fire) Swal.fire({ title: type === 'error' ? 'Atención' : 'BUGISTORE', text, icon: type, confirmButtonText: 'Aceptar' });
    else window.alert(text);
  }
  
  function limpiarDetalle() {
    productoActual = null;
    nombre.value = '';
    genero.value = '';
    precio.value = '';
    disponibilidad.value = '';
  
  }
  async function cargarCatalogo() {
    try {
      const response = await fetch('api_catalogo.php', { credentials: 'same-origin', cache: 'no-store' });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo consultar el catálogo.');
      catalogo = data.productos || [];
    } catch (error) {
      mensaje(`${error.message} Abre el proyecto desde AppServ/XAMPP o tu hosting.`, 'error');
    }
  }
  function buscar() {
    const q = clave.value.trim().toLocaleLowerCase('es-MX');
    if (!q) { limpiarDetalle(); mensaje('Escribe la clave o el nombre de un videojuego.', 'warning'); return; }
    const exacto = catalogo.find(p => String(p.id).toLocaleLowerCase('es-MX') === q);
    const coincidencias = exacto ? [exacto] : catalogo.filter(p => String(p.name).toLocaleLowerCase('es-MX').includes(q));
    if (!coincidencias.length) { limpiarDetalle(); mensaje('No se encontró un videojuego con esa clave o nombre.', 'error'); return; }
    productoActual = coincidencias[0];
    nombre.value = productoActual.name;
    genero.value = ({carreras:'Carreras',historia:'Historia',battle:'Battle Royale',estrategia:'Estrategia',mas:'Más juegos'})[productoActual.category] || productoActual.category;
    precio.value = Number(productoActual.price) === 0 ? 'Gratis' : moneda(productoActual.price);
    disponibilidad.value = `${productoActual.stock} disponibles`;
    clave.value = productoActual.id;
    habilitarBotones(true);
    if (coincidencias.length > 1) mensaje(`Se muestran los datos de ${productoActual.name}. Hay más coincidencias; escribe un nombre más específico.`, 'info');
  }

  buscarBtn.addEventListener('click', buscar);
  clave.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); buscar(); } });
  clave.addEventListener('input', () => { if (productoActual && clave.value.trim() !== productoActual.id) limpiarDetalle(); });
  
 

  cargarCatalogo();
});
