<?php
/**
 * 测试蛋白质结构API
 * 验证新的structure_data路径配置是否正确
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>结构API测试</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
h1 { color: #333; }
.test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #4CAF50; }
.test-section.error { border-color: #f44336; }
.test-section h3 { margin-top: 0; color: #4CAF50; }
.test-section.error h3 { color: #f44336; }
pre { background: #eee; padding: 10px; overflow-x: auto; border-radius: 4px; }
.success { color: #4CAF50; font-weight: bold; }
.error { color: #f44336; font-weight: bold; }
.info { color: #2196F3; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #4CAF50; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
</style></head><body><div class='container'>";

echo "<h1>🧪 蛋白质结构API测试</h1>";
echo "<p class='info'>测试时间: " . date('Y-m-d H:i:s') . "</p>";

// 测试1：检查目录结构
echo "<div class='test-section'>";
echo "<h3>📁 测试 1: 检查目录结构</h3>";

$base_path = __DIR__ . '/structure_data';
$dirs_to_check = [
    'structure_data' => $base_path,
    'predicted_xid' => $base_path . '/predicted_xid',
    'predicted_xid/pdb' => $base_path . '/predicted_xid/pdb',
    'predicted_xid/json' => $base_path . '/predicted_xid/json',
    'experimental_xid' => $base_path . '/experimental_xid',
    'experimental_xid/pdb' => $base_path . '/experimental_xid/pdb',
    'experimental_xid/json' => $base_path . '/experimental_xid/json',
];

echo "<table><tr><th>目录</th><th>状态</th><th>文件数</th></tr>";
$all_dirs_exist = true;
foreach ($dirs_to_check as $name => $path) {
    $exists = is_dir($path);
    $file_count = $exists ? count(glob($path . '/*')) : 0;
    echo "<tr>";
    echo "<td>" . htmlspecialchars($name) . "</td>";
    echo "<td>" . ($exists ? "<span class='success'>✓ 存在</span>" : "<span class='error'>✗ 不存在</span>") . "</td>";
    echo "<td>" . ($exists ? $file_count : 'N/A') . "</td>";
    echo "</tr>";
    if (!$exists) $all_dirs_exist = false;
}
echo "</table>";

if ($all_dirs_exist) {
    echo "<p class='success'>✓ 所有目录都存在</p>";
} else {
    echo "<p class='error'>✗ 部分目录不存在</p>";
}
echo "</div>";

// 测试2：检查metadata文件
echo "<div class='test-section'>";
echo "<h3>📄 测试 2: 检查Metadata文件</h3>";

$metadata_files = [
    '预测数据' => $base_path . '/predicted_xid/pred_metadata_XID.csv',
    '实验数据' => $base_path . '/experimental_xid/exp_metadata_XID.csv',
];

echo "<table><tr><th>类型</th><th>文件</th><th>状态</th><th>记录数</th></tr>";
foreach ($metadata_files as $type => $file) {
    $exists = file_exists($file);
    $count = 0;
    if ($exists) {
        $lines = file($file);
        $count = count($lines) - 1; // 减去表头
    }
    echo "<tr>";
    echo "<td>" . htmlspecialchars($type) . "</td>";
    echo "<td>" . htmlspecialchars(basename($file)) . "</td>";
    echo "<td>" . ($exists ? "<span class='success'>✓ 存在</span>" : "<span class='error'>✗ 不存在</span>") . "</td>";
    echo "<td>" . ($exists ? $count : 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 测试3：测试PLZ_ID到XID映射
echo "<div class='test-section'>";
echo "<h3>🔗 测试 3: PLZ_ID到XID映射</h3>";

// 从预测数据读取一些样例
$pred_metadata = $base_path . '/predicted_xid/pred_metadata_XID.csv';
if (file_exists($pred_metadata)) {
    $file = fopen($pred_metadata, 'r');
    $header = fgetcsv($file);
    
    echo "<h4>预测数据样例 (前5条):</h4>";
    echo "<table><tr><th>PLZ_ID</th><th>XID</th><th>pLDDT</th><th>pTM</th></tr>";
    
    $plzIdIndex = array_search('PLZ_ID', $header);
    $proteinIdIndex = array_search('protein_id', $header);
    $plddtIndex = array_search('pLDDT', $header);
    $ptmIndex = array_search('pTM', $header);
    
    $sample_plz_ids = [];
    $count = 0;
    while (($row = fgetcsv($file)) !== false && $count < 5) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row[$plzIdIndex]) . "</td>";
        echo "<td>" . htmlspecialchars($row[$proteinIdIndex]) . "</td>";
        echo "<td>" . htmlspecialchars($row[$plddtIndex]) . "</td>";
        echo "<td>" . htmlspecialchars($row[$ptmIndex]) . "</td>";
        echo "</tr>";
        $sample_plz_ids[] = $row[$plzIdIndex];
        $count++;
    }
    echo "</table>";
    fclose($file);
} else {
    echo "<p class='error'>预测数据metadata文件不存在</p>";
    $sample_plz_ids = [];
}

// 从实验数据读取样例
$exp_metadata = $base_path . '/experimental_xid/exp_metadata_XID.csv';
if (file_exists($exp_metadata)) {
    $file = fopen($exp_metadata, 'r');
    $header = fgetcsv($file);
    
    echo "<h4>实验数据样例 (前5条):</h4>";
    echo "<table><tr><th>PLZ_ID</th><th>XID</th><th>PDB ID</th><th>分辨率</th></tr>";
    
    $plzIdIndex = array_search('PLZ_ID', $header);
    $proteinIdIndex = array_search('protein_id', $header);
    $pdbIdIndex = array_search('pdb_id', $header);
    $resolutionIndex = array_search('resolution', $header);
    
    $sample_exp_plz_ids = [];
    $count = 0;
    while (($row = fgetcsv($file)) !== false && $count < 5) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row[$plzIdIndex]) . "</td>";
        echo "<td>" . htmlspecialchars($row[$proteinIdIndex]) . "</td>";
        echo "<td>" . htmlspecialchars($row[$pdbIdIndex]) . "</td>";
        echo "<td>" . htmlspecialchars($row[$resolutionIndex]) . "</td>";
        echo "</tr>";
        $sample_exp_plz_ids[] = $row[$plzIdIndex];
        $count++;
    }
    echo "</table>";
    fclose($file);
} else {
    echo "<p class='error'>实验数据metadata文件不存在</p>";
    $sample_exp_plz_ids = [];
}
echo "</div>";

// 测试4：测试API调用 - 预测数据
if (!empty($sample_plz_ids)) {
    echo "<div class='test-section'>";
    echo "<h3>🧬 测试 4: API调用 - 预测结构</h3>";
    
    $test_plz_id = $sample_plz_ids[0];
    echo "<p>测试PLZ_ID: <strong>$test_plz_id</strong></p>";
    
    // 测试info接口
    $info_url = "api_protein_structure.php?plz_id={$test_plz_id}&type=predicted&action=info";
    echo "<h4>Info API测试:</h4>";
    echo "<p>URL: <a href='$info_url' target='_blank'>$info_url</a></p>";
    
    $info_response = @file_get_contents($info_url);
    if ($info_response) {
        $info_data = json_decode($info_response, true);
        if ($info_data) {
            echo "<pre>" . htmlspecialchars(json_encode($info_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            
            if (isset($info_data['files']['pdb_exists']) && $info_data['files']['pdb_exists']) {
                echo "<p class='success'>✓ PDB文件存在</p>";
            } else {
                echo "<p class='error'>✗ PDB文件不存在</p>";
            }
        } else {
            echo "<p class='error'>✗ JSON解析失败</p>";
            echo "<pre>" . htmlspecialchars($info_response) . "</pre>";
        }
    } else {
        echo "<p class='error'>✗ API调用失败</p>";
    }
    
    // 测试PDB下载链接
    $pdb_url = "api_protein_structure.php?plz_id={$test_plz_id}&type=predicted&action=pdb";
    echo "<h4>PDB下载测试:</h4>";
    echo "<p>URL: <a href='$pdb_url' target='_blank'>$pdb_url</a> (点击测试下载)</p>";
    
    echo "</div>";
}

// 测试5：测试API调用 - 实验数据
if (!empty($sample_exp_plz_ids)) {
    echo "<div class='test-section'>";
    echo "<h3>🔬 测试 5: API调用 - 实验结构</h3>";
    
    $test_plz_id = $sample_exp_plz_ids[0];
    echo "<p>测试PLZ_ID: <strong>$test_plz_id</strong></p>";
    
    // 测试info接口
    $info_url = "api_protein_structure.php?plz_id={$test_plz_id}&type=experimental&action=info";
    echo "<h4>Info API测试:</h4>";
    echo "<p>URL: <a href='$info_url' target='_blank'>$info_url</a></p>";
    
    $info_response = @file_get_contents($info_url);
    if ($info_response) {
        $info_data = json_decode($info_response, true);
        if ($info_data) {
            echo "<pre>" . htmlspecialchars(json_encode($info_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            
            if (isset($info_data['files']['pdb_exists']) && $info_data['files']['pdb_exists']) {
                echo "<p class='success'>✓ PDB文件存在</p>";
            } else {
                echo "<p class='error'>✗ PDB文件不存在</p>";
            }
        } else {
            echo "<p class='error'>✗ JSON解析失败</p>";
            echo "<pre>" . htmlspecialchars($info_response) . "</pre>";
        }
    } else {
        echo "<p class='error'>✗ API调用失败</p>";
    }
    
    // 测试PDB下载链接
    $pdb_url = "api_protein_structure.php?plz_id={$test_plz_id}&type=experimental&action=pdb";
    echo "<h4>PDB下载测试:</h4>";
    echo "<p>URL: <a href='$pdb_url' target='_blank'>$pdb_url</a> (点击测试下载)</p>";
    
    echo "</div>";
}

// 测试总结
echo "<div class='test-section'>";
echo "<h3>📊 测试总结</h3>";
echo "<ul>";
echo "<li>目录结构: " . ($all_dirs_exist ? "<span class='success'>✓ 通过</span>" : "<span class='error'>✗ 失败</span>") . "</li>";
echo "<li>Metadata文件: " . (file_exists($pred_metadata) && file_exists($exp_metadata) ? "<span class='success'>✓ 通过</span>" : "<span class='error'>✗ 失败</span>") . "</li>";
echo "<li>API测试: 请查看上方测试结果</li>";
echo "</ul>";

echo "<h4>快速测试链接:</h4>";
echo "<ul>";
if (!empty($sample_plz_ids)) {
    foreach (array_slice($sample_plz_ids, 0, 3) as $plz_id) {
        echo "<li>预测结构: <a href='api_protein_structure.php?plz_id={$plz_id}&type=predicted&action=info' target='_blank'>{$plz_id}</a></li>";
    }
}
if (!empty($sample_exp_plz_ids)) {
    foreach (array_slice($sample_exp_plz_ids, 0, 3) as $plz_id) {
        echo "<li>实验结构: <a href='api_protein_structure.php?plz_id={$plz_id}&type=experimental&action=info' target='_blank'>{$plz_id}</a></li>";
    }
}
echo "</ul>";
echo "</div>";

echo "</div></body></html>";
?>

