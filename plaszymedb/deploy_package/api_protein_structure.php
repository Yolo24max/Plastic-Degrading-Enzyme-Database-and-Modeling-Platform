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
    // 优先使用 original_PLZ_IDs（包含完整的带分号的ID）
    $originalPlzIdIndex = array_search('original_PLZ_IDs', $header);
    $plzIdIndex = array_search('PLZ_ID', $header);
    $proteinIdIndex = array_search('protein_id', $header);
    
    // 选择可用的PLZ_ID列（优先 original_PLZ_IDs）
    $usePlzIdIndex = ($originalPlzIdIndex !== false) ? $originalPlzIdIndex : $plzIdIndex;
    
    if ($usePlzIdIndex === false || $proteinIdIndex === false) {
        fclose($file);
        return $mapping;
    }
    
    // 读取数据行
    // 映射格式：PLZ_ID (hash) => protein_id (XID)
    while (($row = fgetcsv($file)) !== false) {
        if (isset($row[$usePlzIdIndex]) && isset($row[$proteinIdIndex])) {
            $plz_id_value = trim($row[$usePlzIdIndex]);
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
    $plz_id_raw = isset($_GET['plz_id']) ? trim($_GET['plz_id']) : '';
    $data_type = isset($_GET['type']) ? trim($_GET['type']) : 'predicted'; // predicted 或 experimental
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'info'; // info, pdb, json
    
    if (empty($plz_id_raw)) {
        throw new Exception('PLZ_ID parameter cannot be empty');
    }
    
    // 处理多个PLZ_ID（前端可能传递用分号分隔的多个ID）
    // 我们会尝试第一个有效的ID
    $plz_ids_array = array_map('trim', explode(';', $plz_id_raw));
    $plz_id = $plz_ids_array[0]; // 使用第一个ID作为主ID
    
    // Validate data type
    if (!in_array($data_type, ['predicted', 'experimental'])) {
        throw new Exception('Invalid data type');
    }
    
    // 根据数据类型确定文件路径和metadata文件
    $base_path = __DIR__ . '/structure_data';
    
    if ($data_type === 'predicted') {
        $pdb_dir = $base_path . '/predicted_xid/pdb/';
        $json_dir = $base_path . '/predicted_xid/json/';
        // 使用 PLZ_XID.csv 因为它包含 original_PLZ_IDs 列（完整的带分号的ID）
        $metadata_csv = $base_path . '/predicted_xid/PLZ_XID.csv';
    } else {
        $pdb_dir = $base_path . '/experimental_xid/pdb/';
        $json_dir = $base_path . '/experimental_xid/json/';
        $metadata_csv = $base_path . '/experimental_xid/exp_metadata_XID.csv';
    }
    
    // 加载PLZ_ID到XID的映射
    $mapping = loadPlzToXidMapping($metadata_csv);
    
    // 尝试所有提供的PLZ_ID，找到第一个有效的
    $xid = null;
    $valid_plz_id = null;
    
    foreach ($plz_ids_array as $try_plz_id) {
        if (isset($mapping[$try_plz_id])) {
            $xid = $mapping[$try_plz_id];
            $valid_plz_id = $try_plz_id;
            break;
        }
    }
    
    // 如果所有PLZ_ID都无效
    if ($xid === null) {
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => "No {$data_type} structure data available for this protein",
            'plz_id' => $plz_id_raw,
            'tried_ids' => $plz_ids_array,
            'data_type' => $data_type,
            'available' => false
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 使用找到的有效PLZ_ID
    $plz_id = $valid_plz_id;
    
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
