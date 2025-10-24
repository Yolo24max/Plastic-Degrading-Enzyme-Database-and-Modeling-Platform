#!/bin/bash
# 更新 EC2 上的 api_protein_structure.php 文件 (v2 - 正确版本)
# 使用方法：
# 1. SSH 连接到 EC2: ssh -i "your-key.pem" ec2-user@ec2-18-218-230-149.us-east-2.compute.amazonaws.com
# 2. 创建此脚本: nano update_api_v2.sh
# 3. 粘贴脚本内容
# 4. 执行: bash update_api_v2.sh

echo "============================================"
echo "更新 API 文件到正确版本"
echo "============================================"

# 备份现有文件
if [ -f /var/www/html/api_protein_structure.php ]; then
    sudo cp /var/www/html/api_protein_structure.php /var/www/html/api_protein_structure.php.backup_$(date +%Y%m%d_%H%M%S)
    echo "✓ 已备份现有文件"
else
    echo "! 未找到现有文件，将创建新文件"
fi

# 创建新文件
sudo tee /var/www/html/api_protein_structure.php > /dev/null << 'PHPEOF'
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入数据库配置
require_once 'db_config.php';

/**
 * 从CSV文件加载PLZ_ID到XID的映射
 */
function loadPlzToXidMapping($csvPath) {
    static $cache = [];
    
    if (isset($cache[$csvPath])) {
        return $cache[$csvPath];
    }
    
    $mapping = [];
    if (!file_exists($csvPath)) {
        return $mapping;
    }
    
    $file = fopen($csvPath, 'r');
    if ($file === false) {
        return $mapping;
    }
    
    // 读取表头
    $header = fgetcsv($file);
    if ($header === false) {
        fclose($file);
        return $mapping;
    }
    
    // 查找PLZ_ID和protein_id列的索引
    $plzIdIndex = array_search('PLZ_ID', $header);
    $proteinIdIndex = array_search('protein_id', $header);
    
    if ($plzIdIndex === false || $proteinIdIndex === false) {
        fclose($file);
        return $mapping;
    }
    
    // 读取数据行
    // 映射格式：PLZ_ID (hash) => protein_id (XID)
    while (($row = fgetcsv($file)) !== false) {
        if (isset($row[$plzIdIndex]) && isset($row[$proteinIdIndex])) {
            $plz_id_value = trim($row[$plzIdIndex]);
            $protein_id_value = trim($row[$proteinIdIndex]);
            
            // 支持多个PLZ_ID（用分号分隔）映射到同一个protein_id
            $plz_ids = explode(';', $plz_id_value);
            foreach ($plz_ids as $single_plz_id) {
                $single_plz_id = trim($single_plz_id);
                if (!empty($single_plz_id)) {
                    $mapping[$single_plz_id] = $protein_id_value;
                }
            }
        }
    }
    
    fclose($file);
    $cache[$csvPath] = $mapping;
    return $mapping;
}

try {
    // 获取请求参数
    $plz_id = isset($_GET['plz_id']) ? trim($_GET['plz_id']) : '';
    $data_type = isset($_GET['type']) ? trim($_GET['type']) : 'predicted'; // predicted 或 experimental
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'info'; // info, pdb, json
    
    if (empty($plz_id)) {
        throw new Exception('PLZ_ID parameter cannot be empty');
    }
    
    // Validate data type
    if (!in_array($data_type, ['predicted', 'experimental'])) {
        throw new Exception('Invalid data type');
    }
    
    // 根据数据类型确定文件路径和metadata文件
    // EC2上的绝对路径
    $base_path = '/var/www/html/plaszymedb/structure_data';
    
    if ($data_type === 'predicted') {
        $pdb_dir = $base_path . '/predicted_xid/pdb/';
        $json_dir = $base_path . '/predicted_xid/json/';
        $metadata_csv = $base_path . '/predicted_xid/pred_metadata_XID.csv';
    } else {
        $pdb_dir = $base_path . '/experimental_xid/pdb/';
        $json_dir = $base_path . '/experimental_xid/json/';
        $metadata_csv = $base_path . '/experimental_xid/exp_metadata_XID.csv';
    }
    
    // 加载PLZ_ID到XID的映射
    $mapping = loadPlzToXidMapping($metadata_csv);
    
    // 获取对应的XID（protein_id）
    if (!isset($mapping[$plz_id])) {
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => "No {$data_type} structure data available for this protein",
            'plz_id' => $plz_id,
            'data_type' => $data_type,
            'available' => false
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $xid = $mapping[$plz_id];
    
    // 使用XID构建文件路径
    $pdb_file = $pdb_dir . $xid . '.pdb';
    $json_file = $json_dir . $xid . '.json';
    
    switch ($action) {
        case 'info':
            // 返回结构信息
            $info = [
                'plz_id' => $plz_id,
                'xid' => $xid,
                'data_type' => $data_type,
                'files' => [
                    'pdb_exists' => file_exists($pdb_file),
                    'json_exists' => file_exists($json_file),
                    'pdb_path' => 'api_protein_structure.php?plz_id=' . $plz_id . '&type=' . $data_type . '&action=pdb',
                    'json_path' => 'api_protein_structure.php?plz_id=' . $plz_id . '&type=' . $data_type . '&action=json',
                    'pdb_file_path' => $pdb_file,
                    'json_file_path' => $json_file
                ]
            ];
            
            // 从数据库获取相关信息
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT enzyme_name, ec_number, pdb_ids FROM plaszymedb WHERE PLZ_ID = ?");
            $stmt->execute([$plz_id]);
            $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($db_info) {
                $info['enzyme_info'] = $db_info;
            }
            
            echo json_encode($info, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'pdb':
            // 返回PDB文件内容
            if (!file_exists($pdb_file)) {
                throw new Exception("PDB file does not exist - PLZ_ID: {$plz_id}, XID: {$xid}, Path: {$pdb_file}");
            }
            
            header('Content-Type: text/plain');
            header('Content-Disposition: inline; filename="' . $xid . '.pdb"');
            readfile($pdb_file);
            break;
            
        case 'json':
            // 返回JSON元数据
            if (!file_exists($json_file)) {
                throw new Exception("JSON file does not exist - PLZ_ID: {$plz_id}, XID: {$xid}, Path: {$json_file}");
            }
            
            $json_content = file_get_contents($json_file);
            $json_data = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON file format error: ' . json_last_error_msg());
            }
            
            echo json_encode($json_data, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'list_available':
            // 列出可用的结构文件
            $available = [];
            
            if (is_dir($pdb_dir)) {
                $pdb_files = glob($pdb_dir . '*.pdb');
                foreach ($pdb_files as $file) {
                    $id = basename($file, '.pdb');
                    $available[] = [
                        'plz_id' => $id,
                        'has_pdb' => true,
                        'has_json' => file_exists($json_dir . $id . '.json')
                    ];
                }
            }
            
            echo json_encode([
                'data_type' => $data_type,
                'count' => count($available),
                'structures' => $available
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
PHPEOF

echo "✓ API 文件已更新"

# 设置正确的权限
sudo chown apache:apache /var/www/html/api_protein_structure.php
sudo chmod 644 /var/www/html/api_protein_structure.php
echo "✓ 文件权限已设置"

# 重启 Apache
sudo systemctl restart httpd
echo "✓ Apache 已重启"

echo ""
echo "============================================"
echo "测试 API"
echo "============================================"

# 首先检查CSV文件是否存在
echo ""
echo "1. 检查 CSV 映射文件："
if [ -f /var/www/html/plaszymedb/structure_data/predicted_xid/pred_metadata_XID.csv ]; then
    echo "   ✓ predicted CSV 存在"
    echo "   前5行内容："
    head -5 /var/www/html/plaszymedb/structure_data/predicted_xid/pred_metadata_XID.csv
else
    echo "   ✗ predicted CSV 不存在"
fi

echo ""
echo "2. 测试 API (使用示例 PLZ_ID)："
echo "   尝试获取结构信息..."

# 从CSV获取第一个有效的PLZ_ID进行测试
if [ -f /var/www/html/plaszymedb/structure_data/predicted_xid/pred_metadata_XID.csv ]; then
    TEST_PLZ_ID=$(awk -F',' 'NR==2 {print $1}' /var/www/html/plaszymedb/structure_data/predicted_xid/pred_metadata_XID.csv | tr -d '\r')
    if [ ! -z "$TEST_PLZ_ID" ]; then
        echo "   使用 PLZ_ID: $TEST_PLZ_ID"
        curl -s "http://localhost/api_protein_structure.php?plz_id=${TEST_PLZ_ID}&type=predicted&action=info" | head -20
    fi
fi

echo ""
echo "============================================"
echo "更新完成！"
echo "============================================"
echo ""
echo "现在您可以："
echo "1. 测试特定的 PLZ_ID："
echo "   curl 'http://localhost/api_protein_structure.php?plz_id=YOUR_PLZ_ID&type=predicted&action=pdb'"
echo ""
echo "2. 在浏览器中访问："
echo "   http://ec2-18-218-230-149.us-east-2.compute.amazonaws.com/api_protein_structure.php?plz_id=YOUR_PLZ_ID&type=predicted&action=info"

