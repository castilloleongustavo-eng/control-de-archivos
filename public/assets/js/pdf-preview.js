import { getDocument, GlobalWorkerOptions } from '/assets/vendor/pdfjs/build/pdf.mjs';

GlobalWorkerOptions.workerSrc = '/assets/vendor/pdfjs/build/pdf.worker.mjs';
const viewer = document.getElementById('pdf-viewer');
const canvas = document.getElementById('pdf-canvas');
const previous = document.getElementById('pdf-previous');
const next = document.getElementById('pdf-next');
const status = document.getElementById('pdf-status');
const count = document.getElementById('pdf-page-count');
let documentPdf;
let currentPage = 1;
let rendering = false;

async function renderPage(pageNumber) {
  if (rendering) return;
  rendering = true;
  previous.disabled = next.disabled = true;
  try {
    const page = await documentPdf.getPage(pageNumber);
    const natural = page.getViewport({ scale: 1 });
    const available = Math.min(viewer.clientWidth - 32, 1100);
    // No reservar un canvas ilimitado por dimensiones controladas por el PDF.
    const scale = Math.min(2, available / natural.width, Math.sqrt(8000000 / (natural.width * natural.height)));
    const viewport = page.getViewport({ scale });
    canvas.width = Math.max(1, Math.floor(viewport.width));
    canvas.height = Math.max(1, Math.floor(viewport.height));
    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    currentPage = pageNumber;
    canvas.setAttribute('aria-label', 'Página ' + currentPage + ' de ' + documentPdf.numPages);
    count.textContent = currentPage + ' / ' + documentPdf.numPages;
    status.textContent = '';
    viewer.dataset.rendered = 'true';
    page.cleanup();
  } catch {
    status.textContent = 'No pudimos mostrar esta página. Puedes descargar el documento para abrirlo.';
  } finally {
    rendering = false;
    previous.disabled = currentPage <= 1;
    next.disabled = currentPage >= documentPdf.numPages;
  }
}

previous.addEventListener('click', () => renderPage(currentPage - 1));
next.addEventListener('click', () => renderPage(currentPage + 1));
try {
  documentPdf = await getDocument({
    url: viewer.dataset.source,
    withCredentials: true,
    isEvalSupported: false,
    disableFontFace: true,
    useWasm: false,
    cMapUrl: '/assets/vendor/pdfjs/cmaps/',
    cMapPacked: true,
    standardFontDataUrl: '/assets/vendor/pdfjs/standard_fonts/',
  }).promise;
  // Solo se pinta la página en canvas. No se incorpora una capa de scripts,
  // enlaces, formularios ni anotaciones activas provenientes del documento.
  await renderPage(1);
} catch {
  count.textContent = 'Vista previa no disponible';
  status.textContent = 'El documento está protegido o no se pudo mostrar. Descárgalo para abrirlo con tu aplicación.';
}
