[CmdletBinding()]
param([string]$BaseUrl = 'http://127.0.0.1:8088', [int]$DebugPort = 9228)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
if (!(Test-Path -LiteralPath $chrome)) { throw 'Chrome no está instalado; no se instala automáticamente.' }
if (Get-NetTCPConnection -LocalPort $DebugPort -State Listen -ErrorAction SilentlyContinue) { throw 'Puerto de depuración ocupado.' }
$runtime = Join-Path $PSScriptRoot '.runtime'
$profile = Join-Path $runtime 'chrome-profile'
$output = Join-Path $projectRoot 'docs\screenshots'
$null = New-Item -ItemType Directory -Force -Path $output
$chromeProcess = Start-Process -FilePath $chrome -ArgumentList @('--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check',"--remote-debugging-port=$DebugPort",'--remote-debugging-address=127.0.0.1',('--user-data-dir="' + $profile + '"'),'about:blank') -WindowStyle Hidden -PassThru
$socket = [Net.WebSockets.ClientWebSocket]::new()
$script:messageId = 0
function Send-Cdp([string]$method, $parameters = @{}) {
    $script:messageId++
    $id = $script:messageId
    $json = @{id=$id;method=$method;params=$parameters} | ConvertTo-Json -Depth 12 -Compress
    $buffer = [Text.Encoding]::UTF8.GetBytes($json)
    $socket.SendAsync([ArraySegment[byte]]::new($buffer),[Net.WebSockets.WebSocketMessageType]::Text,$true,[Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    do {
        $stream = [IO.MemoryStream]::new()
        do {
            $receivedBuffer = New-Object byte[] 65536
            $received = $socket.ReceiveAsync([ArraySegment[byte]]::new($receivedBuffer),[Threading.CancellationToken]::None).GetAwaiter().GetResult()
            $stream.Write($receivedBuffer,0,$received.Count)
        } while (!$received.EndOfMessage)
        $response = [Text.Encoding]::UTF8.GetString($stream.ToArray()) | ConvertFrom-Json
        $stream.Dispose()
    } while ($response.id -ne $id)
    if ($response.error) { throw ($response.error | ConvertTo-Json -Compress) }
    return $response.result
}
function Eval-Js([string]$expression) {
    $result = Send-Cdp 'Runtime.evaluate' @{expression=$expression;returnByValue=$true;awaitPromise=$true}
    if ($result.exceptionDetails) { throw ($result.exceptionDetails | ConvertTo-Json -Depth 6) }
    return $result.result.value
}
function Navigate([string]$path) {
    $null = Send-Cdp 'Page.navigate' @{url=($BaseUrl + $path)}
    for ($i=0;$i -lt 30;$i++) {
        Start-Sleep -Milliseconds 100
        if ((Eval-Js 'document.readyState === "complete" && location.pathname') -eq $path) { return }
    }
    throw "Navegación no completada: $path"
}
function Shot([string]$name) {
    $null = Eval-Js 'document.fonts.ready.then(() => true)'
    $capture = Send-Cdp 'Page.captureScreenshot' @{format='png';captureBeyondViewport=$false}
    [IO.File]::WriteAllBytes((Join-Path $output $name),[Convert]::FromBase64String($capture.data))
}
try {
    $pages = $null
    for ($i=0;$i -lt 30;$i++) {
        Start-Sleep -Milliseconds 200
        try { $pages = Invoke-RestMethod "http://127.0.0.1:$DebugPort/json/list" -TimeoutSec 2; break } catch { }
    }
    if (!$pages) { throw 'No se pudo conectar con Chrome local.' }
    $page = @($pages | Where-Object type -eq 'page')[0]
    $socket.ConnectAsync([Uri]$page.webSocketDebuggerUrl,[Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    $null = Send-Cdp 'Page.enable'
    $null = Send-Cdp 'Emulation.setDeviceMetricsOverride' @{width=1440;height=1000;deviceScaleFactor=1;mobile=$false}
    Navigate '/login'
    if (!(Eval-Js '!!document.querySelector("input[name=login]") && getComputedStyle(document.querySelector(".btn-primary")).backgroundColor === "rgb(37, 99, 235)"')) { throw 'Formulario o CSS no disponible.' }
    Shot 'login-desktop.png'
    Navigate '/register'; Shot 'registro-desktop.png'
    Navigate '/login'
    $null = Eval-Js 'document.querySelector("[name=login]").value="prueba1";document.querySelector("[name=password]").value="DemoSegura2026!A";document.querySelector(".auth-form").requestSubmit();true'
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 200; if ((Eval-Js 'location.pathname') -eq '/') { break } }
    if ((Eval-Js 'location.pathname') -ne '/') { throw 'No se inició sesión desde el navegador.' }
    Shot 'dashboard-desktop.png'
    Navigate '/files/upload'; Shot 'upload-desktop.png'
    $null = Eval-Js 'const f=new File(["Documento demostrativo de ADMOSA.\nSolo prueba1 puede acceder a este archivo.\n"],"Bienvenido a ADMOSA.txt",{type:"text/plain"}); const dt=new DataTransfer();dt.items.add(f);document.querySelector("#file").files=dt.files;document.querySelector("#file").dispatchEvent(new Event("change"));true'
    if (!(Eval-Js 'document.querySelector("#selected-file").textContent.includes("Bienvenido a ADMOSA.txt")')) { throw 'Selección de archivo no actualizada.' }
    $null = Eval-Js 'document.querySelector("#upload-form").requestSubmit();true'
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 200; if ((Eval-Js 'location.pathname') -eq '/files') { break } }
    if (!(Eval-Js 'location.pathname === "/files" && document.body.textContent.includes("Bienvenido a ADMOSA.txt")')) { throw 'Carga desde navegador fallida.' }
    Shot 'files-desktop.png'
    $null = Eval-Js 'document.querySelector("[data-delete-id]").click();true'
    if (!(Eval-Js 'document.querySelector("#delete-dialog").open && document.activeElement.id === "cancel-delete"')) { throw 'Modal o foco de confirmación fallido.' }
    Shot 'delete-dialog.png'
    $null = Eval-Js 'document.querySelector("#cancel-delete").click();true'
    Navigate '/files/upload'
    $pdfScript = @'
const content = 'BT /F1 18 Tf 25 140 Td (ADMOSA - Vista previa privada) Tj ET';
const objects = ['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 350 200] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>','<< /Length '+content.length+' >>\nstream\n'+content+'\nendstream'];
let pdf = '%PDF-1.4\n';const offsets=[];
objects.forEach((object,i)=>{offsets.push(pdf.length);pdf+=(i+1)+' 0 obj\n'+object+'\nendobj\n';});
const xref=pdf.length;pdf+='xref\n0 6\n0000000000 65535 f \n';offsets.forEach(offset=>pdf+=String(offset).padStart(10,'0')+' 00000 n \n');pdf+='trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n'+xref+'\n%%EOF\n';
const pdfTransfer = new DataTransfer();pdfTransfer.items.add(new File([pdf],'Vista previa ADMOSA.pdf',{type:'application/pdf'}));document.querySelector('#file').files=pdfTransfer.files;document.querySelector('#upload-form').requestSubmit();true;
'@
    $null = Eval-Js $pdfScript
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 200; if ((Eval-Js 'location.pathname') -eq '/files') { break } }
    $pdfPreview = Eval-Js 'Array.from(document.querySelectorAll(".file-name a")).find(a=>a.textContent==="Vista previa ADMOSA.pdf").getAttribute("href")'
    Navigate $pdfPreview
    for ($i=0;$i -lt 50;$i++) { Start-Sleep -Milliseconds 200; if (Eval-Js 'document.querySelector("#pdf-viewer").dataset.rendered === "true"') { break } }
    if (!(Eval-Js 'document.querySelector("#pdf-viewer").dataset.rendered === "true" && document.querySelector("#pdf-canvas").width > 0')) { throw 'PDF no renderizado en canvas.' }
    Shot 'pdf-preview-desktop.png'
    Navigate '/files'
    $null = Send-Cdp 'Emulation.setDeviceMetricsOverride' @{width=390;height=844;deviceScaleFactor=1;mobile=$true}
    Navigate '/'; Shot 'dashboard-mobile.png'
    if (!(Eval-Js 'document.documentElement.scrollWidth <= innerWidth')) { throw 'Desbordamiento horizontal del dashboard móvil.' }
    Navigate '/files/upload'; Shot 'upload-mobile.png'
    if (!(Eval-Js 'document.documentElement.scrollWidth <= innerWidth')) { throw 'Desbordamiento horizontal de carga móvil.' }
    # El archivo demostrativo es el único dato creado por esta comprobación.
    Navigate '/files'
    $cleanup = @'
(async()=>{const token=document.querySelector('[name=_token]').value;const links=Array.from(document.querySelectorAll('.file-name a')).filter(a=>['Bienvenido a ADMOSA.txt','Vista previa ADMOSA.pdf'].includes(a.textContent));for(const a of links){const path=a.getAttribute('href').replace('/preview','/delete');const r=await fetch(path,{method:'POST',body:new URLSearchParams({_token:token})});if(!r.ok)throw new Error('No se pudo limpiar el documento visual');}return true;})()
'@
    $null = Eval-Js $cleanup
    $null = Eval-Js 'document.querySelector(".logout").requestSubmit();true'
    for ($i=0;$i -lt 30;$i++) { Start-Sleep -Milliseconds 100; if ((Eval-Js 'location.pathname') -eq '/login') { break } }
    Shot 'login-mobile.png'
    $visualResult = @{executed_at_utc=[DateTime]::UtcNow.ToString('o');browser='Chrome local existente';base_url=$BaseUrl;desktop='1440x1000';mobile='390x844';pdf_canvas_rendered=$true;mobile_horizontal_overflow=$false;result='passed';screenshots=@(Get-ChildItem -LiteralPath $output -Filter '*.png' | ForEach-Object Name)}
    [IO.File]::WriteAllText((Join-Path $projectRoot 'docs\visual-results.json'), ($visualResult | ConvertTo-Json -Depth 4), [Text.UTF8Encoding]::new($false))
    Write-Output 'Chrome: login, registro, carga, PDF en canvas, tabla, modal, logout y vistas móviles verificados. Capturas en docs/screenshots.'
} finally {
    $socket.Dispose()
    $rootPid = $chromeProcess.Id
    $children = @(Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'chrome.exe' -and ($_.ParentProcessId -eq $rootPid -or $_.CommandLine.Contains($profile)) })
    foreach ($child in $children) { Stop-Process -Id $child.ProcessId -ErrorAction SilentlyContinue }
    Stop-Process -Id $rootPid -ErrorAction SilentlyContinue
}
