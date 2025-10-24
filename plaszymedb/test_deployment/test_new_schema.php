<?php
/**
 * 测试新数据库架构
 * Test script for the new database schema
 */

require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><meta charset='utf-8'><title>Database Schema Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #005eb8; }
    h2 { color: #333; margin-top: 30px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #005eb8; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>PlaszymeDB - 新架构测试报告</h1>";
echo "<p>测试时间: " . date('Y-m-d H:i:s') . "</p>";

try {
    // 测试1: 数据库连接
    echo "<h2>1. 数据库连接测试</h2>";
    $pdo = getDbConnection();
    echo "<p class='success'>✓ 数据库连接成功！</p>";
    
    // 测试2: 表结构检查
    echo "<h2>2. 表结构检查</h2>";
    $stmt = $pdo->query("DESCRIBE plaszymedb");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p class='success'>✓ 找到 " . count($fields) . " 个字段</p>";
    
    // 检查关键字段
    echo "<h3>关键字段验证:</h3>";
    $required_fields = ['PLZ_ID', 'enzyme_name', 'sequence', 'ec_number', 'predicted_ec_number', 
                       'can_degrade_PET', 'can_degrade_PE', 'can_degrade_PLA'];
    foreach ($required_fields as $field) {
        $found = false;
        foreach ($fields as $db_field) {
            if ($db_field['Field'] === $field) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "<p class='success'>✓ 字段 '$field' 存在</p>";
        } else {
            echo "<p class='error'>✗ 字段 '$field' 不存在</p>";
        }
    }
    
    // 测试3: 数据统计
    echo "<h2>3. 数据统计</h2>";
    
    // 总记录数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM plaszymedb");
    $total = $stmt->fetch()['total'];
    echo "<p class='info'>总记录数: <strong>$total</strong></p>";
    
    // 有序列的记录
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE sequence IS NOT NULL AND sequence != ''");
    $with_seq = $stmt->fetch()['count'];
    echo "<p class='info'>有序列的记录: <strong>$with_seq</strong></p>";
    
    // 塑料降解统计
    echo "<h3>塑料降解能力统计:</h3>";
    $plastic_types = ['PET', 'PE', 'PLA', 'PCL', 'PBS', 'PBAT', 'PHB', 'PU'];
    echo "<table>";
    echo "<tr><th>塑料类型</th><th>酶数量</th></tr>";
    foreach ($plastic_types as $plastic) {
        $field = "can_degrade_$plastic";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE $field = 1");
        $count = $stmt->fetch()['count'];
        echo "<tr><td>$plastic</td><td>$count</td></tr>";
    }
    echo "</table>";
    
    // 测试4: API端点测试
    echo "<h2>4. API端点功能测试</h2>";
    
    // 获取第一条记录用于测试
    $stmt = $pdo->query("SELECT PLZ_ID FROM plaszymedb LIMIT 1");
    $test_record = $stmt->fetch();
    
    if ($test_record) {
        $test_plz_id = $test_record['PLZ_ID'];
        echo "<p class='info'>使用测试记录: <strong>$test_plz_id</strong></p>";
        
        // 测试search.php
        echo "<h3>search.php 测试:</h3>";
        $search_url = "http://localhost/plaszymedb/search.php?search=";
        echo "<p>测试URL: <a href='$search_url' target='_blank'>$search_url</a></p>";
        
        // 测试get_enzyme_detail.php
        echo "<h3>get_enzyme_detail.php 测试:</h3>";
        $detail_url = "http://localhost/plaszymedb/get_enzyme_detail.php?plz_id=$test_plz_id";
        echo "<p>测试URL: <a href='$detail_url' target='_blank'>$detail_url</a></p>";
        
        // 测试detail.php
        echo "<h3>detail.php 测试:</h3>";
        $detail2_url = "http://localhost/plaszymedb/detail.php?plz_id=$test_plz_id";
        echo "<p>测试URL: <a href='$detail2_url' target='_blank'>$detail2_url</a></p>";
        
        // 测试stats.php
        echo "<h3>stats.php 测试:</h3>";
        $stats_url = "http://localhost/plaszymedb/stats.php";
        echo "<p>测试URL: <a href='$stats_url' target='_blank'>$stats_url</a></p>";
        
        // 测试api_dataset_stats.php
        echo "<h3>api_dataset_stats.php 测试:</h3>";
        $api_stats_url = "http://localhost/plaszymedb/api_dataset_stats.php";
        echo "<p>测试URL: <a href='$api_stats_url' target='_blank'>$api_stats_url</a></p>";
    } else {
        echo "<p class='error'>✗ 数据库中没有记录可供测试</p>";
    }
    
    // 测试5: 前端页面
    echo "<h2>5. 前端页面</h2>";
    $v9_url = "http://localhost/plaszymedb/V9.html";
    echo "<p>主页面: <a href='$v9_url' target='_blank'>$v9_url</a></p>";
    
    // 测试总结
    echo "<h2>测试总结</h2>";
    echo "<div class='info'>";
    echo "<p class='success'>✓ 所有基本测试已完成</p>";
    echo "<p>请点击上方链接手动测试各API端点的实际功能</p>";
    echo "<p>如果所有链接都能正常返回JSON数据（或正常显示页面），则表示迁移成功</p>";
    echo "</div>";
    
    // 数据库架构建议
    echo "<h2>6. 数据库优化建议</h2>";
    echo "<div class='info'>";
    echo "<ul>";
    echo "<li>建议在PLZ_ID字段上添加PRIMARY KEY或UNIQUE索引</li>";
    echo "<li>建议在ec_number字段上添加索引以提高搜索性能</li>";
    echo "<li>建议在taxonomy和host_organism字段上添加索引</li>";
    echo "<li>建议在常用的can_degrade_*字段（如PET、PE、PLA）上添加索引</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ 错误: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

