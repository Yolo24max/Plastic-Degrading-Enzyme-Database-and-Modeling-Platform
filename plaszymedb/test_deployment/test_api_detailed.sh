#!/bin/bash
# 详细测试 API 文件
echo "============================================"
echo "详细测试 API"
echo "============================================"
echo ""

# 测试1: 使用第一个PLZ_ID获取info
echo "测试1: 获取结构信息 (PLZ_ID: 866554aa77)"
echo "----------------------------------------"
curl -s "http://localhost/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=info"
echo ""
echo ""

# 测试2: 获取PDB文件
echo "测试2: 获取PDB文件内容 (前20行)"
echo "----------------------------------------"
curl -s "http://localhost/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=pdb" | head -20
echo ""
echo ""

# 测试3: 获取JSON文件
echo "测试3: 获取JSON元数据"
echo "----------------------------------------"
curl -s "http://localhost/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=json"
echo ""
echo ""

# 测试4: 测试之前报错的PLZ_ID
echo "测试4: 测试之前报错的 PLZ_ID (ee302712eb)"
echo "----------------------------------------"
curl -s "http://localhost/api_protein_structure.php?plz_id=ee302712eb&type=predicted&action=info"
echo ""
echo ""

# 测试5: 检查实际文件是否存在
echo "测试5: 检查实际文件"
echo "----------------------------------------"
echo "检查 X0001.pdb 是否存在："
if [ -f /var/www/html/plaszymedb/structure_data/predicted_xid/pdb/X0001.pdb ]; then
    echo "✓ X0001.pdb 存在"
    ls -lh /var/www/html/plaszymedb/structure_data/predicted_xid/pdb/X0001.pdb
else
    echo "✗ X0001.pdb 不存在"
fi

echo ""
echo "检查 X0001.json 是否存在："
if [ -f /var/www/html/plaszymedb/structure_data/predicted_xid/json/X0001.json ]; then
    echo "✓ X0001.json 存在"
    ls -lh /var/www/html/plaszymedb/structure_data/predicted_xid/json/X0001.json
else
    echo "✗ X0001.json 不存在"
fi

echo ""
echo "============================================"
echo "测试完成"
echo "============================================"

