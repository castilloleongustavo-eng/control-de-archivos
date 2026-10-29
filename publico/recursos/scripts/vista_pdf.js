/**
 * Archivo: publico/recursos/scripts/vista_pdf.js
 * Propósito: Dibuja las páginas PDF dentro de la aplicación.
 * Funcionamiento: Configura PDF.js y su worker local, carga el documento autorizado y atiende los botones anterior y siguiente.
 */
import { getDocument, GlobalWorkerOptions } from '/recursos/externos/pdfjs/build/pdf.mjs';
/** Integra la API externa sin traducir sus propiedades obligatorias ni activar scripts PDF. */
GlobalWorkerOptions.workerSrc = '/recursos/externos/pdfjs/build/pdf.worker.mjs';
// Referencias al canvas, botones e indicadores definidos en vista_previa.php.
const visor = document.getElementById('visor-pdf');
const lienzo = document.getElementById('lienzo-pdf');
const anterior = document.getElementById('pdf-anterior');
const siguiente = document.getElementById('pdf-siguiente');
const estado = document.getElementById('estado-pdf');
const contador = document.getElementById('contador-paginas');
let documentoPdf;
// La página actual y la bandera de dibujo evitan ejecutar dos renderizados sobre el mismo canvas.
let paginaActual = 1;
let dibujando = false;

/** Recibe el número de página y dibuja su contenido sin enlaces, formularios ni anotaciones activas. */
async function dibujarPagina(numeroPagina) {
  if (dibujando) return;
  dibujando = true; anterior.disabled = siguiente.disabled = true;
  try {
    const pagina = await documentoPdf.getPage(numeroPagina);
    const natural = pagina.getViewport({ scale: 1 });
    const disponible = Math.min(visor.clientWidth - 32, 1100);
    // Se adapta al ancho del visor sin reservar un lienzo excesivo en documentos con dimensiones inusuales.
    // Las dimensiones del documento no pueden reservar un lienzo mayor de ocho millones de píxeles.
    const escala = Math.min(2, disponible / natural.width, Math.sqrt(8000000 / (natural.width * natural.height)));
    const vista = pagina.getViewport({ scale: escala });
    lienzo.width = Math.max(1, Math.floor(vista.width)); lienzo.height = Math.max(1, Math.floor(vista.height));
    await pagina.render({ canvasContext: lienzo.getContext('2d'), viewport: vista }).promise;
    paginaActual = numeroPagina;
    lienzo.setAttribute('aria-label', 'Página ' + paginaActual + ' de ' + documentoPdf.numPages);
    contador.textContent = paginaActual + ' / ' + documentoPdf.numPages;
    estado.textContent = ''; visor.dataset.renderizado = 'true'; pagina.cleanup();
  } catch { estado.textContent = 'No pudimos mostrar esta página. Puedes descargar el documento para abrirlo.'; }
  finally { dibujando = false; anterior.disabled = paginaActual <= 1; siguiente.disabled = paginaActual >= documentoPdf.numPages; }
}
// Los controles solicitan una página; dibujarPagina bloquea los botones durante el renderizado.
anterior.addEventListener('click', () => dibujarPagina(paginaActual - 1));
siguiente.addEventListener('click', () => dibujarPagina(paginaActual + 1));
try {
  // Carga con credenciales y recursos locales; desactiva evaluación dinámica y WebAssembly.
  documentoPdf = await getDocument({ url: visor.dataset.origen, withCredentials: true, isEvalSupported: false, disableFontFace: true, useWasm: false, cMapUrl: '/recursos/externos/pdfjs/cmaps/', cMapPacked: true, standardFontDataUrl: '/recursos/externos/pdfjs/standard_fonts/' }).promise;
  await dibujarPagina(1);
} catch { contador.textContent = 'Vista previa no disponible'; estado.textContent = 'El documento está protegido o no se pudo mostrar. Descárgalo para abrirlo con tu aplicación.'; }
