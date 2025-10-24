<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入数据库配置
require_once 'db_config.php';

try {
    // 创建数据库连接
    $pdo = getDbConnection();
    
    // 获取总数统计
    $stats = [];
    
    // 总酶数量
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM plaszymedb");
    $stats['total_enzymes'] = $stmt->fetch()['total'];
    
    // 塑料类型数量 - 基于can_degrade_*字段计算
    $plastic_fields = ['PET', 'PE', 'PLA', 'PCL', 'PBS', 'PBAT', 'PHB', 'PU', 
                      'PVA', 'PS', 'PP', 'PHV', 'PHBV', 'NR', 'PEG', 'PES', 'PEF', 
                      'PEA', 'PA', 'PHO', 'PHPV', 'PHBH', 'PHBVH', 'P3HB_co_3MP', 
                      'P3HP', 'P3HV', 'P4HB', 'PMCL', 'PPL', 'PBSeT', 'PBSA',
                      'ECOFLEX', 'ECOVIO_FT', 'Impranil', 'O_PVA'];
    $stats['plastic_types'] = count($plastic_fields);
    
    // 唯一序列数量（基于序列去重）
    $stmt = $pdo->query("SELECT COUNT(DISTINCT sequence) as count FROM plaszymedb WHERE sequence IS NOT NULL AND sequence != ''");
    $stats['unique_sequences'] = $stmt->fetch()['count'];
    
    // 有3D结构的数量
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE pdb_ids IS NOT NULL AND pdb_ids != ''");
    $stats['structures_3d'] = $stmt->fetch()['count'];
    
    // 塑料类型分布 - 基于can_degrade_*字段
    $plastic_distribution = [];
    foreach ($plastic_fields as $plastic_type) {
        $field_name = 'can_degrade_' . $plastic_type;
        try {
            // 使用反引号保护字段名
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE `$field_name` = 1");
            $count = $stmt->fetch()['count'];
            if ($count > 0) {
                $plastic_distribution[] = [
                    'plastic' => $plastic_type,
                    'count' => (int)$count
                ];
            }
        } catch (PDOException $e) {
            // 如果字段不存在，跳过
            continue;
        }
    }
    // 按数量降序排列
    usort($plastic_distribution, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    $stats['plastic_distribution'] = $plastic_distribution;
    
    // 宿主生物分布（前10）
    $stmt = $pdo->query("SELECT host_organism, COUNT(*) as count FROM plaszymedb WHERE host_organism IS NOT NULL AND host_organism != '' GROUP BY host_organism ORDER BY count DESC LIMIT 10");
    $host_distribution = [];
    while ($row = $stmt->fetch()) {
        $host_distribution[] = [
            'host' => $row['host_organism'],
            'count' => $row['count']
        ];
    }
    $stats['host_distribution'] = $host_distribution;
    
    // EC编号分布
    $stmt = $pdo->query("SELECT ec_number, COUNT(*) as count FROM plaszymedb WHERE ec_number IS NOT NULL AND ec_number != '' GROUP BY ec_number ORDER BY count DESC LIMIT 10");
    $ec_distribution = [];
    while ($row = $stmt->fetch()) {
        $ec_distribution[] = [
            'ec_number' => $row['ec_number'],
            'count' => $row['count']
        ];
    }
    $stats['ec_distribution'] = $ec_distribution;
    
    // 返回结果
    echo json_encode([
        'success' => true,
        'statistics' => $stats,
        'last_updated' => date('Y-m-d H:i:s'),
        'message' => 'Statistics retrieved successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // 数据库错误
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage(),
        'statistics' => null
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // 其他错误
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
        'statistics' => null
    ], JSON_UNESCAPED_UNICODE);
}
?>
