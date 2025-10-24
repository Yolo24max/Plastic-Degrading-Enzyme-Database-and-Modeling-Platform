<?php
/**
 * 数据集统计API端点
 * 为PHYLOGENY页面提供动态的序列统计数据
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入数据库配置
require_once 'db_config.php';

try {
    // 创建数据库连接
    $pdo = getDbConnection();
    
    // 获取各数据集的序列统计
    $stats = [];
    
    // 总序列数
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb");
    $stats['comprehensive'] = $stmt->fetch()['count'];
    
    // PET降解酶 - 使用新的can_degrade_PET字段
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE can_degrade_PET = 1");
    $stats['pet'] = $stmt->fetch()['count'];
    
    // PE & PP降解酶 - 使用新的can_degrade_PE字段
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE can_degrade_PE = 1 OR can_degrade_PP = 1");
    $stats['pe_pp'] = $stmt->fetch()['count'];
    
    // EC 3.1酯酶 - 根据实际EC编号格式
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM plaszymedb WHERE ec_number LIKE '3.1.%' OR ec_number LIKE '3.1.1.%'");
    $stmt->execute();
    $stats['ec31'] = $stmt->fetch()['count'];
    
    // 细菌酶 - 根据实际taxonomy字段值
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM plaszymedb WHERE taxonomy = 'Bacteria' OR taxonomy LIKE '%Bacteria%' OR taxonomy LIKE '%Proteobacteria%' OR taxonomy LIKE '%Bacillota%' OR taxonomy LIKE '%Actinomycetota%' OR taxonomy LIKE '%Chloroflexota%' OR taxonomy LIKE '%Pseudomonadota%'");
    $stmt->execute();
    $stats['bacterial'] = $stmt->fetch()['count'];
    
    // 真菌酶 - 根据实际taxonomy字段值
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM plaszymedb WHERE taxonomy = 'Fungi' OR taxonomy LIKE '%Fungi%' OR taxonomy = 'Eukarya'");
    $stmt->execute();
    $stats['fungal'] = $stmt->fetch()['count'];
    
    // 获取最后更新时间（使用当前日期作为默认值）
    $last_update = date('Y-m-d');
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'statistics' => $stats,
        'last_updated' => $last_update,
        'datasets' => [
            'comprehensive' => [
                'name' => 'All Enzymes',
                'description' => 'Complete collection of plastic-degrading enzymes',
                'count' => $stats['comprehensive']
            ],
            'pet' => [
                'name' => 'PET-Degrading Enzymes', 
                'description' => 'Enzymes capable of degrading PET plastic',
                'count' => $stats['pet']
            ],
            'pe_pp' => [
                'name' => 'PE & PP Degraders',
                'description' => 'Enzymes for polyethylene and polypropylene degradation',
                'count' => $stats['pe_pp']
            ],
            'ec31' => [
                'name' => 'EC 3.1 Esterases',
                'description' => 'Esterases with plastic-degrading activity',
                'count' => $stats['ec31']
            ],
            'bacterial' => [
                'name' => 'Bacterial Enzymes',
                'description' => 'Enzymes from bacterial sources',
                'count' => $stats['bacterial']
            ],
            'fungal' => [
                'name' => 'Fungal Enzymes',
                'description' => 'Enzymes from fungal sources',
                'count' => $stats['fungal']
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 错误处理
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'statistics' => [
            'comprehensive' => 0,
            'pet' => 0,
            'pe_pp' => 0,
            'ec31' => 0,
            'bacterial' => 0,
            'fungal' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
