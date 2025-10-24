#!/bin/bash
echo "============================================"
echo "调试 PLZ_ID 映射问题"
echo "============================================"
echo ""

CSV_FILE="/var/www/html/plaszymedb/structure_data/predicted_xid/pred_metadata_XID.csv"

echo "1. 检查 X0002 在 CSV 中的记录："
echo "----------------------------------------"
grep "X0002" "$CSV_FILE"
echo ""

echo "2. 查找包含 '98b7748823' 的记录："
echo "----------------------------------------"
grep "98b7748823" "$CSV_FILE"
echo ""

echo "3. 查找包含 'e79726b180' 的记录："
echo "----------------------------------------"
grep "e79726b180" "$CSV_FILE"
echo ""

echo "4. 测试 API 查询这个 PLZ_ID："
echo "----------------------------------------"
echo "测试完整的 PLZ_ID (带分号):"
curl -s "http://localhost/api_protein_structure.php?plz_id=98b7748823;e79726b180&type=predicted&action=info"
echo ""
echo ""

echo "测试第一个 PLZ_ID:"
curl -s "http://localhost/api_protein_structure.php?plz_id=98b7748823&type=predicted&action=info"
echo ""
echo ""

echo "测试第二个 PLZ_ID:"
curl -s "http://localhost/api_protein_structure.php?plz_id=e79726b180&type=predicted&action=info"
echo ""
echo ""

echo "5. 检查 X0002.pdb 文件是否存在："
echo "----------------------------------------"
if [ -f "/var/www/html/plaszymedb/structure_data/predicted_xid/pdb/X0002.pdb" ]; then
    echo "✓ X0002.pdb 存在"
    ls -lh "/var/www/html/plaszymedb/structure_data/predicted_xid/pdb/X0002.pdb"
else
    echo "✗ X0002.pdb 不存在"
fi
echo ""

echo "6. CSV 文件的前5行（查看格式）："
echo "----------------------------------------"
head -5 "$CSV_FILE"
echo ""

echo "============================================"
echo "调试完成"
echo "============================================"

