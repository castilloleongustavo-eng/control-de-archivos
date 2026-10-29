/**
 * Archivo: publico/recursos/scripts/aplicacion.js
 * Propósito: Gestiona las interacciones de formularios y documentos.
 * Funcionamiento: Permite ver la contraseña, confirmar eliminaciones y seleccionar o arrastrar un archivo antes de enviarlo.
 */
'use strict';
/** Mejora los formularios sin sustituir ninguna validación ni autorización del servidor. */
// Contraseña: conecta los botones Ver con su campo mediante aria-controls.
document.querySelectorAll('.alternar-contrasena').forEach(boton => {
  // aria-controls relaciona cada botón con su campo; cambiar el tipo no modifica el valor escrito.
  boton.addEventListener('click', () => {
    const campo = document.getElementById(boton.getAttribute('aria-controls'));
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    boton.textContent = mostrar ? 'Ocultar' : 'Ver';
    boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
});
// Eliminación: prepara el diálogo, la ruta POST y la devolución del foco al botón original.
const dialogo = document.getElementById('dialogo-eliminar');
// Se conserva el control de origen para devolverle el foco al cerrar el diálogo.
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
// Carga: obtiene el selector de archivo y los elementos que muestran su estado.
const entrada = document.getElementById('documento');
// Estos elementos solo existen en la pantalla de carga; los encadenamientos opcionales evitan errores en las demás.
const zona = document.getElementById('zona-carga');
/** Recibe el archivo seleccionado y muestra metadatos como texto, nunca mediante HTML. */
function mostrarSeleccion() {
  const archivo = entrada.files[0];
  document.getElementById('archivo-seleccionado').textContent = archivo ? archivo.name + ' · ' + (archivo.size / 1048576).toFixed(2) + ' MB' : 'Ningún archivo seleccionado.';
  entrada.setCustomValidity(archivo && archivo.size > 10485760 ? 'El archivo supera el máximo de 10 MB.' : '');
}
entrada?.addEventListener('change', mostrarSeleccion);
// Arrastre: evita la navegación del navegador y resalta visualmente la zona de carga.
zona?.addEventListener('dragover', evento => { evento.preventDefault(); zona.classList.add('arrastrando'); });
zona?.addEventListener('dragleave', () => zona.classList.remove('arrastrando'));
zona?.addEventListener('drop', evento => {
  evento.preventDefault(); zona.classList.remove('arrastrando');
  if (evento.dataTransfer.files.length === 1) { entrada.files = evento.dataTransfer.files; mostrarSeleccion(); }
});
// Durante el envío desactiva Guardar para evitar pulsaciones repetidas.
document.getElementById('formulario-carga')?.addEventListener('submit', () => {
  const boton = document.getElementById('guardar-documento'); boton.disabled = true; boton.textContent = 'Guardando archivo…';
});
