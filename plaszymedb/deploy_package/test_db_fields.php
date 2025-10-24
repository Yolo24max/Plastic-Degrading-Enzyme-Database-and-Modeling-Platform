<?php
// 临时测试脚本 - 检查数据库字段内容
require_once 'db_config.php';

try {
    $pdo = getDbConnection();
    
    echo "=== 数据库字段测试 ===\n\n";
    
    // 检查表结构
    echo "1. 表结构:\n";
    $stmt = $pdo->query("DESCRIBE plaszymedb");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n2. 塑料类型样本:\n";
    $stmt = $pdo->query("SELECT DISTINCT plastic FROM plaszymedb WHERE plastic IS NOT NULL LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['plastic']}\n";
    }
    
    echo "\n3. 生物分类样本:\n";
    $stmt = $pdo->query("SELECT DISTINCT taxonomy FROM plaszymedb WHERE taxonomy IS NOT NULL LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['taxonomy']}\n";
    }
    
    echo "\n4. 宿主生物样本:\n";
    $stmt = $pdo->query("SELECT DISTINCT host_organism FROM plaszymedb WHERE host_organism IS NOT NULL LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['host_organism']}\n";
    }
    
    echo "\n5. EC编号样本:\n";
    $stmt = $pdo->query("SELECT DISTINCT ec_number FROM plaszymedb WHERE ec_number IS NOT NULL LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['ec_number']}\n";
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
