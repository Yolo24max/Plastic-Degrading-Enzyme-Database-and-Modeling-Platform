<?php
/**
 * PlaszymeDB 序列导出工具
 * 用于生成iTOL系统发育树构建所需的FASTA文件
 */

require_once 'db_config.php';

// 创建输出目录
$output_dir = __DIR__ . '/phylogeny_data';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

try {
    $pdo = getDbConnection();
    echo "数据库连接成功！\n";
    
    // 定义数据集查询
    $datasets = [
        'comprehensive' => [
            'name' => '综合塑料降解酶',
            'query' => "SELECT PLZ_ID, sequence, host_organism, enzyme_name, ec_number 
                       FROM plaszymedb 
                       WHERE sequence IS NOT NULL AND sequence != ''",
            'filename' => 'comprehensive_enzymes.fasta'
        ],
        'pet' => [
            'name' => 'PET降解酶',
            'query' => "SELECT PLZ_ID, sequence, host_organism, enzyme_name, ec_number 
                       FROM plaszymedb 
                       WHERE can_degrade_PET = 1
                       AND sequence IS NOT NULL AND sequence != ''",
            'filename' => 'pet_enzymes.fasta'
        ],
        'pe_pp' => [
            'name' => 'PE和PP降解酶',
            'query' => "SELECT PLZ_ID, sequence, host_organism, enzyme_name, ec_number 
                       FROM plaszymedb 
                       WHERE (can_degrade_PE = 1 OR can_degrade_PP = 1)
                       AND sequence IS NOT NULL AND sequence != ''",
            'filename' => 'pe_pp_enzymes.fasta'
        ],
        'ec31' => [
            'name' => 'EC 3.1酯酶',
            'query' => "SELECT PLZ_ID, sequence, host_organism, enzyme_name, ec_number 
                       FROM plaszymedb 
                       WHERE (ec_number LIKE '3.1.%' OR ec_number LIKE '3.1.1.%')
                       AND sequence IS NOT NULL AND sequence != ''",
            'filename' => 'ec31_esterases.fasta'
        ],
        'bacterial' => [
            'name' => '细菌酶',
            'query' => "SELECT PLZ_ID, sequence, host_organism, enzyme_name, taxonomy 
                       FROM plaszymedb 
                       WHERE (taxonomy = 'Bacteria' OR taxonomy LIKE '%Bacteria%' 
                             OR taxonomy LIKE '%Proteobacteria%' OR taxonomy LIKE '%Bacillota%' 
                             OR taxonomy LIKE '%Actinomycetota%' OR taxonomy LIKE '%Chloroflexota%' 
                             OR taxonomy LIKE '%Pseudomonadota%')
                       AND sequence IS NOT NULL AND sequence != ''",
            'filename' => 'bacterial_enzymes.fasta'
        ],
        'fungal' => [
            'name' => '真菌酶',
            'query' => "SELECT PLZ_ID, sequence, host_organism, enzyme_name, taxonomy 
                       FROM plaszymedb 
                       WHERE (taxonomy = 'Fungi' OR taxonomy LIKE '%Fungi%' OR taxonomy = 'Eukarya')
                       AND sequence IS NOT NULL AND sequence != ''",
            'filename' => 'fungal_enzymes.fasta'
        ]
    ];
    
    // 导出统计
    $export_stats = [];
    
    foreach ($datasets as $dataset_key => $dataset_info) {
        echo "\n处理数据集: {$dataset_info['name']}\n";
        echo "查询: {$dataset_info['query']}\n";
        
        $stmt = $pdo->prepare($dataset_info['query']);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $count = count($results);
        echo "找到 $count 条序列记录\n";
        
        if ($count > 0) {
            $fasta_file = $output_dir . '/' . $dataset_info['filename'];
            $fasta_content = '';
            
            foreach ($results as $row) {
                // 构建FASTA头部信息
                $accession = $row['PLZ_ID'] ?? 'Unknown';
                $organism = $row['host_organism'] ?? 'Unknown';
                $enzyme_name = $row['enzyme_name'] ?? 'Enzyme';
                
                // 添加额外信息（如果存在）
                $extra_info = '';
                if (isset($row['ec_number']) && $row['ec_number'] != '') {
                    $extra_info .= " ec={$row['ec_number']}";
                }
                if (isset($row['taxonomy']) && $row['taxonomy'] != '') {
                    $extra_info .= " taxonomy={$row['taxonomy']}";
                }
                
                // 清理序列数据
                $sequence = preg_replace('/[^ACDEFGHIKLMNPQRSTVWY]/', '', strtoupper($row['sequence']));
                
                if (strlen($sequence) > 50) { // 只保留有效长度的序列
                    $header = ">{$accession} {$organism} {$enzyme_name}{$extra_info}";
                    $fasta_content .= $header . "\n";
                    
                    // 格式化序列（每行80个字符）
                    $formatted_sequence = chunk_split($sequence, 80, "\n");
                    $fasta_content .= rtrim($formatted_sequence) . "\n";
                }
            }
            
            // 写入FASTA文件
            file_put_contents($fasta_file, $fasta_content);
            echo "FASTA文件已生成: $fasta_file\n";
            
            // 重新计算有效序列数
            $valid_sequences = substr_count($fasta_content, '>');
            $export_stats[$dataset_key] = [
                'name' => $dataset_info['name'],
                'total_records' => $count,
                'valid_sequences' => $valid_sequences,
                'filename' => $dataset_info['filename']
            ];
        } else {
            echo "警告: 没有找到符合条件的序列\n";
            $export_stats[$dataset_key] = [
                'name' => $dataset_info['name'],
                'total_records' => 0,
                'valid_sequences' => 0,
                'filename' => $dataset_info['filename']
            ];
        }
        
        echo str_repeat('-', 50) . "\n";
    }
    
    // 生成导出报告
    $report_file = $output_dir . '/export_report.txt';
    $report_content = "PlaszymeDB 序列导出报告\n";
    $report_content .= "导出时间: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($export_stats as $dataset_key => $stats) {
        $report_content .= "数据集: {$stats['name']}\n";
        $report_content .= "  总记录数: {$stats['total_records']}\n";
        $report_content .= "  有效序列数: {$stats['valid_sequences']}\n";
        $report_content .= "  文件名: {$stats['filename']}\n\n";
    }
    
    file_put_contents($report_file, $report_content);
    
    echo "\n=== 导出完成 ===\n";
    echo "导出报告已保存到: $report_file\n";
    echo "FASTA文件位置: $output_dir/\n\n";
    
    // 显示统计摘要
    echo "导出统计摘要:\n";
    foreach ($export_stats as $dataset_key => $stats) {
        echo "- {$stats['name']}: {$stats['valid_sequences']} 条有效序列\n";
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}

// 生成下一步指导文件
$instructions_file = $output_dir . '/phylogeny_instructions.md';
$instructions_content = "# 系统发育树构建指南

## 已完成步骤
✅ 序列数据已从PlaszymeDB导出到FASTA文件

## 下一步操作

### 步骤2: 构建系统发育树

推荐使用以下工具之一：

#### 方法A: 使用IQ-TREE (推荐)
```bash
# 对每个FASTA文件运行以下命令
iqtree -s comprehensive_enzymes.fasta -m MFP -bb 1000 -nt AUTO
iqtree -s pet_enzymes.fasta -m MFP -bb 1000 -nt AUTO
iqtree -s pe_pp_enzymes.fasta -m MFP -bb 1000 -nt AUTO
iqtree -s ec31_esterases.fasta -m MFP -bb 1000 -nt AUTO
iqtree -s bacterial_enzymes.fasta -m MFP -bb 1000 -nt AUTO
iqtree -s fungal_enzymes.fasta -m MFP -bb 1000 -nt AUTO
```

#### 方法B: 使用MEGA GUI
1. 打开MEGA软件
2. 选择 Align > Edit/Build Alignment
3. 导入FASTA文件
4. 进行多序列比对
5. 构建系统发育树

### 步骤3: iTOL项目创建
1. 访问 https://itol.embl.de/
2. 注册账户
3. 上传生成的 .treefile 文件
4. 设置项目为公开分享
5. 获取项目ID

### 步骤4: 更新配置
修改 interactive_tree.php 中的项目映射

## 文件说明
- *.fasta: 蛋白质序列文件
- *.treefile: IQ-TREE生成的系统发育树文件
- *.log: 构建日志文件
";

file_put_contents($instructions_file, $instructions_content);
echo "\n构建指南已保存到: $instructions_file\n";
?>
