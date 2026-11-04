# Starts one installed copy of SANAM IT Assets on this PC. Run it again after a reboot.
#
#   powershell -NoProfile -ExecutionPolicy Bypass -File scripts\start.ps1
#   powershell -NoProfile -ExecutionPolicy Bypass -File scripts\start.ps1 -DataDir data\demo -Port 8083

param(
    [string]$DataDir,
    [int]$Port = 8081,
    [string]$MariadbExe = ''
)

$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot
if (-not $DataDir) { $DataDir = 'data\main' }
if (-not [IO.Path]::IsPathRooted($DataDir)) { $DataDir = Join-Path $repo $DataDir }
if (-not (Test-Path (Join-Path $DataDir 'config\config_db.php'))) {
    throw "No installed copy in $DataDir. Run scripts\install.ps1 first."
}

function Test-Port([int]$Number) {
    $client = New-Object Net.Sockets.TcpClient
    try { $client.Connect('127.0.0.1', $Number); return $true } catch { return $false } finally { $client.Close() }
}

if (-not (Test-Port 3306)) {
    if (-not $MariadbExe) {
        $MariadbExe = (Get-ChildItem 'C:\Program Files\MariaDB*\bin\mariadbd.exe' | Select-Object -Last 1).FullName
    }
    if (-not $MariadbExe) { throw 'MariaDB is not running and mariadbd.exe was not found. Pass it with -MariadbExe.' }
    Write-Output 'Starting MariaDB...'
    Start-Process -FilePath $MariadbExe -ArgumentList '--console' -WindowStyle Hidden
    for ($i = 0; $i -lt 30 -and -not (Test-Port 3306); $i++) { Start-Sleep -Seconds 1 }
    if (-not (Test-Port 3306)) { throw 'MariaDB did not start within 30 seconds.' }
}

if (Test-Port $Port) {
    Write-Output "Port $Port is already in use. If this copy is running, open http://127.0.0.1:$Port"
    exit 0
}

$php = (Get-Command php -ErrorAction Stop).Source
$public = Join-Path $repo 'core\public'
$env:SANAM_CONFIG_DIR = Join-Path $DataDir 'config'
$env:SANAM_VAR_DIR = Join-Path $DataDir 'files'
$ssl = Join-Path (Split-Path -Parent $php) 'extras\ssl\openssl.cnf'
if (Test-Path $ssl) { $env:OPENSSL_CONF = $ssl }

# Bound to 127.0.0.1 on purpose: this built-in server is for one PC and has no HTTPS.
Start-Process -FilePath $php -WindowStyle Hidden -WorkingDirectory (Join-Path $repo 'core') -ArgumentList @(
    '-d', 'max_execution_time=300', '-S', "127.0.0.1:$Port", '-t', "`"$public`"", "`"$(Join-Path $public 'index.php')`""
)
for ($i = 0; $i -lt 15 -and -not (Test-Port $Port); $i++) { Start-Sleep -Seconds 1 }
if (-not (Test-Port $Port)) { throw "The web server did not start on port $Port." }
Write-Output "Running: http://127.0.0.1:$Port"
