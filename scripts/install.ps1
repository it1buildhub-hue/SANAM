# Installs SANAM IT Assets from this repository onto this Windows PC.
#
#   powershell -NoProfile -ExecutionPolicy Bypass -File scripts\install.ps1
#   powershell -NoProfile -ExecutionPolicy Bypass -File scripts\install.ps1 -Demo
#
# Needs PHP 8.3 and a running MariaDB. Everything created for this copy (settings, uploaded files and
# the login list) goes into the data folder, which git ignores. -Demo builds a separate copy on database
# sanam_demo with made-up sample data, on port 8083.

param(
    [string]$DbName,
    [string]$DbUser,
    [int]$Port,
    [string]$DataDir,
    [string]$DbHost = '127.0.0.1',
    [string]$DbRootPassword = '',
    [string]$MysqlExe = '',
    [switch]$Demo
)

$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot
$core = Join-Path $repo 'core'
$pluginSource = Join-Path $repo 'plugin\sanamassets'

if (-not $DbName) { $DbName = $(if ($Demo) { 'sanam_demo' } else { 'sanam' }) }
if (-not $DbUser) { $DbUser = "$($DbName)_app" }
if (-not $Port) { $Port = $(if ($Demo) { 8083 } else { 8081 }) }
if (-not $DataDir) { $DataDir = Join-Path $repo $(if ($Demo) { 'data\demo' } else { 'data\main' }) }

$php = (Get-Command php -ErrorAction Stop).Source
if (-not $MysqlExe) {
    $found = Get-Command mysql -ErrorAction SilentlyContinue
    if ($found) { $MysqlExe = $found.Source } else {
        $MysqlExe = (Get-ChildItem 'C:\Program Files\MariaDB*\bin\mysql.exe' | Select-Object -Last 1).FullName
    }
}
if (-not $MysqlExe -or -not (Test-Path $MysqlExe)) { throw 'mysql.exe was not found. Pass it with -MysqlExe.' }

$ssl = Join-Path (Split-Path -Parent $php) 'extras\ssl\openssl.cnf'
if (Test-Path $ssl) { $env:OPENSSL_CONF = $ssl }

$config = Join-Path $DataDir 'config'
$files = Join-Path $DataDir 'files'
if (Test-Path (Join-Path $config 'config_db.php')) {
    throw "$config already holds an installed copy. Use a new -DataDir, or use scripts\start.ps1 to run it."
}
$folders = @('_cache', '_cron', '_dumps', '_graphs', '_inventories', '_locales', '_lock', '_log', '_pictures',
    '_plugins', '_rss', '_sessions', '_themes', '_tmp', '_uploads', '_documents')
New-Item -ItemType Directory -Force -Path $config | Out-Null
foreach ($f in $folders) { New-Item -ItemType Directory -Force -Path (Join-Path $files $f) | Out-Null }
$env:SANAM_CONFIG_DIR = $config
$env:SANAM_VAR_DIR = $files
$env:SANAM_ROOT = $core -replace '\\', '/'

function Invoke-Php {
    & $php @args
    if ($LASTEXITCODE -ne 0) { throw "php $($args[0]) $($args[1]) failed with exit code $LASTEXITCODE" }
}

# 1. Database and its own account, with a new random password.
Write-Output 'Creating the database and its account...'
$bytes = New-Object byte[] 24
[Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
$dbPassword = ([Convert]::ToBase64String($bytes) -replace '[+/=]', 'x')
$sql = "CREATE DATABASE ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; " +
    "CREATE USER '$DbUser'@'localhost' IDENTIFIED BY '$dbPassword'; " +
    "CREATE USER '$DbUser'@'127.0.0.1' IDENTIFIED BY '$dbPassword'; " +
    "GRANT ALL PRIVILEGES ON ``$DbName``.* TO '$DbUser'@'localhost'; " +
    "GRANT ALL PRIVILEGES ON ``$DbName``.* TO '$DbUser'@'127.0.0.1'; " +
    "GRANT SELECT ON mysql.time_zone_name TO '$DbUser'@'localhost'; " +
    "GRANT SELECT ON mysql.time_zone_name TO '$DbUser'@'127.0.0.1';"
$rootArgs = @('-h', $DbHost, '-u', 'root')
if ($DbRootPassword) { $rootArgs += "--password=$DbRootPassword" }
& $MysqlExe @rootArgs -e $sql
if ($LASTEXITCODE -ne 0) { throw 'Could not create the database. Is MariaDB running, and does the database already exist?' }

Set-Location $core

# 2. Tables and default data.
Write-Output 'Creating the tables (about a minute)...'
Invoke-Php bin/console database:install -H $DbHost -d $DbName -u $DbUser -p $dbPassword -L en_GB --no-telemetry -n -q -r -f

# 3. Plugins. The deployed copy leaves out tests, tools and theme sources.
Write-Output 'Installing the plugins...'
$pluginTarget = Join-Path $core 'plugins\sanamassets'
if (Test-Path $pluginTarget) { Remove-Item -LiteralPath $pluginTarget -Recurse -Force }
New-Item -ItemType Directory -Path $pluginTarget | Out-Null
Get-ChildItem -LiteralPath $pluginSource -Force |
    Where-Object { $_.Name -notin @('tests', 'tools', 'theme', 'README.md') } |
    Copy-Item -Destination $pluginTarget -Recurse -Force
foreach ($p in @('sanamassets', 'fields', 'datainjection')) {
    Invoke-Php bin/console plugin:install $p -n
    Invoke-Php bin/console plugin:activate $p -n
}

# 4. SANAM data: states, asset types, roles, fields, companies, accounts.
Write-Output 'Adding SANAM data...'
$tools = Join-Path $pluginSource 'tools'
Invoke-Php (Join-Path $tools 'seed_sanam.php')
Invoke-Php (Join-Path $tools 'seed_fields.php')
Invoke-Php (Join-Path $tools 'create_companies.php')
$logins = Join-Path $DataDir 'LOGINS.txt'
& $php (Join-Path $tools 'create_accounts.php') | Out-File -FilePath $logins -Encoding utf8
if ($LASTEXITCODE -ne 0) { throw 'create_accounts.php failed.' }
if ($Demo) { Invoke-Php (Join-Path $tools 'seed_demo.php') }

# 5. Brand colours and logo.
Copy-Item -LiteralPath (Join-Path $pluginSource 'theme\sanam.scss') -Destination (Join-Path $files '_themes\sanam.scss') -Force
Invoke-Php bin/console config:set palette sanam
Invoke-Php bin/console cache:clear

Write-Output ''
Write-Output 'Installed.'
Write-Output "  Logins:   $logins  (keep this file private)"
Write-Output "  Start it: powershell -NoProfile -ExecutionPolicy Bypass -File scripts\start.ps1 -DataDir `"$DataDir`" -Port $Port"
Write-Output "  Address:  http://127.0.0.1:$Port"
