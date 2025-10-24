#!/bin/bash

echo "========================================"
echo "同步分号修复到部署包"
echo "========================================"
echo ""

# 定义需要同步的文件
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

# 同步文件
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ 复制 $file 到 deploy_package/"
        cp "$file" "deploy_package/"
    else
        echo "✗ 警告: $file 不存在"
    fi
done

echo ""
echo "========================================"
echo "✓ 同步完成！"
echo "========================================"
echo ""
echo "下一步："
echo "1. 使用 Git 提交修改"
echo "2. 推送到远程仓库"
echo "3. 在 EC2 服务器上拉取更新"
echo "4. 测试 http://<server-ip>/test_semicolon_fix.html"
echo ""

