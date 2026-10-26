<#
.SYNOPSIS
    Packs OneSuite - code, uploaded files, database and settings - into one
    zip to move to another computer or server.

.DESCRIPTION
    Run this on the OLD system when you are ready to move (the database is
    exported at that moment, so run it last). It creates:

        <Destination>\OneSuite-move-<date>.zip
            OneSuite\                  the project, without vendor/, node_modules/,
                                       logs, caches, old backups or the SQLite file
            OneSuite\.env              your settings, including APP_KEY
            onesuite-database.sql      a full MySQL export
            READ-ME-FIRST.txt          the steps to run on the new system

    The zip contains your data and your APP_KEY: keep it private, and never
    put it inside a web folder such as htdocs.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\export-for-move.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\export-for-move.ps1 -Destination D:\Moving -MysqlBin "C:\xampp\mysql\bin"
#>
param(
    [string]$Destination = (Join-Path $env:USERPROFILE 'Desktop'),
    [string]$MysqlBin = 'E:\vivek\mysql\bin'
)

$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $PSScriptRoot
$stamp = Get-Date -Format 'yyyy-MM-dd-HHmm'
$work = Join-Path $Destination "OneSuite-move-$stamp"
$zip = "$work.zip"

New-Item -ItemType Directory -Force -Path $Destination | Out-Null
$webRoot = (Split-Path -Parent $project).TrimEnd('\') + '\'
if (((Resolve-Path $Destination).Path.TrimEnd('\') + '\').StartsWith($webRoot, [StringComparison]::OrdinalIgnoreCase)) {
    throw "Choose a -Destination outside $webRoot - the package holds your database and APP_KEY, and anything in the web folder can be downloaded through the web server."
}

# ---- database settings from .env ----
$envFile = Join-Path $project '.env'
if (-not (Test-Path $envFile)) { throw ".env not found in $project" }
$settings = @{}
Get-Content $envFile | Where-Object { $_ -match '^\s*([A-Z_]+)\s*=\s*(.*)\s*$' } | ForEach-Object {
    $settings[$Matches[1]] = $Matches[2].Trim('"')
}
if ($settings['DB_CONNECTION'] -ne 'mysql') { throw "This script exports MySQL; .env says DB_CONNECTION=$($settings['DB_CONNECTION'])." }

$mysqldump = Join-Path $MysqlBin 'mysqldump.exe'
if (-not (Test-Path $mysqldump)) {
    $onPath = Get-Command mysqldump -ErrorAction SilentlyContinue
    if (-not $onPath) { throw "mysqldump not found. Pass -MysqlBin with the folder that contains mysqldump.exe." }
    $mysqldump = $onPath.Source
}

New-Item -ItemType Directory -Force -Path $work | Out-Null
Write-Host "Packing into $work" -ForegroundColor Cyan

# ---- 1. project files ----
# Left out: things the new system rebuilds (vendor, node_modules, caches,
# compiled views), this machine's logs and backups, dev leftovers, the old
# SQLite database, and the public/storage link (it points at a path on this
# PC; "php artisan storage:link" recreates it).
# Full paths only: a bare "vendor" would also drop public\vendor (the theme's
# CSS/JS) and resources\views\vendor (pagination views), which must travel.
$excludeDirs = @(
    (Join-Path $project 'vendor'),
    (Join-Path $project 'node_modules'),
    (Join-Path $project '.git'),
    (Join-Path $project '.idea'),
    (Join-Path $project '.vscode'),
    (Join-Path $project 'storage\logs'),
    (Join-Path $project 'storage\backups'),
    (Join-Path $project 'storage\app\claude-backups'),
    (Join-Path $project 'storage\framework\views'),
    (Join-Path $project 'storage\framework\cache'),
    (Join-Path $project 'storage\framework\sessions'),
    (Join-Path $project 'storage\framework\testing'),
    (Join-Path $project 'storage\pail'),
    (Join-Path $project 'public\storage')
)
$excludeFiles = @('cookies_*.txt', '.phpunit.result.cache', 'database.sqlite*', '*.log', '_*preview.html')

$target = Join-Path $work 'OneSuite'
& robocopy $project $target /E /XJ /NFL /NDL /NJH /NJS /NP /R:1 /W:1 /XD $excludeDirs /XF $excludeFiles | Out-Null
if ($LASTEXITCODE -ge 8) { throw "Copying files failed (robocopy exit code $LASTEXITCODE)." }

# Laravel expects these folders to exist, even empty.
foreach ($dir in 'storage\logs', 'storage\framework\views', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\app\public', 'storage\app\private') {
    New-Item -ItemType Directory -Force -Path (Join-Path $target $dir) | Out-Null
}
Write-Host "  project files copied"

# ---- 2. database ----
$dumpFile = Join-Path $work 'onesuite-database.sql'
$dbHost = if ($settings['DB_HOST']) { $settings['DB_HOST'] } else { '127.0.0.1' }
$dbPort = if ($settings['DB_PORT']) { $settings['DB_PORT'] } else { '3306' }
# Each argument in its own parentheses: in a PowerShell list "," binds tighter than "+".
$dumpArgs = @(("--user=" + $settings['DB_USERNAME']), ("--host=" + $dbHost), ("--port=" + $dbPort),
    '--single-transaction', '--routines', '--default-character-set=utf8mb4', ("--result-file=" + $dumpFile), $settings['DB_DATABASE'])
if ($settings['DB_PASSWORD']) { $dumpArgs = @(("--password=" + $settings['DB_PASSWORD'])) + $dumpArgs }
& $mysqldump @dumpArgs
if ($LASTEXITCODE -ne 0 -or -not (Test-Path $dumpFile)) { throw "Database export failed - is MySQL running?" }
Write-Host ("  database exported ({0:N1} MB)" -f ((Get-Item $dumpFile).Length / 1MB))

# ---- 3. instructions ----
@"
OneSuite - moving to a new system
=================================
Exported $(Get-Date -Format 'yyyy-MM-dd HH:mm') from $env:COMPUTERNAME.

Full guide: OneSuite\README.md, section "Moving OneSuite to another system".

On the new system (PHP 8.3+, Composer, MySQL/MariaDB):

1. Put the OneSuite folder where the web server or "php artisan serve" will run it.
2. Create an empty database, utf8mb4:
       CREATE DATABASE onesuite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
3. Import the data:
       mysql -u root -p onesuite < onesuite-database.sql
4. In OneSuite\.env change DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD
   and APP_URL for the new system. Do NOT change APP_KEY - saved mail
   passwords and platform settings are encrypted with it.
5. In the OneSuite folder:
       composer install --no-dev --optimize-autoloader
       php artisan migrate --force
       php artisan storage:link
6. Start it (php artisan serve, or point the web server at OneSuite\public)
   and sign in with the same logins as before.

Keep this package private: it contains your data and APP_KEY.
"@ | Set-Content -Path (Join-Path $work 'READ-ME-FIRST.txt') -Encoding UTF8

# ---- 4. zip ----
if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path (Join-Path $work '*') -DestinationPath $zip
Remove-Item $work -Recurse -Force

Write-Host ("Done: {0} ({1:N1} MB)" -f $zip, ((Get-Item $zip).Length / 1MB)) -ForegroundColor Green
Write-Host "It contains your data and APP_KEY - keep it private." -ForegroundColor Yellow
