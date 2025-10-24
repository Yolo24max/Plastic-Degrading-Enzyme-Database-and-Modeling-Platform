#!/bin/bash
# 更新 EC2 上的 api_protein_structure.php 文件
# 使用方法：
# 1. SSH 连接到 EC2: ssh -i "your-key.pem" ec2-user@ec2-18-218-230-149.us-east-2.compute.amazonaws.com
# 2. 创建此脚本: nano update_api.sh
# 3. 粘贴脚本内容
# 4. 执行: bash update_api.sh

# 备份现有文件
sudo cp /var/www/html/api_protein_structure.php /var/www/html/api_protein_structure.php.backup_$(date +%Y%m%d_%H%M%S)

# 创建新文件
sudo tee /var/www/html/api_protein_structure.php > /dev/null << 'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/httpd/php_errors.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * 从CSV文件加载PLZ_ID到protein_id的映射关系
 * @param string $csvPath CSV文件路径
 * @return array 映射数组 [PLZ_ID => protein_id]
 */
function loadIdMapping($csvPath) {
    static $cache = [];
    
    if (isset($cache[$csvPath])) {
        return $cache[$csvPath];
    }
    
    $mapping = [];
    
    if (!file_exists($csvPath)) {
        return $mapping;
    }
    
    $file = fopen($csvPath, 'r');
    if (!$file) {
        return $mapping;
    }
    
    // 读取表头
    $headers = fgetcsv($file);
    if (!$headers) {
        fclose($file);
        return $mapping;
    }
    
    // 查找列索引
    $plzIdIndex = array_search('PLZ_ID', $headers);
    $proteinIdIndex = array_search('protein_id', $headers);
    
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
    $proteinId = $_GET['protein_id'] ?? '';
    
    if (empty($proteinId)) {
        throw new Exception('Missing protein_id parameter');
    }
    
    $proteinId = trim($proteinId);
    $baseDir = '/var/www/html/plaszymedb';
    
    // 定义结构数据路径
    $structureDataDirs = [
        'predicted' => [
            'pdb' => "$baseDir/structure_data/predicted_xid/pdb",
            'json' => "$baseDir/structure_data/predicted_xid/json",
            'csv' => "$baseDir/structure_data/predicted_xid/pred_metadata_XID.csv"
        ],
        'experimental' => [
            'pdb' => "$baseDir/structure_data/experimental_xid/pdb",
            'json' => "$baseDir/structure_data/experimental_xid/json",
            'csv' => "$baseDir/structure_data/experimental_xid/exp_metadata_XID.csv"
        ]
    ];
    
    $result = [
        'protein_id' => $proteinId,
        'structures' => []
    ];
    
    // 检查每种类型的结构数据
    foreach ($structureDataDirs as $type => $paths) {
        // 加载ID映射
        $idMapping = loadIdMapping($paths['csv']);
        
        // 查找对应的protein_id（XID格式）
        $targetId = $proteinId;
        
        // 如果输入是PLZ_ID（哈希格式），转换为protein_id
        if (isset($idMapping[$proteinId])) {
            $targetId = $idMapping[$proteinId];
        }
        
        // 构造文件路径（使用XID）
        $pdbFile = $paths['pdb'] . '/' . $targetId . '.pdb';
        $jsonFile = $paths['json'] . '/' . $targetId . '.json';
        
        // 检查文件是否存在
        if (file_exists($pdbFile) && file_exists($jsonFile)) {
            $pdbContent = file_get_contents($pdbFile);
            $jsonContent = file_get_contents($jsonFile);
            
            if ($pdbContent !== false && $jsonContent !== false) {
                $metadata = json_decode($jsonContent, true);
                
                $result['structures'][] = [
                    'type' => $type,
                    'protein_id' => $targetId,
                    'pdb_content' => $pdbContent,
                    'metadata' => $metadata ?: []
                ];
            }
        }
    }
    
    if (empty($result['structures'])) {
        // 如果没有找到结构，提供详细的调试信息
        $debugInfo = [
            'searched_locations' => [],
            'id_mappings' => []
        ];
        
        foreach ($structureDataDirs as $type => $paths) {
            $idMapping = loadIdMapping($paths['csv']);
            $targetId = isset($idMapping[$proteinId]) ? $idMapping[$proteinId] : $proteinId;
            
            $debugInfo['searched_locations'][$type] = [
                'pdb' => $paths['pdb'] . '/' . $targetId . '.pdb',
                'json' => $paths['json'] . '/' . $targetId . '.json',
                'pdb_exists' => file_exists($paths['pdb'] . '/' . $targetId . '.pdb'),
                'json_exists' => file_exists($paths['json'] . '/' . $targetId . '.json')
            ];
            
            $debugInfo['id_mappings'][$type] = [
                'csv_file' => $paths['csv'],
                'csv_exists' => file_exists($paths['csv']),
                'mapping_count' => count($idMapping),
                'target_id' => $targetId,
                'is_mapped' => isset($idMapping[$proteinId])
            ];
        }
        
        http_response_code(404);
        echo json_encode([
            'error' => 'No structure data found for protein_id: ' . $proteinId,
            'debug' => $debugInfo
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>
EOF

# 设置正确的权限
sudo chown apache:apache /var/www/html/api_protein_structure.php
sudo chmod 644 /var/www/html/api_protein_structure.php

echo "✓ API 文件已更新"
echo "✓ 备份文件已保存"

# 重启 Apache
sudo systemctl restart httpd
echo "✓ Apache 已重启"

echo ""
echo "测试 API："
echo "curl 'http://localhost/api_protein_structure.php?protein_id=X0001'"

