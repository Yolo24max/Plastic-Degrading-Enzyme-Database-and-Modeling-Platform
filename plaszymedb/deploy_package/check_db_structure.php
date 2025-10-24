<?php
require_once 'db_config.php';

try {
    $pdo = getDbConnection();
    echo "数据库连接成功！\n\n";
    
    // 查看表结构
    echo "=== PlaszymeDB 表结构 ===\n";
    $stmt = $pdo->query("DESCRIBE plaszymedb");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "字段名: {$column['Field']}, 类型: {$column['Type']}, 可空: {$column['Null']}\n";
    }
    
    echo "\n=== 样本数据 (前3条记录) ===\n";
    $stmt = $pdo->query("SELECT * FROM plaszymedb LIMIT 3");
    $samples = $stmt->fetchAll();
    
    if (!empty($samples)) {
        // 显示字段名
        echo "可用字段: " . implode(", ", array_keys($samples[0])) . "\n\n";
        
        foreach ($samples as $i => $sample) {
            echo "记录 " . ($i + 1) . ":\n";
            foreach ($sample as $field => $value) {
                $display_value = strlen($value) > 100 ? substr($value, 0, 100) . "..." : $value;
                echo "  $field: $display_value\n";
            }
            echo "\n";
        }
    }
    
    echo "=== 统计信息 ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM plaszymedb");
    $total = $stmt->fetch()['total'];
    echo "总记录数: $total\n";
    
    // 检查序列字段（可能的名称）
    $possible_seq_fields = ['sequence', 'protein_sequence', 'seq', 'amino_acid_sequence'];
    foreach ($possible_seq_fields as $field) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE $field IS NOT NULL AND $field != ''");
            $count = $stmt->fetch()['count'];
            echo "有 $field 字段的记录: $count\n";
        } catch (Exception $e) {
            // 字段不存在，跳过
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
