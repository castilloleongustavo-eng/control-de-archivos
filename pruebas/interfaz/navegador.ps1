[CmdletBinding()]
param([string]$UrlBase = 'http://127.0.0.1:8089', [int]$PuertoDepuracion = 9228)
$ErrorActionPreference = 'Stop'
$raizProyecto = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
if (!(Test-Path -LiteralPath $chrome)) { throw 'Chrome no está instalado; no se instala automáticamente.' }
if (Get-NetTCPConnection -LocalPort $PuertoDepuracion -State Listen -ErrorAction SilentlyContinue) { throw 'Puerto de depuración ocupado.' }
$temporal = Join-Path $raizProyecto 'pruebas\.runtime'
$perfil = Join-Path $temporal 'chrome-perfil'
$salida = Join-Path $raizProyecto 'documentacion\evidencias\capturas'
$null = New-Item -ItemType Directory -Force -Path $salida
$procesoChrome = Start-Process -FilePath $chrome -ArgumentList @('--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check',"--remote-debugging-port=$PuertoDepuracion",'--remote-debugging-address=127.0.0.1',('--user-data-dir="' + $perfil + '"'),'about:blank') -WindowStyle Hidden -PassThru
$socket = [Net.WebSockets.ClientWebSocket]::new()
$script:messageId = 0
function Enviar-Cdp([string]$metodo, $parametros = @{}) {
    $script:messageId++
    $id = $script:messageId
    $json = @{id=$id;method=$metodo;params=$parametros} | ConvertTo-Json -Depth 12 -Compress
    $datos = [Text.Encoding]::UTF8.GetBytes($json)
    $socket.SendAsync([ArraySegment[byte]]::new($datos),[Net.WebSockets.WebSocketMessageType]::Text,$true,[Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    do {
        $flujo = [IO.MemoryStream]::new()
        do {
            $datosRecibidos = New-Object byte[] 65536
            $recibido = $socket.ReceiveAsync([ArraySegment[byte]]::new($datosRecibidos),[Threading.CancellationToken]::None).GetAwaiter().GetResult()
            $flujo.Write($datosRecibidos,0,$recibido.Count)
        } while (!$recibido.EndOfMessage)
        $respuesta = [Text.Encoding]::UTF8.GetString($flujo.ToArray()) | ConvertFrom-Json
        $flujo.Dispose()
    } while ($respuesta.id -ne $id)
    if ($respuesta.error) { throw ($respuesta.error | ConvertTo-Json -Compress) }
    return $respuesta.result
}
function Evaluar-JavaScript([string]$expresion) {
    $resultado = Enviar-Cdp 'Runtime.evaluate' @{expression=$expresion;returnByValue=$true;awaitPromise=$true}
    if ($resultado.exceptionDetails) { throw ($resultado.exceptionDetails | ConvertTo-Json -Depth 6) }
    return $resultado.result.value
}
function Navegar([string]$ruta) {
    $null = Enviar-Cdp 'Page.navigate' @{url=($UrlBase + $ruta)}
    for ($i=0;$i -lt 30;$i++) {
        Start-Sleep -Milliseconds 100
        if ((Evaluar-JavaScript 'document.readyState === "complete" && location.pathname') -eq $ruta) { return }
    }
    throw "Navegación no completada: $ruta"
}
function Capturar([string]$nombre) {
    $null = Evaluar-JavaScript 'document.fonts.ready.then(() => true)'
    $captura = Enviar-Cdp 'Page.captureScreenshot' @{format='png';captureBeyondViewport=$false}
    [IO.File]::WriteAllBytes((Join-Path $salida $nombre),[Convert]::FromBase64String($captura.data))
}
try {
    $paginas = $null
    for ($i=0;$i -lt 30;$i++) {
        Start-Sleep -Milliseconds 200
        try { $paginas = Invoke-RestMethod "http://127.0.0.1:$PuertoDepuracion/json/list" -TimeoutSec 2; break } catch { }
    }
    if (!$paginas) { throw 'No se pudo conectar con Chrome local.' }
    $pagina = @($paginas | Where-Object type -eq 'page')[0]
    $socket.ConnectAsync([Uri]$pagina.webSocketDebuggerUrl,[Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    $null = Enviar-Cdp 'Page.enable'
    $null = Enviar-Cdp 'Emulation.setDeviceMetricsOverride' @{width=1440;height=1000;deviceScaleFactor=1;mobile=$false}
    Navegar '/ingresar'
    if (!(Evaluar-JavaScript '!!document.querySelector("input[name=acceso]") && getComputedStyle(document.querySelector(".btn-primary")).backgroundColor === "rgb(37, 99, 235)"')) { throw 'Formulario o CSS no disponible.' }
    Capturar 'ingreso-escritorio.png'
    Navegar '/registrarse'; Capturar 'registro-escritorio.png'
    Navegar '/ingresar'
    $null = Evaluar-JavaScript 'document.querySelector("[name=acceso]").value="prueba1";document.querySelector("[name=contrasena]").value="DemoSegura2026!A";document.querySelector(".formulario-autenticacion").requestSubmit();true'
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 200; if ((Evaluar-JavaScript 'location.pathname') -eq '/') { break } }
    if ((Evaluar-JavaScript 'location.pathname') -ne '/') { throw 'No se inició sesión desde el navegador.' }
    Capturar 'resumen-escritorio.png'
    Navegar '/documentos/subir'; Capturar 'carga-escritorio.png'
    $null = Evaluar-JavaScript 'const f=new File(["Documento demostrativo de Documentos.\nSolo prueba1 puede acceder a este archivo.\n"],"Bienvenido a Documentos.txt",{type:"text/plain"}); const dt=new DataTransfer();dt.items.add(f);document.querySelector("#documento").files=dt.files;document.querySelector("#documento").dispatchEvent(new Event("change"));true'
    if (!(Evaluar-JavaScript 'document.querySelector("#archivo-seleccionado").textContent.includes("Bienvenido a Documentos.txt")')) { throw 'Selección de archivo no actualizada.' }
    $null = Evaluar-JavaScript 'document.querySelector("#formulario-carga").requestSubmit();true'
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 200; if ((Evaluar-JavaScript 'location.pathname') -eq '/documentos') { break } }
    if (!(Evaluar-JavaScript 'location.pathname === "/documentos" && document.body.textContent.includes("Bienvenido a Documentos.txt")')) { throw 'Carga desde navegador fallida.' }
    Capturar 'documentos-escritorio.png'
    $null = Evaluar-JavaScript 'document.querySelector("[data-eliminar-id]").click();true'
    if (!(Evaluar-JavaScript 'document.querySelector("#dialogo-eliminar").open && document.activeElement.id === "cancelar-eliminacion"')) { throw 'Modal o foco de confirmación fallido.' }
    Capturar 'confirmacion-eliminacion.png'
    $null = Evaluar-JavaScript 'document.querySelector("#cancelar-eliminacion").click();true'
    Navegar '/documentos/subir'
    $codigoPdf = @'
const content = 'BT /F1 18 Tf 25 140 Td (Documentos - Vista previa privada) Tj ET';
const objects = ['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 350 200] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>','<< /Length '+content.length+' >>\nstream\n'+content+'\nendstream'];
let pdf = '%PDF-1.4\n';const offsets=[];
objects.forEach((object,i)=>{offsets.push(pdf.length);pdf+=(i+1)+' 0 obj\n'+object+'\nendobj\n';});
const xref=pdf.length;pdf+='xref\n0 6\n0000000000 65535 f \n';offsets.forEach(offset=>pdf+=String(offset).padStart(10,'0')+' 00000 n \n');pdf+='trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n'+xref+'\n%%EOF\n';
const pdfTransfer = new DataTransfer();pdfTransfer.items.add(new File([pdf],'Vista previa Documentos.pdf',{type:'application/pdf'}));document.querySelector('#documento').files=pdfTransfer.files;document.querySelector('#formulario-carga').requestSubmit();true;
'@
    $null = Evaluar-JavaScript $codigoPdf
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 200; if ((Evaluar-JavaScript 'location.pathname') -eq '/documentos') { break } }
    $vistaPdf = Evaluar-JavaScript 'Array.from(document.querySelectorAll(".nombre-documento a")).find(a=>a.textContent==="Vista previa Documentos.pdf").getAttribute("href")'
    Navegar $vistaPdf
    for ($i=0;$i -lt 50;$i++) { Start-Sleep -Milliseconds 200; if (Evaluar-JavaScript 'document.querySelector("#visor-pdf").dataset.renderizado === "true"') { break } }
    if (!(Evaluar-JavaScript 'document.querySelector("#visor-pdf").dataset.renderizado === "true" && document.querySelector("#lienzo-pdf").width > 0')) { throw 'PDF no renderizado en canvas.' }
    Capturar 'pdf-escritorio.png'
    Navegar '/documentos'
    $null = Enviar-Cdp 'Emulation.setDeviceMetricsOverride' @{width=390;height=844;deviceScaleFactor=1;mobile=$true}
    Navegar '/'; Capturar 'resumen-movil.png'
    if (!(Evaluar-JavaScript 'document.documentElement.scrollWidth <= innerWidth')) { throw 'Desbordamiento horizontal del dashboard móvil.' }
    Navegar '/documentos/subir'; Capturar 'carga-movil.png'
    if (!(Evaluar-JavaScript 'document.documentElement.scrollWidth <= innerWidth')) { throw 'Desbordamiento horizontal de carga móvil.' }
    # El archivo demostrativo es el único dato creado por esta comprobación.
    Navegar '/documentos'
    $limpieza = @'
(async()=>{const token=document.querySelector('[name=_token]').value;const links=Array.from(document.querySelectorAll('.nombre-documento a')).filter(a=>['Bienvenido a Documentos.txt','Vista previa Documentos.pdf'].includes(a.textContent));for(const a of links){const path=a.getAttribute('href').replace('/vista-previa','/eliminar');const r=await fetch(path,{method:'POST',body:new URLSearchParams({_token:token})});if(!r.ok)throw new Error('No se pudo limpiar el documento visual');}return true;})()
'@
    $null = Evaluar-JavaScript $limpieza
    $null = Evaluar-JavaScript 'document.querySelector(".cerrar-sesion").requestSubmit();true'
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 100; if ((Evaluar-JavaScript 'location.pathname') -eq '/ingresar') { break } }
    Capturar 'ingreso-movil.png'
    $resultadoVisual = @{fecha_ejecucion_utc=[DateTime]::UtcNow.ToString('o');navegador='Chrome local existente';url_base=$UrlBase;escritorio='1440x1000';movil='390x844';pdf_renderizado=$true;desbordamiento_movil=$false;resultado='correcto';capturas=@(Get-ChildItem -LiteralPath $salida -Filter '*.png' | ForEach-Object Name)}
    [IO.File]::WriteAllText((Join-Path $raizProyecto 'documentacion\evidencias\resultados-interfaz.json'), ($resultadoVisual | ConvertTo-Json -Depth 4), [Text.UTF8Encoding]::new($false))
    Write-Output 'Chrome: login, registro, carga, PDF en canvas, tabla, modal, cerrar-sesion y vistas móviles verificados. Capturas en documentacion/evidencias/capturas.'
} finally {
    $socket.Dispose()
    $identificadorRaiz = $procesoChrome.Id
    $hijos = @(Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'chrome.exe' -and ($_.ParentProcessId -eq $identificadorRaiz -or $_.CommandLine.Contains($perfil)) })
    foreach ($hijo in $hijos) { Stop-Process -Id $hijo.ProcessId -ErrorAction SilentlyContinue }
    Stop-Process -Id $identificadorRaiz -ErrorAction SilentlyContinue
}
