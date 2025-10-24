<?php
// 测试搜索功能脚本
header('Content-Type: application/json');

// 引入数据库配置
require_once 'db_config.php';

try {
    // 创建数据库连接
    $pdo = getDbConnection();
    
    // 测试基本查询
    echo "测试数据库连接...\n";
    
    // 检查表是否存在
    $sql = "SHOW TABLES LIKE 'plaszymedb'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✓ 表 'plaszymedb' 存在\n";
        
        // 检查表结构
        $sql = "SHOW COLUMNS FROM plaszymedb";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "表结构:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // 测试数据查询
        $sql = "SELECT COUNT(*) as total FROM plaszymedb";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $count = $stmt->fetch();
        
        echo "✓ 总记录数: " . $count['total'] . "\n";
        
        // 测试搜索查询
        $sql = "SELECT PLZ_ID, enzyme_name, plastic, ec_number FROM plaszymedb LIMIT 3";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $samples = $stmt->fetchAll();
        
        echo "前3条记录:\n";
        foreach ($samples as $sample) {
            echo "  - PLZ_ID: " . $sample['PLZ_ID'] . ", 酶名称: " . $sample['enzyme_name'] . "\n";
        }
        
    } else {
        echo "✗ 表 'plaszymedb' 不存在\n";
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
