# Backup and restore rehearsal for a SANAM instance (plan sections 11 and 12).
#
#   powershell -ExecutionPolicy Bypass -File backup_restore_test.ps1
#
# 1. Dumps the database (single transaction, no locks on InnoDB tables).
# 2. Archives files/ (minus caches and sessions) and config/ (holds sanamcrypt.key - keep it safe).
# 3. Restores the dump into a separate database and restores files and config to a separate folder.
# 4. Compares CHECKSUM TABLE for every table: source and restore must match exactly.
# The restore rehearsal never touches the source database.

$ErrorActionPreference = 'Stop'
$bin      = 'C:\Program Files\MariaDB 11.8\bin'
$sanam     = 'D:\Learn\sanam-core'
$source   = 'sanam'
$target   = 'sanam_restore'
$stamp    = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup   = "D:\Learn\backups\sanam-$stamp"
$restore  = 'D:\Learn\sanam-restore'

New-Item -ItemType Directory -Path $backup -Force | Out-Null

# 1. Database dump
$dump = Join-Path $backup "$source.sql"
& "$bin\mariadb-dump.exe" -u root --single-transaction --routines --triggers --events $source | Out-File -FilePath $dump -Encoding utf8
if ($LASTEXITCODE -ne 0) { throw 'database dump failed' }

# 2. Files and config
$files_tgz = Join-Path $backup 'files.tar.gz'
$config_tgz = Join-Path $backup 'config.tar.gz'
Push-Location $sanam
tar -czf $files_tgz --exclude=files/_cache --exclude=files/_tmp --exclude=files/_sessions files
tar -czf $config_tgz config
Pop-Location

# Checksums so the copy can be verified later.
Get-ChildItem $backup -File | ForEach-Object {
    $hash = (Get-FileHash $_.FullName -Algorithm SHA256).Hash
    "$hash  $($_.Name)"
} | Set-Content (Join-Path $backup 'SHA256SUMS.txt') -Encoding ascii

# 3. Restore rehearsal into separate database and folder
& "$bin\mariadb.exe" -u root -e "DROP DATABASE IF EXISTS $target; CREATE DATABASE $target CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Get-Content $dump -Raw | & "$bin\mariadb.exe" -u root $target
if ($LASTEXITCODE -ne 0) { throw 'restore into the rehearsal database failed' }

if (Test-Path $restore) { Remove-Item -LiteralPath $restore -Recurse -Force }
New-Item -ItemType Directory -Path $restore | Out-Null
tar -xzf $files_tgz -C $restore
tar -xzf $config_tgz -C $restore

# 4. Compare every table between source and restore
$tables = & "$bin\mariadb.exe" -u root -N -B $source -e 'SHOW TABLES'
$mismatch = 0
foreach ($t in $tables) {
    $a = (& "$bin\mariadb.exe" -u root -N -B $source -e "CHECKSUM TABLE ``$t``" | Select-Object -Last 1).Split("`t")[1]
    $b = (& "$bin\mariadb.exe" -u root -N -B $target -e "CHECKSUM TABLE ``$t``" | Select-Object -Last 1).Split("`t")[1]
    if ($a -ne $b) { $mismatch++; Write-Output "MISMATCH $t ($a vs $b)" }
}
Write-Output ("tables compared: {0}, mismatches: {1}" -f @($tables).Count, $mismatch)
Write-Output "backup folder: $backup"
Write-Output "restore folder: $restore"
if ($mismatch -gt 0) { exit 1 }
