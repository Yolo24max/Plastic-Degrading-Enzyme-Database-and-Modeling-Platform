#!/bin/bash
echo "============================================"
echo "检查 PHP 错误"
echo "============================================"
echo ""

# 1. 检查 Apache 错误日志
echo "1. Apache 错误日志 (最后50行)："
echo "----------------------------------------"
sudo tail -50 /var/log/httpd/error_log
echo ""

# 2. 直接用 PHP CLI 测试文件
echo "2. 使用 PHP CLI 测试 (模拟请求)："
echo "----------------------------------------"
cd /var/www/html
export QUERY_STRING="plz_id=866554aa77&type=predicted&action=info"
php -r "
\$_GET['plz_id'] = '866554aa77';
\$_GET['type'] = 'predicted';
\$_GET['action'] = 'info';
include 'api_protein_structure.php';
"
echo ""

# 3. 检查 PHP 语法错误
echo "3. 检查 PHP 语法："
echo "----------------------------------------"
php -l /var/www/html/api_protein_structure.php
echo ""

# 4. 检查文件内容前几行
echo "4. API 文件前20行："
echo "----------------------------------------"
head -20 /var/www/html/api_protein_structure.php
echo ""

# 5. 检查 db_config.php 是否存在
echo "5. 检查 db_config.php："
echo "----------------------------------------"
if [ -f /var/www/html/db_config.php ]; then
    echo "✓ db_config.php 存在"
    ls -lh /var/www/html/db_config.php
else
    echo "✗ db_config.php 不存在"
fi
echo ""

# 6. 测试 CSV 文件是否可读
echo "6. 测试 CSV 文件权限："
echo "----------------------------------------"
ls -lh /var/www/html/plaszymedb/structure_data/predicted_xid/pred_metadata_XID.csv
echo ""

echo "============================================"
echo "检查完成"
echo "============================================"

