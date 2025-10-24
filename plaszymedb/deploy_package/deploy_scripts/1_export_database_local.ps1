# PlaszymeDB 本地数据库导出脚本
# 在Windows本地执行，导出MySQL数据库

$ErrorActionPreference = "Stop"

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  PlaszymeDB 数据库导出脚本" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# 配置参数
$MYSQL_BIN = "C:\xampp\mysql\bin\mysqldump.exe"
$MYSQL_HOST = "localhost"
$MYSQL_PORT = "3307"
$MYSQL_USER = "root"
$MYSQL_PASS = "yoloShang2025"
$DB_NAME = "plaszymedb"
$PROJECT_DIR = "C:\xampp\htdocs\plaszymedb"
$OUTPUT_FILE = "$PROJECT_DIR\plaszymedb_backup.sql"

# 检查mysqldump是否存在
if (-not (Test-Path $MYSQL_BIN)) {
    Write-Host "错误: 找不到mysqldump.exe，请检查路径: $MYSQL_BIN" -ForegroundColor Red
    exit 1
}

Write-Host "[1/3] 开始导出数据库: $DB_NAME" -ForegroundColor Yellow
Write-Host "      输出文件: $OUTPUT_FILE" -ForegroundColor Gray

try {
    # 导出数据库
    & $MYSQL_BIN -h $MYSQL_HOST -P $MYSQL_PORT -u $MYSQL_USER -p"$MYSQL_PASS" `
        --single-transaction `
        --routines `
        --triggers `
        --events `
        $DB_NAME | Out-File -FilePath $OUTPUT_FILE -Encoding UTF8
    
    if ($LASTEXITCODE -ne 0) {
        throw "数据库导出失败"
    }
    
    Write-Host "✓ 数据库导出成功" -ForegroundColor Green
    
} catch {
    Write-Host "✗ 数据库导出失败: $_" -ForegroundColor Red
    exit 1
}

# 检查文件大小
$fileSize = (Get-Item $OUTPUT_FILE).Length / 1MB
Write-Host "[2/3] 文件大小: $([math]::Round($fileSize, 2)) MB" -ForegroundColor Yellow

# 验证SQL文件
Write-Host "[3/3] 验证SQL文件..." -ForegroundColor Yellow
$content = Get-Content $OUTPUT_FILE -First 10
if ($content -match "MySQL dump") {
    Write-Host "✓ SQL文件验证成功" -ForegroundColor Green
} else {
    Write-Host "✗ SQL文件可能损坏，请检查" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  数据库导出完成！" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "导出文件位置: $OUTPUT_FILE" -ForegroundColor White
Write-Host ""
Write-Host "下一步: 使用WinSCP或scp命令将整个项目文件夹上传到EC2" -ForegroundColor Yellow
Write-Host ""

