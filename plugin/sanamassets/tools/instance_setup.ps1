# Builds the SANAM staging and test instances from the rebranded core (plan section 12).
#
#   powershell -NoProfile -ExecutionPolicy Bypass -File instance_setup.ps1 -Instance staging|test
#
# Staging = core folder config and files (the default). Test = same code with
# SANAM_CONFIG_DIR / SANAM_VAR_DIR pointing at D:\Learn\sanam-test (config and files).
# Database passwords are read from D:\Learn\sanam-instances\<instance>\db-password.txt and are
# written only into the instance config by database:install.

param(
    [Parameter(Mandatory = $true)][ValidateSet('staging', 'test')] [string]$Instance
)

$ErrorActionPreference = 'Stop'
$core    = 'D:\Learn\sanam-core'
$php     = 'C:\Users\buildhub\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
$plugins = 'D:\Learn\sanamassets'
$base    = 'D:\Learn\sanam-instances'

$env:OPENSSL_CONF = 'C:\Users\buildhub\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\extras\ssl\openssl.cnf'

if ($Instance -eq 'test') {
    $env:SANAM_CONFIG_DIR = 'D:\Learn\sanam-test\config'
    $env:SANAM_VAR_DIR    = 'D:\Learn\sanam-test\files'
    $db   = 'sanam_test'
    $user = 'sanam_test_app'
} else {
    Remove-Item Env:SANAM_CONFIG_DIR -ErrorAction SilentlyContinue
    Remove-Item Env:SANAM_VAR_DIR -ErrorAction SilentlyContinue
    $db   = 'sanam'
    $user = 'sanam_app'
}
$pw = (Get-Content "$base\$($Instance)\db-password.txt" -Raw).Trim()

Set-Location $core

# 1. Schema and default data.
& $php bin/console database:install -H 127.0.0.1 -d $db -u $user -p $pw -L en_GB --no-telemetry -n -q -r -f
if ($LASTEXITCODE -ne 0) { throw "database:install failed for $db" }

# 2. Plugins: copy from the plugin repo, then install and activate.
$pluginDir = Join-Path $core 'plugins\sanamassets'
if (Test-Path $pluginDir) { Remove-Item -LiteralPath $pluginDir -Recurse -Force }
New-Item -ItemType Directory -Path $pluginDir | Out-Null
Get-ChildItem -Path $plugins -Force | Where-Object { $_.Name -notin @('.git', 'tests', 'tools', 'theme', 'README.md') } |
    Copy-Item -Destination $pluginDir -Recurse -Force
foreach ($p in @('sanamassets', 'fields', 'datainjection')) {
    & $php bin/console plugin:install $p -n
    & $php bin/console plugin:activate $p -n
}

Write-Output "$Instance instance ready on database $db"
