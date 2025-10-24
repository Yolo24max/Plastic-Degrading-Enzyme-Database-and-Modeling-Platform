<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // 数据库连接
    $pdo = new PDO('mysql:host=localhost;dbname=plaszymedb;charset=utf8mb4', 'root', 'yoloShang2025');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 获取PLZ_ID参数
    $plzId = isset($_GET['plz_id']) ? $_GET['plz_id'] : '';
    
    if (empty($plzId)) {
        echo json_encode([
            'success' => false,
            'error' => 'PLZ_ID parameter is missing'
        ]);
        exit;
    }
    
    // 查询数据库
    $stmt = $pdo->prepare("SELECT * FROM plaszymedb WHERE PLZ_ID = ?");
    $stmt->execute([$plzId]);
    $enzyme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enzyme) {
        echo json_encode([
            'success' => false,
            'error' => 'Enzyme data not found'
        ]);
        exit;
    }
    
    // 处理序列信息计算
    $sequence = isset($enzyme['sequence']) ? $enzyme['sequence'] : '';
    $sequenceInfo = calculateSequenceInfo($sequence);
    
    // 返回成功结果（数据库中已经有plastic字段，无需重新生成）
    echo json_encode([
        'success' => true,
        'data' => array_merge($enzyme, $sequenceInfo)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * 计算序列信息
 */
function calculateSequenceInfo($sequence) {
    if (empty($sequence)) {
        return [
            'sequence_length' => 0,
            'molecular_weight' => 0,
            'theoretical_pi' => 0
        ];
    }
    
    // 清理序列，移除非氨基酸字符
    $cleanSequence = preg_replace('/[^ACDEFGHIKLMNPQRSTVWY]/i', '', strtoupper($sequence));
    $length = strlen($cleanSequence);
    
    // 氨基酸分子量表 (Da)
    $aaWeights = [
        'A' => 89.09, 'C' => 121.15, 'D' => 133.10, 'E' => 147.13,
        'F' => 165.19, 'G' => 75.07, 'H' => 155.16, 'I' => 131.17,
        'K' => 146.19, 'L' => 131.17, 'M' => 149.21, 'N' => 132.12,
        'P' => 115.13, 'Q' => 146.15, 'R' => 174.20, 'S' => 105.09,
        'T' => 119.12, 'V' => 117.15, 'W' => 204.23, 'Y' => 181.19
    ];
    
    // 氨基酸pKa值表
    $aaPKa = [
        'D' => 3.9, 'E' => 4.3, 'H' => 6.0, 'C' => 8.3,
        'Y' => 10.1, 'K' => 10.5, 'R' => 12.5
    ];
    
    // 计算分子量
    $molecularWeight = 0;
    for ($i = 0; $i < $length; $i++) {
        $aa = $cleanSequence[$i];
        if (isset($aaWeights[$aa])) {
            $molecularWeight += $aaWeights[$aa];
        }
    }
    
    // 减去水分子重量 (肽键形成时失去的水)
    if ($length > 1) {
        $molecularWeight -= ($length - 1) * 18.015;
    }
    
    // 简化的pI计算 (基于氨基酸组成的近似值)
    $positiveCount = 0;
    $negativeCount = 0;
    
    for ($i = 0; $i < $length; $i++) {
        $aa = $cleanSequence[$i];
        if (in_array($aa, ['K', 'R', 'H'])) {
            $positiveCount++;
        } elseif (in_array($aa, ['D', 'E'])) {
            $negativeCount++;
        }
    }
    
    // 简化的pI估算
    $theoreticalPI = 7.0;
    if ($positiveCount > $negativeCount) {
        $theoreticalPI += ($positiveCount - $negativeCount) * 0.5;
    } elseif ($negativeCount > $positiveCount) {
        $theoreticalPI -= ($negativeCount - $positiveCount) * 0.5;
    }
    
    // 限制pI范围
    $theoreticalPI = max(3.0, min(12.0, $theoreticalPI));
    
    return [
        'sequence_length' => $length,
        'molecular_weight' => round($molecularWeight / 1000, 1), // 转换为kDa
        'theoretical_pi' => round($theoreticalPI, 1)
    ];
}
?>