'use strict';
document.querySelectorAll('.password-toggle').forEach(button => {
  button.addEventListener('click', () => {
    const field = document.getElementById(button.getAttribute('aria-controls'));
    const show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    button.textContent = show ? 'Ocultar' : 'Ver';
    button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
});
const dialog = document.getElementById('delete-dialog');
let previousFocus;
document.querySelectorAll('[data-delete-id]').forEach(button => {
  button.addEventListener('click', () => {
    previousFocus = button;
    document.getElementById('delete-form').action = '/files/' + button.dataset.deleteId + '/delete';
    document.getElementById('delete-filename').textContent = button.dataset.deleteName;
    dialog.showModal();
    document.getElementById('cancel-delete').focus();
  });
});
document.getElementById('cancel-delete')?.addEventListener('click', () => dialog.close());
dialog?.addEventListener('close', () => previousFocus?.focus());
const input = document.getElementById('file');
const zone = document.getElementById('drop-zone');
function selected() {
  const file = input.files[0];
  document.getElementById('selected-file').textContent = file ? file.name + ' · ' + (file.size / 1048576).toFixed(2) + ' MB' : 'Ningún archivo seleccionado.';
  input.setCustomValidity(file && file.size > 10485760 ? 'El archivo supera el máximo de 10 MB.' : '');
}
input?.addEventListener('change', selected);
zone?.addEventListener('dragover', event => { event.preventDefault(); zone.classList.add('dragging'); });
zone?.addEventListener('dragleave', () => zone.classList.remove('dragging'));
zone?.addEventListener('drop', event => {
  event.preventDefault(); zone.classList.remove('dragging');
  if (event.dataTransfer.files.length === 1) { input.files = event.dataTransfer.files; selected(); }
});
document.getElementById('upload-form')?.addEventListener('submit', () => {
  const button = document.getElementById('upload-submit');
  button.disabled = true; button.textContent = 'Guardando archivo…';
});
