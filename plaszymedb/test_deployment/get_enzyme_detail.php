<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入数据库配置
require_once 'db_config.php';

try {
    // 创建数据库连接
    $pdo = getDbConnection();
    
    // 获取PLZ_ID参数
    $plzId = isset($_GET['plz_id']) ? trim($_GET['plz_id']) : '';
    
    if (empty($plzId)) {
        throw new Exception('PLZ_ID parameter cannot be empty');
    }
    
    // 构建SQL查询，获取酶的所有详细信息
    $sql = "SELECT * FROM plaszymedb 
            WHERE PLZ_ID = :plz_id
            LIMIT 1";
    
    // 执行查询
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':plz_id', $plzId, PDO::PARAM_STR);
    $stmt->execute();
    
    $enzyme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($enzyme) {
        // 处理空值，将NULL或空字符串替换为更友好的显示
        foreach ($enzyme as $key => $value) {
            if ($value === null || trim($value) === '') {
                $enzyme[$key] = '/';
            }
        }
        
        // 添加plastic字段（基于can_degrade_*字段）
        $plastics = [];
        $plastic_fields = ['PET', 'PE', 'PLA', 'PCL', 'PBS', 'PBAT', 'PHB', 'PU', 
                          'PVA', 'PS', 'PP', 'PHV', 'PHBV', 'NR', 'PEG', 'PES', 'PEF', 
                          'PEA', 'PA', 'PHO', 'PHPV', 'PHBH', 'PHBVH', 'P3HB_co_3MP', 
                          'P3HP', 'P3HV', 'P4HB', 'PMCL', 'PPL', 'PBSeT', 'PBSA',
                          'ECOFLEX', 'ECOVIO_FT', 'Impranil', 'O_PVA'];
        
        foreach ($plastic_fields as $plastic_type) {
            $field_name = 'can_degrade_' . $plastic_type;
            if (isset($enzyme[$field_name]) && $enzyme[$field_name] == 1) {
                $plastics[] = $plastic_type;
            }
        }
        $enzyme['plastic'] = !empty($plastics) ? implode(', ', $plastics) : 'N/A';
        
        // 返回成功结果
        echo json_encode([
            'success' => true,
            'enzyme' => $enzyme,
            'message' => 'Enzyme details retrieved successfully'
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // 未找到对应的酶
        echo json_encode([
            'success' => false,
            'enzyme' => null,
            'message' => "Enzyme information not found for PLZ_ID '{$plzId}'"
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    // 数据库错误
    echo json_encode([
        'success' => false,
        'enzyme' => null,
        'message' => 'Database connection error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // 其他错误
    echo json_encode([
        'success' => false,
        'enzyme' => null,
        'message' => 'Query error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
