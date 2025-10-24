<?php
/**
 * API测试文件 - 验证BROWSE页面的数据库集成
 */

echo "<h1>PlaszymeDB API 测试</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}h2{color:#005eb8;border-bottom:2px solid #e0e0e0;padding-bottom:5px;}pre{background:#f8f9fa;padding:15px;border-radius:5px;overflow-x:auto;}.success{color:#28a745;}.error{color:#dc3545;}</style>\n";

// 测试数据库连接
echo "<h2>1. 数据库连接测试</h2>\n";
try {
    require_once 'db_config.php';
    $pdo = getDbConnection();
    echo "<p class='success'>✓ 数据库连接成功</p>\n";
    
    // 测试表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'plaszymedb'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ plaszymedb表存在</p>\n";
    } else {
        echo "<p class='error'>✗ plaszymedb表不存在</p>\n";
    }
    
    // 测试数据量
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb");
    $count = $stmt->fetch()['count'];
    echo "<p class='success'>✓ 数据库包含 {$count} 条记录</p>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ 数据库连接失败: " . $e->getMessage() . "</p>\n";
}

// 测试统计API
echo "<h2>2. 统计API测试</h2>\n";
$stats_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/stats.php';
$stats_response = @file_get_contents($stats_url);
if ($stats_response) {
    $stats_data = json_decode($stats_response, true);
    if ($stats_data && $stats_data['success']) {
        echo "<p class='success'>✓ 统计API工作正常</p>\n";
        echo "<pre>" . json_encode($stats_data['statistics'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
    } else {
        echo "<p class='error'>✗ 统计API返回错误</p>\n";
        echo "<pre>" . htmlspecialchars($stats_response) . "</pre>\n";
    }
} else {
    echo "<p class='error'>✗ 无法访问统计API</p>\n";
}

// 测试搜索API
echo "<h2>3. 搜索API测试</h2>\n";
$search_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/search.php?search=PET';
$search_response = @file_get_contents($search_url);
if ($search_response) {
    $search_data = json_decode($search_response, true);
    if ($search_data && $search_data['success']) {
        echo "<p class='success'>✓ 搜索API工作正常</p>\n";
        echo "<p>搜索结果: {$search_data['total']} 条记录</p>\n";
        if (!empty($search_data['results'])) {
            echo "<p>示例结果:</p>\n";
            echo "<pre>" . json_encode($search_data['results'][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
        }
    } else {
        echo "<p class='error'>✗ 搜索API返回错误</p>\n";
        echo "<pre>" . htmlspecialchars($search_response) . "</pre>\n";
    }
} else {
    echo "<p class='error'>✗ 无法访问搜索API</p>\n";
}

// 测试详情API
echo "<h2>4. 详情API测试</h2>\n";
try {
    // 获取第一个PLZ_ID进行测试
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT PLZ_ID FROM plaszymedb LIMIT 1");
    $first_record = $stmt->fetch();
    
    if ($first_record) {
        $detail_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/detail.php?id=' . urlencode($first_record['PLZ_ID']);
        $detail_response = @file_get_contents($detail_url);
        if ($detail_response) {
            $detail_data = json_decode($detail_response, true);
            if ($detail_data && $detail_data['success']) {
                echo "<p class='success'>✓ 详情API工作正常</p>\n";
                echo "<p>测试PLZ_ID: {$first_record['PLZ_ID']}</p>\n";
                echo "<p>酶名称: " . ($detail_data['data']['enzyme_name'] ?? 'N/A') . "</p>\n";
            } else {
                echo "<p class='error'>✗ 详情API返回错误</p>\n";
                echo "<pre>" . htmlspecialchars($detail_response) . "</pre>\n";
            }
        } else {
            echo "<p class='error'>✗ 无法访问详情API</p>\n";
        }
    } else {
        echo "<p class='error'>✗ 数据库中没有记录可供测试</p>\n";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ 详情API测试失败: " . $e->getMessage() . "</p>\n";
}

echo "<h2>5. 测试完成</h2>\n";
echo "<p>如果所有测试都显示 ✓，那么BROWSE页面应该可以正常工作。</p>\n";
echo "<p><a href='V9.html'>点击这里访问PlaszymeDB主页</a></p>\n";
?>
