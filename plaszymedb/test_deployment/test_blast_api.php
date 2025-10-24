<?php
/**
 * 测试修复后的BLAST API
 */

$testSequence = "MFERLSPTTMLAIKKYAYWLWLLLALSMPFNYWMARDSEHPAFWAFALVFAVFGVGPVLDMLFGRDPANPDEETQTPQLLGQGYYVLLTLATVPVLIGTLVWAAGVFVDYQGWGWLGRLGWILSMGTVMGAVGIVVAHELIHKDSALEQAAGGILLAAVCYAGFKVEHVRGHHVHVSTPEDASSARFGQSVYQFLPHAYKYNFLNAWRLEAERLKRKGLPVLGWQNELIGWYLLSLALLVGFGWAFGWLGVLFFLGQAFVAVTLLEIINYVEHYGLHRRKGEDGRYERTNHTHSWNSNFVFTNLVLFHLQRHSDHHAYAKRPYQVLRHYDDSPQMPSGYAGMVVLALIPPLWRAVMDPKVKAYYAGEEYQLSAEQSDTPAAS";

$postData = json_encode([
    'sequence' => $testSequence,
    'max_results' => 10,
    'threshold' => 'medium', // 使用15%阈值
    'plastic_filter' => 'all',
    'structure_filter' => 'all'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/plaszymedb/blast_search.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP状态码: $httpCode\n";
echo "响应内容: \n";

if ($response) {
    $result = json_decode($response, true);
    
    if ($result['success']) {
        echo "✓ BLAST搜索成功!\n";
        echo "找到 " . $result['total_count'] . " 个匹配结果\n\n";
        
        foreach ($result['results'] as $index => $match) {
            echo "结果 " . ($index + 1) . ":\n";
            echo "  PLZ_ID: " . $match['plz_id'] . "\n";
            echo "  酶名称: " . ($match['enzyme_name'] ?: '未知') . "\n";
            echo "  相似度: " . $match['identity'] . "%\n";
            echo "  覆盖度: " . $match['coverage'] . "%\n";
            echo "  分数: " . $match['score'] . "\n";
            echo "  宿主: " . ($match['host_organism'] ?: '未知') . "\n";
            echo "  塑料类型: " . ($match['plastic'] ?: '未知') . "\n\n";
        }
    } else {
        echo "✗ BLAST搜索失败: " . $result['error'] . "\n";
    }
} else {
    echo "✗ 请求失败\n";
}
?>
