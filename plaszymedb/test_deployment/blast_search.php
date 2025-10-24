<?php
/**
 * PlaszymeDB BLAST搜索API
 * 
 * 实现蛋白质序列相似性搜索功能
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once 'db_config.php';

try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    $sequence = trim(isset($input['sequence']) ? $input['sequence'] : '');
    $maxResults = (int)(isset($input['max_results']) ? $input['max_results'] : 25);
    $threshold = isset($input['threshold']) ? $input['threshold'] : 'medium';
    $plasticFilter = isset($input['plastic_filter']) ? $input['plastic_filter'] : 'all';
    $structureFilter = isset($input['structure_filter']) ? $input['structure_filter'] : 'all';
    
    // 验证输入
    if (empty($sequence)) {
        throw new Exception('Please provide protein sequence');
    }
    
    // 清理序列（移除FASTA头部和空白字符）
    $cleanSequence = cleanProteinSequence($sequence);
    
    if (strlen($cleanSequence) < 10) {
        throw new Exception('Sequence too short, at least 10 amino acids required');
    }
    
    // 验证序列格式
    if (!isValidProteinSequence($cleanSequence)) {
        throw new Exception('Invalid protein sequence format');
    }
    
    // 获取数据库连接
    $pdo = getDbConnection();
    
    // 执行BLAST搜索
    $results = performBlastSearch($pdo, $cleanSequence, $maxResults, $threshold, $plasticFilter, $structureFilter);
    
    // 返回结果
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total_count' => count($results),
        'search_info' => [
            'sequence_length' => strlen($cleanSequence),
            'threshold' => $threshold,
            'max_results' => $maxResults,
            'plastic_filter' => $plasticFilter,
            'structure_filter' => $structureFilter
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * 清理蛋白质序列
 */
function cleanProteinSequence($sequence) {
    // 移除FASTA头部
    $lines = explode("\n", $sequence);
    $cleanLines = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && substr($line, 0, 1) !== '>') {
            $cleanLines[] = $line;
        }
    }
    
    $cleanSequence = implode('', $cleanLines);
    
    // 移除空白字符并转为大写
    return strtoupper(preg_replace('/\s+/', '', $cleanSequence));
}

/**
 * 验证蛋白质序列格式
 */
function isValidProteinSequence($sequence) {
    // 检查是否只包含有效的氨基酸字母
    return preg_match('/^[ACDEFGHIKLMNPQRSTVWYX]+$/', $sequence);
}

/**
 * 执行BLAST搜索
 */
function performBlastSearch($pdo, $sequence, $maxResults, $threshold, $plasticFilter, $structureFilter) {
    // 构建SQL查询
    $sql = "SELECT 
                PLZ_ID,
                protein_id,
                enzyme_name,
                host_organism,
                sequence,
                ec_number,
                pdb_ids,
                can_degrade_PET, can_degrade_PE, can_degrade_PLA, can_degrade_PCL,
                can_degrade_PBS, can_degrade_PBAT, can_degrade_PHB, can_degrade_PU
            FROM plaszymedb 
            WHERE sequence IS NOT NULL 
                AND sequence != ''";
    
    $params = [];
    
    // 添加塑料类型过滤
    if ($plasticFilter !== 'all') {
        $plastic_field = 'can_degrade_' . str_replace(['(', ')', '-', ' '], ['', '', '_', '_'], $plasticFilter);
        $sql .= " AND $plastic_field = 1";
    }
    
    // 添加结构过滤
    if ($structureFilter === 'with_structure') {
        $sql .= " AND (pdb_ids IS NOT NULL AND pdb_ids != '')";
    } elseif ($structureFilter === 'without_structure') {
        $sql .= " AND (pdb_ids IS NULL OR pdb_ids = '')";
    }
    
    $sql .= " LIMIT " . ($maxResults * 3); // 获取更多数据以便计算相似度后过滤
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $enzymes = $stmt->fetchAll();
    
    // 计算序列相似度
    $results = [];
    foreach ($enzymes as $enzyme) {
        if (empty($enzyme['sequence'])) {
            continue;
        }
        
        $similarity = calculateSequenceSimilarity($sequence, $enzyme['sequence']);
        
        // 根据阈值过滤
        $minSimilarity = getMinSimilarity($threshold);
        if ($similarity >= $minSimilarity) {
            // 生成plastic字段
            $plastics = [];
            $plastic_fields = ['PET', 'PE', 'PLA', 'PCL', 'PBS', 'PBAT', 'PHB', 'PU'];
            foreach ($plastic_fields as $plastic_type) {
                $field_name = 'can_degrade_' . $plastic_type;
                if (isset($enzyme[$field_name]) && $enzyme[$field_name] == 1) {
                    $plastics[] = $plastic_type;
                }
            }
            
            $results[] = [
                'plz_id' => $enzyme['PLZ_ID'],
                'protein_id' => $enzyme['protein_id'],
                'enzyme_name' => $enzyme['enzyme_name'],
                'host_organism' => $enzyme['host_organism'],
                'plastic' => !empty($plastics) ? implode(', ', $plastics) : 'N/A',
                'ec_number' => $enzyme['ec_number'],
                'pdb_id' => $enzyme['pdb_ids'],
                'identity' => round($similarity, 1),
                'score' => calculateBlastScore($similarity),
                'coverage' => calculateCoverage($sequence, $enzyme['sequence']),
                'alignment_preview' => generateAlignmentPreview($sequence, $enzyme['sequence'])
            ];
        }
    }
    
    // 按相似度排序
    usort($results, function($a, $b) {
        if ($b['identity'] == $a['identity']) {
            return 0;
        }
        return ($b['identity'] > $a['identity']) ? 1 : -1;
    });
    
    // 限制返回结果数量
    return array_slice($results, 0, $maxResults);
}

/**
 * 计算序列相似度（简化版）
 */
function calculateSequenceSimilarity($seq1, $seq2) {
    $seq1 = strtoupper(trim($seq1));
    $seq2 = strtoupper(trim($seq2));
    
    if (empty($seq1) || empty($seq2)) {
        return 0;
    }
    
    // 使用简单的局部匹配算法
    $len1 = strlen($seq1);
    $len2 = strlen($seq2);
    
    if ($len1 === 0 || $len2 === 0) {
        return 0;
    }
    
    // 计算最长公共子序列
    $matches = 0;
    $windowSize = min(50, min($len1, $len2)); // 使用窗口扫描
    
    for ($i = 0; $i <= $len1 - $windowSize; $i += 10) {
        $window1 = substr($seq1, $i, $windowSize);
        
        for ($j = 0; $j <= $len2 - $windowSize; $j += 10) {
            $window2 = substr($seq2, $j, $windowSize);
            
            // 计算窗口内的匹配度
            $windowMatches = 0;
            for ($k = 0; $k < $windowSize; $k++) {
                if (isset($window1[$k]) && isset($window2[$k]) && $window1[$k] === $window2[$k]) {
                    $windowMatches++;
                }
            }
            
            if ($windowMatches > $matches) {
                $matches = $windowMatches;
            }
        }
    }
    
    // 计算相似度百分比（修正算法）
    $similarity = ($matches / $windowSize) * 100; // 修正：应该除以窗口大小而不是序列长度
    
    // 应用长度相似性调整
    $lengthRatio = min($len1, $len2) / max($len1, $len2);
    $adjustedSimilarity = $similarity * $lengthRatio;
    
    return min(100, max(0, $adjustedSimilarity));
}

/**
 * 获取最小相似度阈值
 */
function getMinSimilarity($threshold) {
    switch ($threshold) {
        case 'low':
            return 5;    // 降低到5%
        case 'medium':
            return 15;   // 降低到15% 
        case 'high':
            return 30;   // 降低到30%
        case 'very_high':
            return 50;   // 降低到50%
        default:
            return 15;   // 默认15%
    }
}

/**
 * 计算BLAST分数
 */
function calculateBlastScore($similarity) {
    return round($similarity * 2.5); // 简单的分数计算
}

/**
 * 计算覆盖度
 */
function calculateCoverage($seq1, $seq2) {
    $len1 = strlen($seq1);
    $len2 = strlen($seq2);
    
    if ($len1 === 0 || $len2 === 0) {
        return 0;
    }
    
    // 简化的覆盖度计算
    return round((min($len1, $len2) / max($len1, $len2)) * 100);
}

/**
 * 生成比对预览
 */
function generateAlignmentPreview($seq1, $seq2) {
    $preview = [];
    $maxLen = min(50, min(strlen($seq1), strlen($seq2)));
    
    if ($maxLen > 0) {
        $query = substr($seq1, 0, $maxLen);
        $subject = substr($seq2, 0, $maxLen);
        
        $match = '';
        for ($i = 0; $i < $maxLen; $i++) {
            if (isset($query[$i]) && isset($subject[$i])) {
                $match .= ($query[$i] === $subject[$i]) ? '|' : ' ';
            } else {
                $match .= ' ';
            }
        }
        
        $preview = [
            'query' => $query,
            'match' => $match,
            'subject' => $subject
        ];
    }
    
    return $preview;
}
?>