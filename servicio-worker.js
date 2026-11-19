
const CACHE_NAME = "bugistore-v3";
const PAGINA_INICIAL = "./Login.html";

// Instalar la PWA
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.add(PAGINA_INICIAL))
      .then(() => self.skipWaiting())
  );
});

// Activar la PWA y eliminar cachés antiguos
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((nombres) =>
        Promise.all(
          nombres
            .filter((nombre) =>
              (
                nombre.startsWith("innova-games-") ||
                nombre.startsWith("bugistore-")
              ) &&
              nombre !== CACHE_NAME
            )
            .map((nombre) => caches.delete(nombre))
        )
      )
      .then(() => self.clients.claim())
  );
});

// Interceptar solicitudes
self.addEventListener("fetch", (event) => {
  const solicitud = event.request;
  const url = new URL(solicitud.url);

  // Solo atender solicitudes GET del mismo sitio
  if (
    url.origin !== self.location.origin ||
    solicitud.method !== "GET"
  ) {
    return;
  }

  // No almacenar ni interceptar archivos PHP
  if (url.pathname.toLowerCase().endsWith(".php")) {
    return;
  }

  // Las páginas HTML consultan primero al servidor
  if (solicitud.mode === "navigate") {
    event.respondWith(
      fetch(solicitud)
        .catch(async () => {
          const pagina = await caches.match(PAGINA_INICIAL);

          if (pagina) {
            return pagina;
          }

          return new Response(
            "No hay conexión. Conéctate a Internet e inténtalo nuevamente.",
            {
              status: 503,
              headers: {
                "Content-Type": "text/plain; charset=utf-8"
              }
            }
          );
        })
    );

    return;
  }

  // Guardar únicamente recursos estáticos
  const esRecursoEstatico =
    /\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?)$/i
      .test(url.pathname);

  if (!esRecursoEstatico) {
    return;
  }

  // Buscar primero en caché y descargar si no existe
  event.respondWith(
    caches.match(solicitud)
      .then(async (guardado) => {
        if (guardado) {
          return guardado;
        }

        const respuesta = await fetch(solicitud);

        if (respuesta.ok && respuesta.type === "basic") {
          const cache = await caches.open(CACHE_NAME);
          await cache.put(solicitud, respuesta.clone());
        }

        return respuesta;
      })
  );
});
