<?php
/**
 * 数据库连接测试脚本
 */

// 启用错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📊 数据库连接测试</h2>";

try {
    // 加载配置
    require_once 'config.php';
    echo "✅ 配置文件加载成功<br><br>";
    
    // 显示连接参数（隐藏密码）
    echo "<h3>连接参数:</h3>";
    echo "🖥️ 主机: " . DB_HOST . "<br>";
    echo "🗄️ 数据库: " . DB_NAME . "<br>";
    echo "👤 用户: " . DB_USER . "<br>";
    echo "🔐 密码: " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : '(空)') . "<br><br>";
    
    // 测试连接
    echo "<h3>连接测试:</h3>";
    $pdo = getDbConnection();
    echo "✅ 数据库连接成功！<br><br>";
    
    // 测试基本查询
    echo "<h3>数据库信息:</h3>";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch()['version'];
    echo "📋 MySQL版本: " . $version . "<br>";
    
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $dbName = $stmt->fetch()['db_name'];
    echo "📂 当前数据库: " . $dbName . "<br>";
    
    // 检查表
    echo "<br><h3>数据表检查:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠️ 数据库中没有表<br>";
        echo "<p>请确保已导入数据库结构和数据</p>";
    } else {
        echo "📋 找到 " . count($tables) . " 个表:<br>";
        foreach ($tables as $table) {
            echo "- " . $table . "<br>";
            
            // 如果是enzymes表，显示记录数
            if ($table === 'enzymes') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enzymes");
                $stmt->execute();
                $count = $stmt->fetch()['count'];
                echo "  📊 包含 {$count} 条记录<br>";
            }
        }
    }
    
    echo "<br>🎉 <strong>数据库连接测试完全成功！</strong>";
    
} catch (Exception $e) {
    echo "❌ <strong>错误:</strong> " . $e->getMessage() . "<br>";
    
    // 提供故障排除建议
    echo "<br><h3>🔧 故障排除建议:</h3>";
    echo "<ul>";
    echo "<li>确保XAMPP的MySQL服务已启动</li>";
    echo "<li>检查用户名和密码是否正确</li>";
    echo "<li>确保数据库 'plaszymedb' 已创建</li>";
    echo "<li>检查MySQL是否允许root用户从localhost连接</li>";
    echo "</ul>";
    
    // 显示详细错误信息
    echo "<br><h4>详细错误信息:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='debug_blast.php'>→ 转到BLAST调试页面</a></p>";
echo "<p><a href='test_blast.php'>→ 转到BLAST测试页面</a></p>";
echo "<p><a href='V9.html'>→ 返回主页面</a></p>";
?>
