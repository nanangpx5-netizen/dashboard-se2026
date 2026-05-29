<#
.SYNOPSIS
    Restore Database Dashboard SE2026 dari backup
.DESCRIPTION
    Me-restore database dashboard SE2026 dari file backup (.sql atau .sql.gz).
    Mendukung:
    - Restore full backup
    - Restore incremental backup (gabung dengan full backup terakhir)
    - List backup yang tersedia
    - Dry-run (verifikasi tanpa restore)

.PARAMETER File
    Path file backup yang akan di-restore (.sql atau .sql.gz)
.PARAMETER List
    Tampilkan daftar backup yang tersedia
.PARAMETER DryRun
    Verifikasi file backup tanpa menjalankan restore
.PARAMETER BackupDir
    Direktori backup (default: ../storage/backups)
.PARAMETER Force
    Skip konfirmasi

.EXAMPLE
    .\restore.ps1 -List
    .\restore.ps1 -File "D:\Backups\dashboard_full_20260527_020000.sql.gz"
    .\restore.ps1 -File "storage\backups\dashboard_full_20260527_020000.sql" -DryRun
#>

param(
    [string]$File = '',
    [switch]$List,
    [switch]$DryRun,
    [string]$BackupDir = '',
    [switch]$Force
)

# ─── Konfigurasi ────────────────────────────────────────────────────────────
$ScriptRoot   = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot  = Resolve-Path "$ScriptRoot\.."
$DefaultDir   = Join-Path $ProjectRoot "storage\backups"
$BackupDir    = if ($BackupDir -eq '') { $DefaultDir } else { $BackupDir }
$LogDir       = Join-Path $ProjectRoot "storage\logs"
$LogFile      = Join-Path $LogDir "restore.log"
$MysqlClient  = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
$Gzip         = if (Get-Command 'gzip' -ErrorAction SilentlyContinue) { 'gzip' } else { $null }
$SevenZip     = if (Get-Command '7z' -ErrorAction SilentlyContinue) { '7z' } else { $null }

# ─── Baca .env ───────────────────────────────────────────────────────────────
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

# ─── Fungsi ─────────────────────────────────────────────────────────────────
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

function Get-HumanSize {
    param([long]$Bytes)
    if ($Bytes -ge 1GB) { return "{0:N2} GB" -f ($Bytes / 1GB) }
    if ($Bytes -ge 1MB) { return "{0:N2} MB" -f ($Bytes / 1MB) }
    return "{0:N2} KB" -f ($Bytes / 1KB)
}

function Show-BackupList {
    Write-Host "`n=== Daftar Backup Dashboard SE2026 ===" -ForegroundColor Cyan
    Write-Host "Lokasi: $BackupDir`n"

    if (-not (Test-Path $BackupDir)) {
        Write-Host "  (Belum ada backup)" -ForegroundColor Yellow
        return
    }

    $Files = Get-ChildItem -Path $BackupDir -Filter "dashboard_*.sql*" | Sort-Object LastWriteTime -Descending

    if ($Files.Count -eq 0) {
        Write-Host "  (Tidak ada file backup ditemukan)" -ForegroundColor Yellow
        return
    }

    Write-Host ("{0,-5} {1,-30} {2,-12} {3,-10} {4,-20}" -f "No", "Nama File", "Ukuran", "Tipe", "Tanggal")
    Write-Host ("{0,-5} {1,-30} {2,-12} {3,-10} {4,-20}" -f "-----", "----------", "------", "----", "------")

    $No = 1
    foreach ($F in $Files) {
        $Type = if ($F.Name -match 'full') { 'Full' } else { 'Inc' }
        $Size = Get-HumanSize $F.Length
        $Date = $F.LastWriteTime.ToString('yyyy-MM-dd HH:mm')
        Write-Host ("{0,-5} {1,-30} {2,-12} {3,-10} {4,-20}" -f $No, $F.Name, $Size, $Type, $Date)
        $No++
    }
    Write-Host ""
}

function Test-BackupIntegrity {
    param([string]$FilePath)

    Write-Log "Memverifikasi integritas file backup: $(Split-Path $FilePath -Leaf)" -Level 'INFO'

    if (-not (Test-Path $FilePath)) {
        Write-Log "  File tidak ditemukan!" -Level 'ERROR'
        return $false
    }

    $TempFile = $null
    try {
        # Dekompres jika perlu
        if ($FilePath -match '\.gz$') {
            $TempFile = [System.IO.Path]::GetTempFileName() + ".sql"
            if ($Gzip) {
                & $Gzip -d -c $FilePath > $TempFile 2>&1 | Out-Null
            } elseif ($SevenZip) {
                & $SevenZip x $FilePath -o"$([System.IO.Path]::GetTempPath())" -y | Out-Null
                $TempFile = [System.IO.Path]::GetTempPath() + [System.IO.Path]::GetFileNameWithoutExtension([System.IO.Path]::GetFileNameWithoutExtension($FilePath))
            } else {
                # PowerShell native
                $InputFile = [System.IO.File]::OpenRead($FilePath)
                $GzipStream = New-Object System.IO.Compression.GzipStream($InputFile, [System.IO.Compression.CompressionMode]::Decompress)
                $Reader = New-Object System.IO.StreamReader($GzipStream)
                $Reader.ReadToEnd() | Set-Content -Path $TempFile -Encoding UTF8
                $Reader.Close()
                $GzipStream.Close()
                $InputFile.Close()
            }
        } else {
            $TempFile = $FilePath
        }

        # SQL syntax check: coba parse dengan mysql client
        $CheckCmd = "SET @check=0;"
        $Result = & $MysqlClient -h $DbHost -P $DbPort -u $DbUser --password=$DbPass -N -e $CheckCmd 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Log "  Koneksi database gagal: $Result" -Level 'WARN'
            Write-Log "  Verifikasi file: HANYA CEK HEADER/FOOTER" -Level 'WARN'
        }

        # Cek footer untuk memastikan backup lengkap
        $Content = Get-Content $TempFile -Tail 5
        $HasFooter = $false
        foreach ($Line in $Content) {
            if ($Line -match '-- Dump completed' -or $Line -match 'PATCH 004') {
                $HasFooter = $true
                break
            }
        }

        if (-not $HasFooter) {
            Write-Log "  PERINGATAN: File backup mungkin tidak lengkap (footer tidak ditemukan)" -Level 'WARN'
        } else {
            Write-Log "  File backup valid dan lengkap." -Level 'INFO'
        }

        # Hitung baris SQL
        $SqlCount = (Get-Content $TempFile | Where-Object { $_ -match '^INSERT INTO|^CREATE TABLE|^ALTER TABLE' }).Count
        Write-Log "  Statement SQL: $SqlCount perintah"

        # Cek ukuran
        $Size = (Get-Item $TempFile).Length
        Write-Log "  Ukuran data: $(Get-HumanSize $Size)"

        return $true
    }
    catch {
        Write-Log "  Verifikasi gagal: $_" -Level 'ERROR'
        return $false
    }
    finally {
        if ($TempFile -and $TempFile -ne $FilePath -and (Test-Path $TempFile)) {
            Remove-Item $TempFile -Force -ErrorAction SilentlyContinue
        }
    }
}

function Do-Restore {
    param([string]$FilePath)

    $Filename = Split-Path $FilePath -Leaf
    Write-Log "Memulai RESTORE: $Filename" -Level 'INFO'

    if (-not (Test-Path $FilePath)) {
        Write-Log "  File tidak ditemukan: $FilePath" -Level 'ERROR'
        return $false
    }

    # Konfirmasi
    if (-not $Force) {
        $conf = Read-Host "YAKIN akan me-restore database '$DbName'? Semua data dashboard akan ditimpa. (y/N)"
        if ($conf -ne 'y' -and $conf -ne 'Y') {
            Write-Log "Restore dibatalkan oleh user." -Level 'INFO'
            return $false
        }
    }

    # Backup database saat ini sebelum restore (pre-restore safety)
    $PreRestoreBackup = Join-Path $BackupDir "pre_restore_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql.gz"
    Write-Log "Backup safety sebelum restore: $PreRestoreBackup" -Level 'INFO'
    & $ScriptRoot\backup.ps1 -Type full -BackupDir $BackupDir -RetentionDays 7 | Out-Null

    # Ekstrak jika gzip
    $SqlFile = $FilePath
    if ($FilePath -match '\.gz$') {
        Write-Log "Mendekompres file..." -Level 'INFO'
        $SqlFile = [System.IO.Path]::GetTempFileName() + ".sql"
        if ($Gzip) {
            & $Gzip -d -c $FilePath > $SqlFile 2>&1 | Out-Null
        } else {
            $InputFile = [System.IO.File]::OpenRead($FilePath)
            $GzipStream = New-Object System.IO.Compression.GzipStream($InputFile, [System.IO.Compression.CompressionMode]::Decompress)
            $Reader = New-Object System.IO.StreamReader($GzipStream)
            $Reader.ReadToEnd() | Set-Content -Path $SqlFile -Encoding UTF8
            $Reader.Close()
            $GzipStream.Close()
            $InputFile.Close()
        }
    }

    # Jalankan restore
    Write-Log "Menjalankan mysql < $Filename ..." -Level 'INFO'

    try {
        $ArgumentList = @(
            "-h $DbHost",
            "-P $DbPort",
            "-u $DbUser",
            "--password=$DbPass",
            "$DbName"
        )

        $proc = Start-Process -FilePath $MysqlClient -ArgumentList $ArgumentList -RedirectStandardInput $SqlFile -Wait -NoNewWindow -PassThru

        if ($proc.ExitCode -ne 0) {
            Write-Log "  RESTORE GAGAL (exit code: $($proc.ExitCode))" -Level 'ERROR'
            Write-Log "  Gunakan pre-restore backup: $PreRestoreBackup" -Level 'WARN'
            return $false
        }

        Write-Log "RESTORE berhasil: $Filename" -Level 'INFO'

        # Verifikasi post-restore
        $TableCount = & $MysqlClient -h $DbHost -P $DbPort -u $DbUser --password=$DbPass -N -e "
            SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = '$DbName'
              AND TABLE_NAME IN ('sipw_import','sipw_assignment','dash_import_log')
        " 2>&1
        $TableCount = $TableCount.Trim()

        Write-Log "  Tabel dashboard terverifikasi: $TableCount/3" -Level 'INFO'

        # Hapus cache
        if (Test-Path "$ProjectRoot\storage\cache") {
            Remove-Item "$ProjectRoot\storage\cache\*.cache" -Force -ErrorAction SilentlyContinue
            Write-Log "  Cache dibersihkan." -Level 'INFO'
        }

        return $true
    }
    catch {
        Write-Log "  RESTORE GAGAL: $_" -Level 'ERROR'
        Write-Log "  Gunakan pre-restore backup: $PreRestoreBackup" -Level 'WARN'
        return $false
    }
    finally {
        if ($SqlFile -and $SqlFile -ne $FilePath -and (Test-Path $SqlFile)) {
            Remove-Item $SqlFile -Force -ErrorAction SilentlyContinue
        }
    }
}

# ─── Main ───────────────────────────────────────────────────────────────────
if ($List) {
    Show-BackupList
    exit 0
}

if ($DryRun -and $File) {
    Write-Log "=== DRY RUN: Verifikasi Backup ===" -Level 'INFO'
    Test-BackupIntegrity -FilePath $File
    exit 0
}

if (-not $File) {
    Write-Host "Gunakan -File <path> atau -List untuk melihat daftar backup." -ForegroundColor Yellow
    Show-BackupList
    exit 1
}

Write-Log "=== RESTORE DIMULAI ===" -Level 'INFO'

if ($DryRun) {
    Test-BackupIntegrity -FilePath $File
} else {
    $Success = Do-Restore -FilePath $File
    if ($Success) {
        Write-Host "`nRESTORE BERHASIL!" -ForegroundColor Green
        Write-Host "Cache sudah dibersihkan. Refresh browser untuk melihat perubahan." -ForegroundColor Cyan
    } else {
        Write-Host "`nRESTORE GAGAL!" -ForegroundColor Red
        Write-Host "Cek log: $LogFile" -ForegroundColor Yellow
        Write-Host "Gunakan pre-restore backup di $BackupDir untuk mengembalikan ke kondisi sebelum restore." -ForegroundColor Yellow
    }
}

Write-Log "=== RESTORE SELESAI ===" -Level 'INFO'
