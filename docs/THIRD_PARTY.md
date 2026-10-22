# Recursos de terceros incluidos

Los recursos se sirven desde el propio proyecto y funcionan sin CDN durante el uso de la aplicación. No se emplea un gestor de paquetes ni se requiere Node.js.

| Recurso | Versión | Licencia | Origen verificado |
| --- | --- | --- | --- |
| Bootstrap CSS | 5.3.8 | MIT | [Documentación oficial](https://getbootstrap.com/docs/5.3/getting-started/download/) |
| PDF.js, worker, CMaps y fuentes estándar | 6.4.299 | Apache-2.0; ver avisos de fuentes incluidos | [Release oficial Mozilla](https://github.com/mozilla/pdf.js/releases/tag/v6.4.299) |

Bootstrap se descargó del CDN enlazado por su documentación oficial, con verificación SHA-384:

```text
sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB
```

PDF.js se descargó de `mozilla/pdf.js`, release estable `v6.4.299`, publicada el 03/10/2026. El ZIP `pdfjs-6.4.299-dist.zip` se verificó contra el digest SHA-256 publicado por la API de GitHub:

```text
f2245eb7ef3f674c872fe568a177bfec15874f5c034613e55d8e307f93ebd86d
```

Se incluyeron únicamente `build/pdf.mjs`, `build/pdf.worker.mjs`, `web/cmaps`, `web/standard_fonts` y LICENSE. No se incorpora el visor completo, sandbox ejecutable de scripts PDF ni código de servidor de terceros. La integración propia utiliza la API descrita en [los ejemplos oficiales](https://mozilla.github.io/pdf.js/examples/).

El PDF se obtiene del endpoint con sesión y autorización; se dibuja una página por vez en canvas. Se deshabilitan evaluación dinámica, fuentes mediante inyección CSS y WebAssembly; no se construyen capas de formularios, enlaces, anotaciones ni scripts del documento. El canvas se limita a 8 millones de píxeles. PDFs protegidos con contraseña requieren descarga. Mantener las bibliotecas actualizadas ante futuros avisos de seguridad.

Las licencias completas están junto a los recursos: `public/assets/css/bootstrap.LICENSE` y `public/assets/vendor/pdfjs/LICENSE`, además de los avisos de las fuentes estándar.
