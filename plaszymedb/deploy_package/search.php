<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入数据库配置
require_once 'db_config.php';

try {
    // 创建数据库连接
    $pdo = getDbConnection();
    
    // 获取搜索参数
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $plastic = isset($_GET['plastic']) ? trim($_GET['plastic']) : '';
    $ec = isset($_GET['ec']) ? trim($_GET['ec']) : '';
    $host_filter = isset($_GET['host']) ? trim($_GET['host']) : '';
    
    // 构建SQL查询 - 根据新的数据库字段结构
    $sql = "SELECT PLZ_ID, protein_id, enzyme_name, ec_number, taxonomy, host_organism, 
                   sequence, genbank_ids, uniprot_ids, pdb_ids, refseq_ids,
                   gene_name, reference, source_name, sequence_source, structure_source,
                   ec_number_source, predicted_ec_number, ec_prediction_source,
                   can_degrade_PET, can_degrade_PE, can_degrade_PLA, can_degrade_PCL,
                   can_degrade_PBS, can_degrade_PBAT, can_degrade_PHB, can_degrade_PU
            FROM plaszymedb WHERE 1=1";
    
    $params = array();
    
    // 添加全文搜索条件
    if (!empty($search)) {
        $sql .= " AND (
            PLZ_ID LIKE :search1 OR
            enzyme_name LIKE :search2 OR
            ec_number LIKE :search3 OR
            gene_name LIKE :search4 OR
            taxonomy LIKE :search5 OR
            host_organism LIKE :search6 OR
            sequence LIKE :search7 OR
            genbank_ids LIKE :search8 OR
            uniprot_ids LIKE :search9 OR
            pdb_ids LIKE :search10 OR
            refseq_ids LIKE :search11 OR
            reference LIKE :search12 OR
            source_name LIKE :search13 OR
            sequence_source LIKE :search14 OR
            structure_source LIKE :search15 OR
            ec_number_source LIKE :search16 OR
            predicted_ec_number LIKE :search17
        )";
        
        $searchTerm = '%' . $search . '%';
        for ($i = 1; $i <= 17; $i++) {
            $params[":search$i"] = $searchTerm;
        }
    }
    
    // 添加塑料类型筛选 - 使用新的can_degrade_*字段
    if (!empty($plastic) && $plastic !== 'All' && $plastic !== '全部') {
        $plastic_field = 'can_degrade_' . str_replace(['(', ')', '-', ' '], ['', '', '_', '_'], $plastic);
        $sql .= " AND $plastic_field = 1";
    }
    
    // 添加EC编号筛选
    if (!empty($ec) && $ec !== 'All' && $ec !== '全部') {
        // 对于EC编号，使用更精确的匹配方式以处理复合EC编号
        $sql .= " AND (ec_number = :ec_exact OR ec_number LIKE :ec_start OR ec_number LIKE :ec_middle OR ec_number LIKE :ec_end)";
        $params[':ec_exact'] = $ec;                    // 精确匹配
        $params[':ec_start'] = $ec . ';%';            // 开头匹配（后跟分号）
        $params[':ec_middle'] = '%; ' . $ec . ';%';    // 中间匹配（前后都有分号）
        $params[':ec_end'] = '%; ' . $ec;             // 结尾匹配（前面有分号）
    }
    
    // 添加宿主生物筛选
    if (!empty($host_filter) && $host_filter !== 'All' && $host_filter !== '全部') {
        $sql .= " AND (taxonomy LIKE :host OR host_organism LIKE :host2)";
        $params[':host'] = '%' . $host_filter . '%';
        $params[':host2'] = '%' . $host_filter . '%';
    }
    
    // 添加排序和限制
    $sql .= " ORDER BY PLZ_ID LIMIT 50";
    
    // 执行查询
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 为每个结果添加plastic字段（基于can_degrade_*字段）
    foreach ($results as &$result) {
        $plastics = [];
        $plastic_fields = ['PET', 'PE', 'PLA', 'PCL', 'PBS', 'PBAT', 'PHB', 'PU', 
                          'PVA', 'PS', 'PP', 'PHV', 'PHBV', 'NR', 'PEG', 'PES', 'PEF', 
                          'PEA', 'PA', 'PHO', 'PHPV', 'PHBH', 'PHBVH', 'P3HB_co_3MP', 
                          'P3HP', 'P3HV', 'P4HB', 'PMCL', 'PPL', 'PBSeT', 'PBSA',
                          'ECOFLEX', 'ECOVIO_FT', 'Impranil', 'O_PVA'];
        
        foreach ($plastic_fields as $plastic_type) {
            $field_name = 'can_degrade_' . $plastic_type;
            if (isset($result[$field_name]) && $result[$field_name] == 1) {
                $plastics[] = $plastic_type;
            }
        }
        $result['plastic'] = !empty($plastics) ? implode(', ', $plastics) : 'N/A';
    }
    
    // 获取总数（不包含LIMIT的查询）
    $countSql = str_replace(
        "SELECT PLZ_ID, protein_id, enzyme_name, ec_number, taxonomy, host_organism, 
                   sequence, genbank_ids, uniprot_ids, pdb_ids, refseq_ids,
                   gene_name, reference, source_name, sequence_source, structure_source,
                   ec_number_source, predicted_ec_number, ec_prediction_source,
                   can_degrade_PET, can_degrade_PE, can_degrade_PLA, can_degrade_PCL,
                   can_degrade_PBS, can_degrade_PBAT, can_degrade_PHB, can_degrade_PU
            FROM plaszymedb", 
        "SELECT COUNT(*) as total FROM plaszymedb", 
        $sql
    );
    $countSql = str_replace(" ORDER BY PLZ_ID LIMIT 50", "", $countSql);
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 返回结果
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => $totalCount,
        'message' => 'Search completed successfully',
        'debug' => [
            'sql' => $sql,
            'params' => $params,
            'search_term' => $search
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // 数据库错误
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage(),
        'results' => [],
        'total' => 0
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // 其他错误
    echo json_encode([
        'success' => false,
        'message' => 'Search error: ' . $e->getMessage(),
        'results' => [],
        'total' => 0
    ], JSON_UNESCAPED_UNICODE);
}
?>