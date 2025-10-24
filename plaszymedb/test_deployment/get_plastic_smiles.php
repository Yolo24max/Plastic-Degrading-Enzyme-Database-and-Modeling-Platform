<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // 读取 CSV 文件
    $csvFile = __DIR__ . '/plastic_smiles_cleaned.csv';
    
    if (!file_exists($csvFile)) {
        echo json_encode([
            'success' => false,
            'error' => 'CSV file does not exist'
        ]);
        exit;
    }
    
    $plasticData = [];
    $handle = fopen($csvFile, 'r');
    
    if ($handle === false) {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to open CSV file'
        ]);
        exit;
    }
    
    // 跳过标题行
    fgetcsv($handle);
    
    // 读取所有塑料数据
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 2) {
            $plasticName = trim($row[0]);
            $smiles = trim($row[1]);
            
            // 处理特殊情况
            if (strpos($plasticName, 'Blend') !== false) {
                // Blend 类型没有 SMILES
                $plasticData[$plasticName] = [
                    'name' => $plasticName,
                    'smiles' => null,
                    'note' => $smiles
                ];
            } else {
                $plasticData[$plasticName] = [
                    'name' => $plasticName,
                    'smiles' => $smiles,
                    'note' => null
                ];
            }
        }
    }
    
    fclose($handle);
    
    // 如果请求特定塑料类型
    if (isset($_GET['plastic'])) {
        $requestedPlastic = strtoupper(trim($_GET['plastic']));
        
        // 处理名称映射（数据库字段名 -> CSV文件名）
        $nameMapping = [
            'P3HB_CO_3MP' => 'P(3HB-co-3MP)',
            'P3HB-CO-3MP' => 'P(3HB-co-3MP)',
            'O_PVA' => 'O-PVA',
            'O-PVA' => 'O-PVA',
            'PBSEBT' => 'PBSeT',
            'ECOVIO_FT' => 'ECOFLEX' // Ecovio FT 是 Ecoflex 的变体
        ];
        
        // 先检查映射
        if (isset($nameMapping[$requestedPlastic])) {
            $requestedPlastic = $nameMapping[$requestedPlastic];
        }
        
        // 查找匹配的塑料
        $found = false;
        foreach ($plasticData as $key => $data) {
            if (strtoupper($key) === $requestedPlastic) {
                echo json_encode([
                    'success' => true,
                    'data' => $data
                ]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo json_encode([
                'success' => false,
                'error' => 'SMILES data not found for plastic: ' . $requestedPlastic
            ]);
        }
    } else {
        // 返回所有塑料数据
        echo json_encode([
            'success' => true,
            'data' => $plasticData
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

