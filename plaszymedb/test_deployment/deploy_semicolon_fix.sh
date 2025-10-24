#!/bin/bash

echo "========================================"
echo "🚀 部署分号修复到 EC2 服务器"
echo "========================================"
echo ""

# 检查是否提供了服务器 IP
if [ -z "$1" ]; then
    echo "用法: bash deploy_semicolon_fix.sh <EC2_IP_ADDRESS>"
    echo "示例: bash deploy_semicolon_fix.sh 18.237.158.100"
    exit 1
fi

SERVER_IP=$1
SERVER_PATH="/var/www/html"
KEY_FILE="PlaszymeDB_AWS.pem"

echo "目标服务器: $SERVER_IP"
echo "目标路径: $SERVER_PATH"
echo ""

# 检查密钥文件
if [ ! -f "$KEY_FILE" ]; then
    echo "❌ 错误: 找不到密钥文件 $KEY_FILE"
    echo "请确保密钥文件在当前目录或提供正确路径"
    exit 1
fi

echo "步骤 1: 创建临时部署目录..."
DEPLOY_DIR="deploy_temp_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$DEPLOY_DIR"

echo "步骤 2: 复制修改的文件到临时目录..."
FILES=(
    "protein_viewer_optimized.js"
    "protein_3d_viewer.js"
    "api_protein_structure.php"
    "test_semicolon_fix.html"
    "README_SEMICOLON_FIX.md"
    "QUICK_TEST_GUIDE.md"
    "DEPLOYMENT_READY.md"
    "SEMICOLON_FIX_SUMMARY.md"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$DEPLOY_DIR/"
        echo "  ✓ $file"
    else
        echo "  ✗ 警告: $file 不存在"
    fi
done

echo ""
echo "步骤 3: 上传文件到 EC2 服务器..."
scp -i "$KEY_FILE" -r "$DEPLOY_DIR"/* "ec2-user@${SERVER_IP}:${SERVER_PATH}/"

if [ $? -eq 0 ]; then
    echo "✓ 文件上传成功！"
else
    echo "❌ 上传失败！请检查网络连接和服务器配置"
    rm -rf "$DEPLOY_DIR"
    exit 1
fi

echo ""
echo "步骤 4: 设置文件权限..."
ssh -i "$KEY_FILE" "ec2-user@${SERVER_IP}" << 'EOF'
cd /var/www/html
chmod 644 protein_viewer_optimized.js
chmod 644 protein_3d_viewer.js
chmod 644 api_protein_structure.php
chmod 644 test_semicolon_fix.html
chmod 644 SEMICOLON_FIX_SUMMARY.md
chmod 644 QUICK_TEST_GUIDE.md
echo "✓ 权限设置完成"
EOF

echo ""
echo "步骤 5: 清理临时文件..."
rm -rf "$DEPLOY_DIR"
echo "✓ 清理完成"

echo ""
echo "========================================"
echo "✅ 部署完成！"
echo "========================================"
echo ""
echo "测试链接:"
echo "  📋 测试页面: http://${SERVER_IP}/test_semicolon_fix.html"
echo "  🧪 API 测试:  http://${SERVER_IP}/api_protein_structure.php?plz_id=98b7748823%3Be79726b180&type=predicted&action=info"
echo "  🏠 主页:      http://${SERVER_IP}/V9.html"
echo ""
echo "下一步:"
echo "  1. 在浏览器中打开测试页面"
echo "  2. 运行所有 4 个测试用例"
echo "  3. 验证 X0002、X0003 等能正常加载 3D 结构"
echo "  4. 查看 QUICK_TEST_GUIDE.md 了解详细测试步骤"
echo ""

