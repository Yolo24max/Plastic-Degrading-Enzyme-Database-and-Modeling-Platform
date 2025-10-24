<?php
/**
 * BLAST搜索调试脚本
 */

require_once 'db_config.php';

// 测试序列
$testSequence = "MFERLSPTTMLAIKKYAYWLWLLLALSMPFNYWMARDSEHPAFWAFALVFAVFGVGPVLDMLFGRDPANPDEETQTPQLLGQGYYVLLTLATVPVLIGTLVWAAGVFVDYQGWGWLGRLGWILSMGTVMGAVGIVVAHELIHKDSALEQAAGGILLAAVCYAGFKVEHVRGHHVHVSTPEDASSARFGQSVYQFLPHAYKYNFLNAWRLEAERLKRKGLPVLGWQNELIGWYLLSLALLVGFGWAFGWLGVLFFLGQAFVAVTLLEIINYVEHYGLHRRKGEDGRYERTNHTHSWNSNFVFTNLVLFHLQRHSDHHAYAKRPYQVLRHYDDSPQMPSGYAGMVVLALIPPLWRAVMDPKVKAYYAGEEYQLSAEQSDTPAAS";

// 复制blast_search.php中的函数
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

try {
    echo "=== BLAST搜索调试 ===\n";
    echo "测试序列长度: " . strlen($testSequence) . "\n";
    echo "测试序列前50个字符: " . substr($testSequence, 0, 50) . "\n\n";

    // 连接数据库
    $pdo = getDbConnection();
    
    // 获取数据库中的序列样本
    $sql = "SELECT PLZ_ID, enzyme_name, sequence, plastic FROM plaszymedb WHERE sequence IS NOT NULL AND sequence != '' LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $enzymes = $stmt->fetchAll();
    
    echo "数据库中找到的序列数量: " . count($enzymes) . "\n\n";
    
    if (count($enzymes) == 0) {
        echo "错误: 数据库中没有有效的序列数据!\n";
        exit;
    }
    
    // 测试相似度计算
    echo "=== 相似度计算测试 ===\n";
    $foundMatches = 0;
    
    foreach ($enzymes as $enzyme) {
        $dbSequence = trim($enzyme['sequence']);
        if (empty($dbSequence)) {
            continue;
        }
        
        $similarity = calculateSequenceSimilarity($testSequence, $dbSequence);
        
        echo sprintf(
            "PLZ_ID: %s | 酶名称: %s | 序列长度: %d | 相似度: %.2f%%\n",
            $enzyme['PLZ_ID'],
            $enzyme['enzyme_name'] ?: '未知',
            strlen($dbSequence),
            $similarity
        );
        
        // 测试不同阈值
        $thresholds = ['low' => 20, 'medium' => 40, 'high' => 60, 'very_high' => 80];
        foreach ($thresholds as $name => $minSim) {
            if ($similarity >= $minSim) {
                echo "  -> 通过阈值: $name ($minSim%)\n";
                $foundMatches++;
                break;
            }
        }
        echo "\n";
    }
    
    echo "=== 总结 ===\n";
    echo "找到匹配的序列数量: $foundMatches\n";
    
    if ($foundMatches == 0) {
        echo "\n问题分析:\n";
        echo "1. 相似度算法可能过于严格\n";
        echo "2. 阈值设置可能过高\n";
        echo "3. 数据库序列格式可能有问题\n";
        
        // 测试序列完全相同的情况
        echo "\n=== 测试相同序列 ===\n";
        $sameSimilarity = calculateSequenceSimilarity($testSequence, $testSequence);
        echo "相同序列的相似度: " . $sameSimilarity . "%\n";
        
        // 测试部分匹配
        echo "\n=== 测试部分匹配 ===\n";
        $partialSeq = substr($testSequence, 0, 100);
        $partialSimilarity = calculateSequenceSimilarity($testSequence, $partialSeq);
        echo "部分序列的相似度: " . $partialSimilarity . "%\n";
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>