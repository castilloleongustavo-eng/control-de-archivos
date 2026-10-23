import { unzipSync } from '/recursos/externos/fflate/fflate.js';

const visor = document.getElementById('visor-documento');
const estado = document.getElementById('estado-documento');
const contenido = document.getElementById('contenido-documento');
const controles = document.getElementById('controles-documento');
const lector = new TextDecoder('utf-8', { fatal: true });
// Los espacios de nombres distinguen propiedades Office de etiquetas ajenas dentro de un mismo XML.
const espacioWord = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
const espacioRelaciones = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
let nodos = 0;

/** Construye solo elementos propios; nunca inserta HTML, estilos ni scripts del archivo. */
function elemento(etiqueta, texto, clase) {
  if (++nodos > 60000) throw new Error('El documento es demasiado complejo para la vista previa.');
  const nodo = document.createElement(etiqueta);
  if (texto !== undefined) nodo.textContent = texto;
  if (clase) nodo.className = clase;
  return nodo;
}

/** Lee XML como datos y rechaza entidades, DTD y documentos mal formados. */
function xml(archivos, ruta, obligatorio = true) {
  if (!archivos[ruta]) {
    if (obligatorio) throw new Error('El documento está incompleto.');
    return null;
  }
  const texto = lector.decode(archivos[ruta]);
  if (/<!DOCTYPE|<!ENTITY/i.test(texto)) throw new Error('El documento contiene XML no admitido.');
  const resultado = new DOMParser().parseFromString(texto, 'application/xml');
  if (resultado.getElementsByTagName('parsererror').length) throw new Error('El documento contiene XML inválido.');
  return resultado;
}
// Los prefijos XML pueden variar entre programas; se busca por nombre local, no por un prefijo fijo.
const descendientes = (nodo, nombre) => [...nodo.getElementsByTagNameNS('*', nombre)];
const hijos = (nodo, nombre) => [...nodo.children].filter(hijo => hijo.localName === nombre);
const atributoWord = (nodo, nombre) => nodo?.getAttributeNS(espacioWord, nombre) || '';
const textoCompleto = nodo => descendientes(nodo, 't').map(texto => texto.textContent).join('');

/** Resuelve exclusivamente entradas locales del ZIP; no carga relaciones externas. */
function rutaRelacion(base, destino) {
  if (!destino || /[:\\?#]/.test(destino)) return null;
  const partes = destino.startsWith('/') ? [] : base.split('/').slice(0, -1);
  for (const parte of destino.split('/')) {
    if (parte === '..') { if (!partes.length) return null; partes.pop(); }
    else if (parte && parte !== '.') partes.push(parte);
  }
  return partes.join('/');
}
/** Asocia los identificadores Office con archivos internos y excluye destinos externos. */
function relaciones(archivos, ruta) {
  const documento = xml(archivos, ruta, false);
  return new Map(documento ? descendientes(documento, 'Relationship').filter(r => r.getAttribute('TargetMode') !== 'External').map(r => [r.getAttribute('Id'), r.getAttribute('Target')]) : []);
}

/** Extrae solo XML e imágenes compatibles, con límites de entradas y tamaño expandido. */
function extraer(datos) {
  let total = 0;
  let entradas = 0;
  return unzipSync(datos, { filter: archivo => {
    total += archivo.originalSize;
    if (++entradas > 2000 || total > 50 * 1024 * 1024) throw new Error('El documento supera el límite de lectura.');
    if (archivo.name.includes('..') || archivo.name.startsWith('/') || archivo.name.includes('\\')) throw new Error('El contenedor no es válido.');
    return /\.(xml|rels|png|jpe?g)$/i.test(archivo.name);
  } });
}

/** Presenta párrafos, títulos, tablas e imágenes de Word sin ejecutar contenido activo. */
function mostrarWord(archivos) {
  const documento = xml(archivos, 'word/document.xml');
  const cuerpo = descendientes(documento, 'body')[0];
  if (!cuerpo) throw new Error('El documento no contiene un cuerpo de texto.');
  const medios = relaciones(archivos, 'word/_rels/document.xml.rels');
  const pagina = elemento('article', undefined, 'pagina-word');
  /** Recorre el cuerpo Word y traduce únicamente estructuras conocidas a elementos de lectura. */
  function dibujar(origen, destino, profundidad = 0) {
    if (profundidad > 40) throw new Error('El documento es demasiado complejo.');
    for (const hijo of origen.children) {
      let receptor = destino;
      if (hijo.localName === 'p') {
        const estilo = atributoWord(descendientes(hijo, 'pStyle')[0], 'val');
        const nivel = /^(?:Heading|Titulo|Título)([1-6])$/i.exec(estilo);
        receptor = elemento(nivel ? 'h' + nivel[1] : 'p');
        const alineacion = atributoWord(descendientes(hijo, 'jc')[0], 'val');
        if (['center', 'right', 'both'].includes(alineacion)) receptor.classList.add('alineacion-' + alineacion);
        if (descendientes(hijo, 'numPr').length) receptor.classList.add('parrafo-lista');
        destino.append(receptor);
      } else if (['tbl', 'tr', 'tc'].includes(hijo.localName)) {
        receptor = elemento({ tbl: 'table', tr: 'tr', tc: 'td' }[hijo.localName]);
        destino.append(receptor);
      } else if (hijo.localName === 'r') {
        receptor = elemento('span');
        const propiedades = hijos(hijo, 'rPr')[0];
        for (const [nombre, clase] of [['b', 'negrita'], ['i', 'cursiva'], ['u', 'subrayado']]) {
          const propiedad = propiedades && hijos(propiedades, nombre)[0];
          if (propiedad && !['0', 'false', 'none', 'off'].includes(atributoWord(propiedad, 'val'))) receptor.classList.add(clase);
        }
        destino.append(receptor);
      } else if (hijo.localName === 't') { destino.append(document.createTextNode(hijo.textContent)); continue; }
      else if (hijo.localName === 'tab') { destino.append(document.createTextNode('\t')); continue; }
      else if (hijo.localName === 'br' || hijo.localName === 'cr') { destino.append(elemento('br')); continue; }
      else if (hijo.localName === 'blip') {
        const ruta = rutaRelacion('word/document.xml', medios.get(hijo.getAttributeNS(espacioRelaciones, 'embed')));
        if (ruta && archivos[ruta] && /\.(png|jpe?g)$/i.test(ruta)) {
          const imagen = elemento('img');
          const datos = archivos[ruta];
          let binario = '';
          for (let posicion = 0; posicion < datos.length; posicion += 8192) binario += String.fromCharCode(...datos.subarray(posicion, posicion + 8192));
          imagen.src = 'data:image/' + (/\.png$/i.test(ruta) ? 'png' : 'jpeg') + ';base64,' + btoa(binario);
          imagen.alt = 'Imagen del documento';
          destino.append(imagen);
        }
        continue;
      } else if (['pPr', 'rPr', 'tblPr', 'tcPr', 'sectPr', 'del'].includes(hijo.localName)) continue;
      dibujar(hijo, receptor, profundidad + 1);
    }
  }
  dibujar(cuerpo, pagina);
  contenido.append(pagina);
}

/** Convierte índices de columna a la notación habitual de Excel. */
function nombreColumna(indice) {
  let nombre = '';
  for (let numero = indice + 1; numero; numero = Math.floor((numero - 1) / 26)) nombre = String.fromCharCode(65 + (numero - 1) % 26) + nombre;
  return nombre;
}
/** Obtiene el índice numérico desde referencias como A1 o AA150 y rechaza coordenadas mal formadas. */
function indiceColumna(referencia) {
  const letras = /^([A-Z]{1,3})\d+$/.exec(referencia || '');
  if (!letras) throw new Error('La hoja contiene una celda inválida.');
  return [...letras[1]].reduce((valor, letra) => valor * 26 + letra.charCodeAt(0) - 64, 0) - 1;
}

/** Muestra fechas y porcentajes habituales sin interpretar fórmulas ni código de formato. */
function formatoNumerico(archivos, libro) {
  const estilos = xml(archivos, 'xl/styles.xml', false);
  const formatos = new Map(estilos ? descendientes(estilos, 'numFmt').map(formato => [Number(formato.getAttribute('numFmtId')), formato.getAttribute('formatCode')]) : []);
  const celdas = estilos && descendientes(estilos, 'cellXfs')[0];
  const indices = celdas ? hijos(celdas, 'xf').map(estilo => Number(estilo.getAttribute('numFmtId'))) : [];
  const origen1904 = ['1', 'true'].includes(descendientes(libro, 'workbookPr')[0]?.getAttribute('date1904'));
  return (valor, estilo) => {
    if (valor === undefined || valor === '' || !Number.isFinite(Number(valor))) return valor;
    const identificador = indices[Number(estilo)] || 0;
    const formato = (formatos.get(identificador) || '').replace(/"[^"]*"|\\.|\[[^\]]*\]/g, '');
    if ((identificador >= 14 && identificador <= 22) || (identificador >= 45 && identificador <= 47) || /[ydhs]/i.test(formato)) {
      const numero = Number(valor);
      const dias = origen1904 ? numero : numero < 60 ? numero + 1 : numero;
      const fecha = new Date((origen1904 ? Date.UTC(1904, 0, 1) : Date.UTC(1899, 11, 30)) + Math.round(dias * 86400000));
      if (!Number.isNaN(fecha.getTime())) return fecha.toISOString().slice(0, 19).replace('T00:00:00', '').replace('T', ' ');
    }
    if ([9, 10].includes(identificador) || formato.includes('%')) return new Intl.NumberFormat('es', { style: 'percent', maximumFractionDigits: 2 }).format(Number(valor));
    return valor;
  };
}

/** Presenta todas las hojas por bloques de filas y columnas, sin evaluar fórmulas ni enlaces. */
function mostrarExcel(archivos) {
  const libro = xml(archivos, 'xl/workbook.xml');
  const rutas = relaciones(archivos, 'xl/_rels/workbook.xml.rels');
  const compartidos = xml(archivos, 'xl/sharedStrings.xml', false);
  const textos = compartidos ? descendientes(compartidos, 'si').map(textoCompleto) : [];
  const formatear = formatoNumerico(archivos, libro);
  const hojas = descendientes(libro, 'sheet');
  if (!hojas.length) throw new Error('El libro no contiene hojas.');
  const selector = elemento('select', undefined, 'form-select selector-hoja');
  selector.setAttribute('aria-label', 'Hoja del libro');
  hojas.forEach((hoja, indice) => { const opcion = elemento('option', hoja.getAttribute('name') || 'Hoja ' + (indice + 1)); opcion.value = indice; selector.append(opcion); });
  const anterior = elemento('button', '← Filas', 'btn btn-sm btn-outline-secondary');
  const siguiente = elemento('button', 'Filas →', 'btn btn-sm btn-outline-secondary');
  const izquierda = elemento('button', '← Columnas', 'btn btn-sm btn-outline-secondary');
  const derecha = elemento('button', 'Columnas →', 'btn btn-sm btn-outline-secondary');
  const posicion = elemento('span');
  posicion.setAttribute('aria-live', 'polite');
  for (const boton of [anterior, siguiente, izquierda, derecha]) boton.type = 'button';
  controles.append(selector, anterior, siguiente, izquierda, derecha, posicion);
  controles.hidden = false;
  let filas = new Map();
  let maxFila = 0;
  let maxColumna = 0;
  let filaInicial = 1;
  let columnaInicial = 0;
  /** Dibuja bloques de 100 filas y 25 columnas para mantener acotado el tamaño de la tabla en pantalla. */
  function dibujar() {
    nodos = 0;
    contenido.replaceChildren();
    const tabla = elemento('table', undefined, 'tabla-hoja');
    const cabecera = elemento('thead');
    const titulos = elemento('tr'); titulos.append(elemento('th', ''));
    const finColumna = Math.min(maxColumna, columnaInicial + 24);
    const finFila = Math.min(maxFila, filaInicial + 99);
    for (let columna = columnaInicial; columna <= finColumna; columna++) { const celda = elemento('th', nombreColumna(columna)); celda.scope = 'col'; titulos.append(celda); }
    cabecera.append(titulos); tabla.append(cabecera);
    const cuerpo = elemento('tbody');
    for (let numero = filaInicial; numero <= finFila; numero++) {
      const fila = elemento('tr'); const titulo = elemento('th', numero); titulo.scope = 'row'; fila.append(titulo);
      for (let columna = columnaInicial; columna <= finColumna; columna++) fila.append(elemento('td', filas.get(numero)?.get(columna) ?? ''));
      cuerpo.append(fila);
    }
    tabla.append(cuerpo); contenido.append(tabla);
    posicion.textContent = maxFila ? `${filaInicial}–${finFila} / ${maxFila} · ${nombreColumna(columnaInicial)}–${nombreColumna(finColumna)}` : 'Hoja vacía';
    anterior.disabled = filaInicial === 1; siguiente.disabled = finFila >= maxFila;
    izquierda.disabled = columnaInicial === 0; derecha.disabled = finColumna >= maxColumna;
  }
  /** Carga celdas dispersas por coordenadas; los resultados guardados se leen sin ejecutar fórmulas. */
  function cambiarHoja() {
    try {
      const ruta = rutaRelacion('xl/workbook.xml', rutas.get(hojas[Number(selector.value)].getAttributeNS(espacioRelaciones, 'id')));
      const documento = xml(archivos, ruta);
      filas = new Map(); maxFila = 0; maxColumna = 0; filaInicial = 1; columnaInicial = 0;
      for (const celda of descendientes(documento, 'c')) {
        const referencia = celda.getAttribute('r'); const columna = indiceColumna(referencia); const fila = Number(referencia.match(/\d+$/)[0]);
        if (columna > 16383 || fila < 1 || fila > 1048576) throw new Error('La hoja contiene coordenadas inválidas.');
        const tipo = celda.getAttribute('t'); const valor = descendientes(celda, 'v')[0]?.textContent;
        let texto = tipo === 's' ? textos[Number(valor)] ?? '' : tipo === 'inlineStr' ? textoCompleto(celda) : tipo === 'b' ? (valor === '1' ? 'VERDADERO' : 'FALSO') : !tipo || tipo === 'n' ? formatear(valor, celda.getAttribute('s')) ?? '' : valor ?? '';
        if (!texto && descendientes(celda, 'f').length) texto = '=' + descendientes(celda, 'f')[0].textContent;
        if (!texto) continue;
        if (!filas.has(fila)) filas.set(fila, new Map());
        filas.get(fila).set(columna, texto); maxFila = Math.max(maxFila, fila); maxColumna = Math.max(maxColumna, columna);
      }
      dibujar(); estado.textContent = '';
    } catch (error) { contenido.replaceChildren(); estado.textContent = error.message; }
  }
  selector.addEventListener('change', cambiarHoja);
  anterior.addEventListener('click', () => { filaInicial = Math.max(1, filaInicial - 100); dibujar(); });
  siguiente.addEventListener('click', () => { filaInicial += 100; dibujar(); });
  izquierda.addEventListener('click', () => { columnaInicial = Math.max(0, columnaInicial - 25); dibujar(); });
  derecha.addEventListener('click', () => { columnaInicial += 25; dibujar(); });
  cambiarHoja();
}

/** Obtiene el binario desde la ruta existente, que comprueba sesión y propietario. */
try {
  const respuesta = await fetch(visor.dataset.origen, { credentials: 'same-origin', redirect: 'error', cache: 'no-store' });
  if (!respuesta.ok) throw new Error('No se pudo abrir el documento. Comprueba tu sesión.');
  const datos = new Uint8Array(await respuesta.arrayBuffer());
  if (datos.length > 10 * 1024 * 1024) throw new Error('El archivo supera el límite de lectura.');
  if (visor.dataset.formato === 'txt') contenido.append(elemento('pre', lector.decode(datos), 'texto-documento'));
  else if (visor.dataset.formato === 'docx') mostrarWord(extraer(datos));
  else if (visor.dataset.formato === 'xlsx') mostrarExcel(extraer(datos));
  else throw new Error('Este formato no está admitido.');
  if (estado.textContent === 'Cargando documento…') estado.textContent = '';
  visor.dataset.renderizado = estado.textContent ? 'false' : 'true';
} catch (error) {
  contenido.replaceChildren(); controles.hidden = true;
  estado.textContent = (error instanceof Error ? error.message : 'No se pudo mostrar el documento.') + ' Puedes descargarlo para abrirlo.';
  visor.dataset.renderizado = 'false';
} finally { visor.setAttribute('aria-busy', 'false'); }
