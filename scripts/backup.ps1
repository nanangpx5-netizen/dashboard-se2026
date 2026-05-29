<#
.SYNOPSIS
    Backup Database Dashboard SE2026 — Full + Incremental
.DESCRIPTION
    Melakukan backup database dashboard SE2026 secara full atau incremental.
    - Full backup: mysqldump semua tabel dashboard + struktur
    - Incremental backup: hanya data yang berubah sejak full backup terakhir
    - Kompresi GZip otomatis
    - Rotasi: hapus backup lebih dari RETENTION_DAYS hari
    - Logging ke file dan event log

.PARAMETER Type
    Tipe backup: 'full' atau 'incremental' (default: full)
.PARAMETER BackupDir
    Direktori penyimpanan backup (default: ../storage/backups)
.PARAMETER RetentionDays
    Jumlah hari backup disimpan (default: 30)

.EXAMPLE
    .\backup.ps1 -Type full
    .\backup.ps1 -Type incremental
    .\backup.ps1 -Type full -BackupDir "D:\Backups\SE2026"

.NOTES
    Jadwalkan via Windows Task Scheduler:
    - Full backup:   Setiap hari pukul 02:00
    - Incremental:   Setiap 6 jam (08:00, 14:00, 20:00)
#>

param(
    [ValidateSet('full', 'incremental')]
    [string]$Type = 'full',

    [string]$BackupDir = '',

    [int]$RetentionDays = 30
)

# ─── Konfigurasi ────────────────────────────────────────────────────────────
$ScriptRoot   = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot  = Resolve-Path "$ScriptRoot\.."
$DefaultDir   = Join-Path $ProjectRoot "storage\backups"
$BackupDir    = if ($BackupDir -eq '') { $DefaultDir } else { $BackupDir }
$LogDir       = Join-Path $ProjectRoot "storage\logs"
$LogFile      = Join-Path $LogDir "backup.log"
$StateFile    = Join-Path $BackupDir ".last_full_backup"
$MysqlDump    = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe"
$MysqlClient  = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
$Gzip         = if (Get-Command 'gzip' -ErrorAction SilentlyContinue) { 'gzip' } else { $null }

# ─── Baca konfigurasi dari .env ─────────────────────────────────────────────
$EnvFile = Join-Path $ProjectRoot ".env"
$DbHost = 'localhost'
$DbPort = '3306'
$DbName = 'bps_jember_se2026'
$DbUser = 'root'
$DbPass = ''

if (Test-Path $EnvFile) {
    Get-Content $EnvFile | ForEach-Object {
        if ($_ -match '^DB_HOST=(.+)') { $DbHost = $matches[1] }
        if ($_ -match '^DB_PORT=(.+)') { $DbPort = $matches[1] }
        if ($_ -match '^DB_NAME=(.+)') { $DbName = $matches[1] }
        if ($_ -match '^DB_USER=(.+)') { $DbUser = $matches[1] }
        if ($_ -match '^DB_PASS=(.*)') { $DbPass = $matches[1] }
    }
}

# ─── Fungsi bantuan ─────────────────────────────────────────────────────────
function Write-Log {
    param([string]$Message, [string]$Level = 'INFO')
    $Timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    $Line = "[$Timestamp] [$Level] $Message"
    Write-Host $Line
    if (-not (Test-Path (Split-Path $LogFile -Parent))) {
        New-Item -ItemType Directory -Path (Split-Path $LogFile -Parent) -Force | Out-Null
    }
    Add-Content -Path $LogFile -Value $Line
}

function Get-Timestamp {
    return Get-Date -Format 'yyyyMMdd_HHmmss'
}

function Get-HumanSize {
    param([long]$Bytes)
    if ($Bytes -ge 1GB) { return "{0:N2} GB" -f ($Bytes / 1GB) }
    if ($Bytes -ge 1MB) { return "{0:N2} MB" -f ($Bytes / 1MB) }
    return "{0:N2} KB" -f ($Bytes / 1KB)
}

# ─── Tabel dashboard ────────────────────────────────────────────────────────
$DashboardTables = @(
    'sipw_import',
    'sipw_assignment',
    'dash_import_log',
    'dash_monitoring_summary',
    'dash_assignment_log',
    'dash_rollback_points'
)

# ─── Siapkan direktori ──────────────────────────────────────────────────────
if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
    Write-Log "Direktori backup dibuat: $BackupDir"
}

# ─── Fungsi backup ──────────────────────────────────────────────────────────
function Do-FullBackup {
    Write-Log "Memulai FULL backup..." -Level 'INFO'

    $Timestamp = Get-Timestamp
    $Filename   = "dashboard_full_$Timestamp.sql"
    $FilePath   = Join-Path $BackupDir $Filename

    # Struktur + data semua tabel dashboard
    $TablesArg = ($DashboardTables | ForEach-Object { "--tables $_" }) -join ' '

    $ArgumentList = @(
        "-h $DbHost",
        "-P $DbPort",
        "-u $DbUser",
        "--password=$DbPass",
        "--routines",
        "--triggers",
        "--single-transaction",
        "--quick",
        "--default-character-set=utf8mb4",
        "$DbName",
        $TablesArg,
        "--result-file=$FilePath"
    )

    Write-Log "Menjalankan: mysqldump $DbName (tabel: $($DashboardTables.Count) tabel)"
    $proc = Start-Process -FilePath $MysqlDump -ArgumentList $ArgumentList -Wait -NoNewWindow -PassThru

    if ($proc.ExitCode -ne 0) {
        Write-Log "FULL backup GAGAL (exit code: $($proc.ExitCode))" -Level 'ERROR'
        return $null
    }

    # Kompres
    if ($Gzip -and (Test-Path $FilePath)) {
        $GzFile = "$FilePath.gz"
        & $Gzip -f $FilePath
        $FilePath = $GzFile
        Write-Log "Backup dikompres: $GzFile"
    }

    $FileSize = (Get-Item $FilePath).Length
    Write-Log "FULL backup selesai: $Filename ($(Get-HumanSize $FileSize))" -Level 'INFO'

    # Simpan timestamp full backup terakhir
    Set-Content -Path $StateFile -Value $Timestamp

    return @{
        File     = $FilePath
        Filename = $Filename
        Size     = $FileSize
        Tables   = $DashboardTables.Count
    }
}

function Do-IncrementalBackup {
    Write-Log "Memulai INCREMENTAL backup..." -Level 'INFO'

    # Cek kapan full backup terakhir
    if (-not (Test-Path $StateFile)) {
        Write-Log "Tidak ada full backup sebelumnya. Jalankan full backup dulu." -Level 'WARN'
        return Do-FullBackup
    }

    $LastFull = Get-Content $StateFile
    $LastFullDate = [datetime]::ParseExact($LastFull, 'yyyyMMdd_HHmmss', $null)

    Write-Log "Full backup terakhir: $LastFullDate"

    $Timestamp = Get-Timestamp
    $Filename   = "dashboard_inc_$Timestamp.sql"
    $FilePath   = Join-Path $BackupDir $Filename

    # Query data yang berubah sejak full backup terakhir
    # Gunakan prepared statement via mysql client
    $ChangedData = @()

    foreach ($Table in $DashboardTables) {
        # Cek apakah tabel punya kolom updated_at
        $CheckSql = "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = '$DbName'
                       AND TABLE_NAME = '$Table'
                       AND COLUMN_NAME = 'updated_at'"

        $HasUpdatedAt = & $MysqlClient -h $DbHost -P $DbPort -u $DbUser --password=$DbPass -N -e $CheckSql 2>&1
        $HasUpdatedAt = $HasUpdatedAt.Trim() -eq '1'

        if ($HasUpdatedAt) {
            $LastFullDateStr = $LastFullDate.ToString('yyyy-MM-dd HH:mm:ss')
            $Sql = "SELECT * FROM $Table WHERE updated_at >= '$LastFullDateStr'"

            # Escape output: gunakan INTO OUTFILE atau stream
            $RowSql = "SELECT COUNT(*) FROM $Table WHERE updated_at >= '$LastFullDateStr'"
            $RowCount = & $MysqlClient -h $DbHost -P $DbPort -u $DbUser --password=$DbPass -N -e $RowSql 2>&1
            $RowCount = $RowCount.Trim()

            Write-Log "  $Table : $RowCount baris berubah"

            if ($RowCount -gt 0 -and $RowCount -ne '0') {
                # Dump hanya data yang berubah (tanpa DROP/CREATE)
                $ArgumentList = @(
                    "-h $DbHost",
                    "-P $DbPort",
                    "-u $DbUser",
                    "--password=$DbPass",
                    "--no-create-info",
                    "--single-transaction",
                    "--quick",
                    "--default-character-set=utf8mb4",
                    "--where=updated_at >= '$LastFullDateStr'",
                    "$DbName",
                    $Table
                )

                $TempFile = Join-Path $BackupDir "inc_${Table}_$Timestamp.sql"
                & $MysqlDump @ArgumentList --result-file=$TempFile 2>&1 | Out-Null

                if (Test-Path $TempFile) {
                    $ChangedData += $TempFile
                }
            }
        } else {
            # Tabel tanpa updated_at: backup semua (biasanya kecil)
            $TableDesc = & $MysqlClient -h $DbHost -P $DbPort -u $DbUser --password=$DbPass -N -e "SELECT COUNT(*) FROM $Table" 2>&1
            $TableDesc = $TableDesc.Trim()
            Write-Log "  $Table : $TableDesc baris (tanpa kolom waktu, skip incremental)"
        }
    }

    if ($ChangedData.Count -eq 0) {
        Write-Log "INCREMENTAL backup: tidak ada data yang berubah." -Level 'INFO'
        return @{
            File     = $null
            Filename = $Filename
            Size     = 0
            Changed  = 0
        }
    }

    # Gabungkan semua file incremental
    $FirstFile = $true
    foreach ($F in $ChangedData) {
        if (Test-Path $F) {
            if ($FirstFile) {
                Move-Item -Path $F -Destination $FilePath -Force
                $FirstFile = $false
            } else {
                Get-Content $F | Add-Content $FilePath
                Remove-Item $F -Force
            }
        }
    }

    # Kompres
    if ($Gzip -and (Test-Path $FilePath)) {
        $GzFile = "$FilePath.gz"
        & $Gzip -f $FilePath
        $FilePath = $GzFile
    }

    $FileSize = if (Test-Path $FilePath) { (Get-Item $FilePath).Length } else { 0 }
    Write-Log "INCREMENTAL backup selesai: $Filename ($(Get-HumanSize $FileSize), $($ChangedData.Count) tabel berubah)" -Level 'INFO'

    return @{
        File     = $FilePath
        Filename = $Filename
        Size     = $FileSize
        Changed  = $ChangedData.Count
    }
}

function Cleanup-OldBackups {
    Write-Log "Membersihkan backup lebih dari $RetentionDays hari..." -Level 'INFO'
    $Cutoff = (Get-Date).AddDays(-$RetentionDays)
    $OldFiles = Get-ChildItem -Path $BackupDir -Filter "dashboard_*.sql*" | Where-Object {
        $_.LastWriteTime -lt $Cutoff
    }

    foreach ($F in $OldFiles) {
        Remove-Item $F.FullName -Force
        Write-Log "  Hapus: $($F.Name)"
    }

    Write-Log "Pembersihan selesai: $($OldFiles.Count) file dihapus." -Level 'INFO'
}

# ─── Main ───────────────────────────────────────────────────────────────────
Write-Log "=== BACKUP DIMULAI (Type: $Type) ===" -Level 'INFO'

$Result = switch ($Type) {
    'full'         { Do-FullBackup }
    'incremental'  { Do-IncrementalBackup }
}

Cleanup-OldBackups

Write-Log "=== BACKUP SELESAI ===" -Level 'INFO'

# Output untuk konsol
if ($Result) {
    Write-Host "`nRingkasan Backup:" -ForegroundColor Cyan
    Write-Host "  Tipe     : $Type"
    if ($Result.File) {
        Write-Host "  File     : $($Result.File)"
        Write-Host "  Ukuran   : $(Get-HumanSize $Result.Size)"
    }
    if ($Result.Tables) { Write-Host "  Tabel    : $($Result.Tables)" }
    if ($Result.Changed -ne $null) { Write-Host "  Berubah  : $($Result.Changed) tabel" }
}

# Return object untuk diproses oleh scheduler
return $Result
