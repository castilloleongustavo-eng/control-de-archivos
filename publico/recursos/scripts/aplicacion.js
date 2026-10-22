'use strict';
/** Mejora los formularios sin sustituir ninguna validación ni autorización del servidor. */
document.querySelectorAll('.alternar-contrasena').forEach(boton => {
  boton.addEventListener('click', () => {
    const campo = document.getElementById(boton.getAttribute('aria-controls'));
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    boton.textContent = mostrar ? 'Ocultar' : 'Ver';
    boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
});
const dialogo = document.getElementById('dialogo-eliminar');
let focoAnterior;
/** Solo se construye una ruta local con el ID generado por el servidor; el POST exige CSRF. */
document.querySelectorAll('[data-eliminar-id]').forEach(boton => {
  boton.addEventListener('click', () => {
    focoAnterior = boton;
    document.getElementById('formulario-eliminar').action = '/documentos/' + boton.dataset.eliminarId + '/eliminar';
    document.getElementById('nombre-eliminar').textContent = boton.dataset.eliminarNombre;
    dialogo.showModal(); document.getElementById('cancelar-eliminacion').focus();
  });
});
document.getElementById('cancelar-eliminacion')?.addEventListener('click', () => dialogo.close());
dialogo?.addEventListener('close', () => focoAnterior?.focus());
const entrada = document.getElementById('documento');
const zona = document.getElementById('zona-carga');
/** Recibe el archivo seleccionado y muestra metadatos como texto, nunca mediante HTML. */
function mostrarSeleccion() {
  const archivo = entrada.files[0];
  document.getElementById('archivo-seleccionado').textContent = archivo ? archivo.name + ' · ' + (archivo.size / 1048576).toFixed(2) + ' MB' : 'Ningún archivo seleccionado.';
  entrada.setCustomValidity(archivo && archivo.size > 10485760 ? 'El archivo supera el máximo de 10 MB.' : '');
}
entrada?.addEventListener('change', mostrarSeleccion);
zona?.addEventListener('dragover', evento => { evento.preventDefault(); zona.classList.add('arrastrando'); });
zona?.addEventListener('dragleave', () => zona.classList.remove('arrastrando'));
zona?.addEventListener('drop', evento => {
  evento.preventDefault(); zona.classList.remove('arrastrando');
  if (evento.dataTransfer.files.length === 1) { entrada.files = evento.dataTransfer.files; mostrarSeleccion(); }
});
document.getElementById('formulario-carga')?.addEventListener('submit', () => {
  const boton = document.getElementById('guardar-documento'); boton.disabled = true; boton.textContent = 'Guardando archivo…';
});
