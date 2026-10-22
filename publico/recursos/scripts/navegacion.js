'use strict';

/** Navegación exclusivamente visual: no consulta datos ni cambia rutas, formularios o sesión. */
(() => {
  const barra = document.getElementById('navegacion-lateral');
  const alternar = document.getElementById('alternar-navegacion');
  const cerrar = document.getElementById('cerrar-navegacion');
  const fondo = document.getElementById('fondo-navegacion');
  const estructura = document.getElementById('estructura-aplicacion');
  if (!barra || !alternar || !fondo || !estructura) return;
  const consultaMovil = window.matchMedia('(max-width: 960px)');
  let contraida = false;
  let abierta = false;
  try { contraida = localStorage.getItem('documentos.menu-contraido') === 'si'; } catch { /* La preferencia visual es opcional. */ }

  /** Sincroniza accesibilidad y clases; en móvil solo el menú abierto puede recibir foco. */
  function actualizar() {
    const movil = consultaMovil.matches;
    document.body.classList.toggle('navegacion-contraida', !movil && contraida);
    document.body.classList.toggle('navegacion-abierta', movil && abierta);
    fondo.hidden = !(movil && abierta);
    barra.inert = movil && !abierta;
    estructura.inert = movil && abierta;
    alternar.setAttribute('aria-expanded', String(movil ? abierta : !contraida));
    alternar.setAttribute('aria-label', movil ? 'Abrir menú' : contraida ? 'Expandir menú' : 'Contraer menú');
  }

  /** Cierra el panel móvil y devuelve el foco al control que lo abrió. */
  function cerrarMenu() {
    abierta = false; actualizar(); alternar.focus();
  }
  alternar.addEventListener('click', () => {
    if (consultaMovil.matches) {
      abierta = !abierta; actualizar();
      if (abierta) cerrar.focus();
    } else {
      contraida = !contraida;
      try { localStorage.setItem('documentos.menu-contraido', contraida ? 'si' : 'no'); } catch { /* El menú sigue funcionando sin persistencia. */ }
      actualizar();
    }
  });
  cerrar.addEventListener('click', cerrarMenu);
  fondo.addEventListener('click', cerrarMenu);
  barra.querySelectorAll('nav a').forEach(enlace => enlace.addEventListener('click', () => {
    if (consultaMovil.matches) { abierta = false; actualizar(); }
  }));
  document.addEventListener('keydown', evento => {
    if (!consultaMovil.matches || !abierta) return;
    if (evento.key === 'Escape') { evento.preventDefault(); cerrarMenu(); }
    if (evento.key === 'Tab') {
      const controles = [...barra.querySelectorAll('a[href], button:not([disabled])')].filter(elemento => elemento.getClientRects().length);
      const primero = controles[0]; const ultimo = controles.at(-1);
      if (evento.shiftKey && document.activeElement === primero) { evento.preventDefault(); ultimo.focus(); }
      else if (!evento.shiftKey && document.activeElement === ultimo) { evento.preventDefault(); primero.focus(); }
    }
  });
  consultaMovil.addEventListener('change', () => { abierta = false; actualizar(); });
  actualizar();
})();
