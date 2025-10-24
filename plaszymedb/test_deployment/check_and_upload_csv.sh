#!/bin/bash

echo "========================================"
echo "🔍 检查 EC2 服务器上的 CSV 文件"
echo "========================================"
echo ""

if [ -z "$1" ]; then
    echo "用法: bash check_and_upload_csv.sh <EC2_IP_ADDRESS>"
    echo "示例: bash check_and_upload_csv.sh 18.237.158.100"
    exit 1
fi

SERVER_IP=$1
KEY_FILE="wangshang.pem"

echo "服务器 IP: $SERVER_IP"
echo ""

# 检查密钥文件
if [ ! -f "$KEY_FILE" ]; then
    echo "❌ 错误: 找不到密钥文件 $KEY_FILE"
    exit 1
fi

echo "步骤 1: 检查 EC2 服务器上的文件..."
echo ""

ssh -i "$KEY_FILE" "ec2-user@${SERVER_IP}" << 'EOF'
echo "=== 检查 structure_data 目录 ==="
if [ -d "/var/www/html/structure_data" ]; then
    echo "✓ structure_data 目录存在"
    ls -lh /var/www/html/structure_data/
else
    echo "❌ structure_data 目录不存在"
fi

echo ""
echo "=== 检查 predicted_xid 目录 ==="
if [ -d "/var/www/html/structure_data/predicted_xid" ]; then
    echo "✓ predicted_xid 目录存在"
    ls -lh /var/www/html/structure_data/predicted_xid/ | grep -E "\.(csv|CSV)$"
else
    echo "❌ predicted_xid 目录不存在"
fi

echo ""
echo "=== 检查关键 CSV 文件 ==="

if [ -f "/var/www/html/structure_data/predicted_xid/PLZ_XID.csv" ]; then
    echo "✅ PLZ_XID.csv 存在"
    ls -lh /var/www/html/structure_data/predicted_xid/PLZ_XID.csv
    echo ""
    echo "文件头部（前 2 行）："
    head -n 2 /var/www/html/structure_data/predicted_xid/PLZ_XID.csv
else
    echo "❌ PLZ_XID.csv 不存在 - 需要上传！"
fi

echo ""

if [ -f "/var/www/html/structure_data/predicted_xid/pred_metadata_XID.csv" ]; then
    echo "✓ pred_metadata_XID.csv 存在"
    ls -lh /var/www/html/structure_data/predicted_xid/pred_metadata_XID.csv
else
    echo "❌ pred_metadata_XID.csv 不存在"
fi

echo ""
echo "=== 检查 PDB 文件 ==="
PDB_COUNT=$(ls -1 /var/www/html/structure_data/predicted_xid/pdb/*.pdb 2>/dev/null | wc -l)
echo "PDB 文件数量: $PDB_COUNT"

if [ $PDB_COUNT -gt 0 ]; then
    echo "✓ PDB 文件存在"
    ls -lh /var/www/html/structure_data/predicted_xid/pdb/ | head -n 5
else
    echo "❌ 没有 PDB 文件"
fi

EOF

echo ""
echo "========================================"
echo "检查完成！"
echo "========================================"
echo ""
echo "如果 PLZ_XID.csv 不存在，运行以下命令上传："
echo ""
echo "scp -i $KEY_FILE structure_data/predicted_xid/PLZ_XID.csv ec2-user@${SERVER_IP}:/tmp/"
echo "ssh -i $KEY_FILE ec2-user@${SERVER_IP} 'sudo mv /tmp/PLZ_XID.csv /var/www/html/structure_data/predicted_xid/'"
echo "ssh -i $KEY_FILE ec2-user@${SERVER_IP} 'sudo chown apache:apache /var/www/html/structure_data/predicted_xid/PLZ_XID.csv'"
echo "ssh -i $KEY_FILE ec2-user@${SERVER_IP} 'sudo chmod 644 /var/www/html/structure_data/predicted_xid/PLZ_XID.csv'"
echo ""

