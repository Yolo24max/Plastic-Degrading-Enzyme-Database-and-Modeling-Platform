<?php
/**
 * 交互式系统发育树页面 - iTOL集成
 * 实现分层架构模式：前端iframe容器 + 中间层URL重写 + iTOL后端
 * 现已集成PlaszymeDB数据库，提供动态序列统计
 */

header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入数据库配置
require_once 'db_config.php';

// 获取数据库连接
try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $pdo = null;
}

// 获取请求参数
$dataset = isset($_GET['dataset']) ? $_GET['dataset'] : 'comprehensive';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'rectangular';
$colorscheme = isset($_GET['colorscheme']) ? $_GET['colorscheme'] : 'plastic';

/**
 * 从数据库获取各数据集的序列统计
 */
function getDatasetStatistics($pdo) {
    $stats = [];
    
    if (!$pdo) {
        // 如果数据库连接失败，返回默认值
        return [
            'comprehensive' => 0,
            'pet' => 0,
            'pe_pp' => 0,
            'ec31' => 0,
            'bacterial' => 0,
            'fungal' => 0
        ];
    }
    
    try {
        // 总序列数
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb");
        $stats['comprehensive'] = $stmt->fetch()['count'];
        
        // PET降解酶 - 需要包括PET及其相关变体
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE can_degrade_PET = 1");
        $stats['pet'] = $stmt->fetch()['count'];
        
        // PE & PP降解酶 - 聚乙烯和聚丙烯
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM plaszymedb WHERE can_degrade_PE = 1 OR can_degrade_PP = 1");
        $stats['pe_pp'] = $stmt->fetch()['count'];
        
        // EC 3.1酯酶 - 根据实际EC编号格式
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM plaszymedb WHERE ec_number LIKE '3.1.%' OR ec_number LIKE '3.1.1.%'");
        $stmt->execute();
        $stats['ec31'] = $stmt->fetch()['count'];
        
        // 细菌酶 - 根据实际taxonomy字段值
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM plaszymedb WHERE taxonomy = 'Bacteria' OR taxonomy LIKE '%Bacteria%' OR taxonomy LIKE '%Proteobacteria%' OR taxonomy LIKE '%Bacillota%' OR taxonomy LIKE '%Actinomycetota%' OR taxonomy LIKE '%Chloroflexota%' OR taxonomy LIKE '%Pseudomonadota%'");
        $stmt->execute();
        $stats['bacterial'] = $stmt->fetch()['count'];
        
        // 真菌酶 - 根据实际taxonomy字段值
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM plaszymedb WHERE taxonomy = 'Fungi' OR taxonomy LIKE '%Fungi%' OR taxonomy = 'Eukarya'");
        $stmt->execute();
        $stats['fungal'] = $stmt->fetch()['count'];
        
    } catch (Exception $e) {
        error_log("Error getting dataset statistics: " . $e->getMessage());
        // 返回默认值
        $stats = [
            'comprehensive' => 0,
            'pet' => 0,
            'pe_pp' => 0,
            'ec31' => 0,
            'bacterial' => 0,
            'fungal' => 0
        ];
    }
    
    return $stats;
}

// 获取数据集统计
$dataset_stats = getDatasetStatistics($pdo);

// iTOL共享项目映射表
// 注意：这些项目ID需要在iTOL平台上预先创建和配置
$itol_projects = [
    'comprehensive' => '1213510024592901756372470', // PlaszymeDB真菌cutinase系统发育树 (暂用作综合展示)
    'pet' => 'plz_pet_enzymes',
    'pe_pp' => 'plz_pe_pp_degraders',
    'ec31' => 'plz_esterases', 
    'bacterial' => 'plz_bacterial',
    'fungal' => '1213510024592901756372470'  // PlaszymeDB真菌cutinase系统发育树
];

// 验证数据集参数
if (!array_key_exists($dataset, $itol_projects)) {
    $dataset = 'comprehensive';
}

// 构建iTOL URL
$itol_project_id = $itol_projects[$dataset];

// 检查项目ID是否有效（基础验证）
if (empty($itol_project_id) || $itol_project_id === 'demo_tree') {
    // 使用iTOL的公共演示页面作为备用方案
    $itol_base_url = "https://itol.embl.de/";
    $backup_mode = true;
} else {
    // 使用正确的iTOL树访问URL格式
    $itol_base_url = "https://itol.embl.de/tree/$itol_project_id";
    $backup_mode = false;
}

// 构建URL参数 - iTOL树URL不需要额外参数，直接使用基础URL
if ($backup_mode) {
    $itol_url = $itol_base_url;
} else {
    // 对于真实的iTOL项目，直接使用项目URL
    $itol_url = $itol_base_url;
}

// 数据集信息 - 现在使用数据库中的实际统计数据
$dataset_info = [
    'comprehensive' => [
        'title' => '综合塑料降解酶系统发育树',
        'description' => 'PlaszymeDB中所有塑料降解酶的最大似然系统发育树',
        'sequences' => $dataset_stats['comprehensive'],
        'last_updated' => date('Y-m-d')
    ],
    'pet' => [
        'title' => 'PET降解酶系统发育树',
        'description' => 'PET (聚对苯二甲酸乙二醇酯) 降解酶的专门系统发育分析',
        'sequences' => $dataset_stats['pet'],
        'last_updated' => date('Y-m-d')
    ],
    'pe_pp' => [
        'title' => 'PE和PP降解酶系统发育树',
        'description' => '聚乙烯和聚丙烯降解酶的比较系统发育分析',
        'sequences' => $dataset_stats['pe_pp'],
        'last_updated' => date('Y-m-d')
    ],
    'ec31' => [
        'title' => 'EC 3.1酯酶系统发育树',
        'description' => '具有塑料降解活性的EC 3.1.x.x酯酶的进化关系',
        'sequences' => $dataset_stats['ec31'],
        'last_updated' => date('Y-m-d')
    ],
    'bacterial' => [
        'title' => '细菌酶系统发育树',
        'description' => '来自细菌来源的塑料降解酶系统发育树',
        'sequences' => $dataset_stats['bacterial'],
        'last_updated' => date('Y-m-d')
    ],
    'fungal' => [
        'title' => '真菌酶系统发育树',
        'description' => '来自真菌来源的塑料降解酶进化分析',
        'sequences' => $dataset_stats['fungal'],
        'last_updated' => date('Y-m-d')
    ]
];

$current_info = $dataset_info[$dataset];

// 记录访问日志
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'dataset' => $dataset,
    'mode' => $mode,
    'colorscheme' => $colorscheme,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

// 写入访问日志（可选）
$log_file = __DIR__ . '/logs/tree_access.log';
if (is_dir(dirname($log_file))) {
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

// 输出JSON响应用于AJAX请求
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'dataset' => $dataset,
        'itol_url' => $itol_url,
        'info' => $current_info,
        'parameters' => [
            'mode' => $mode,
            'colorscheme' => $colorscheme
        ],
        'all_datasets' => $dataset_info,
        'statistics' => $dataset_stats
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 生成HTML页面（用于直接访问）
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_info['title']); ?> - PlaszymeDB</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Source Sans Pro', Arial, sans-serif;
            background: #f8f9fa;
        }
        
        .tree-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .tree-title {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .tree-description {
            margin: 0;
            color: #666;
            font-size: 1rem;
        }
        
        .tree-stats {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #888;
        }
        
        .tree-container {
            width: 100%;
            height: calc(100vh - 120px);
            border: none;
        }
        
        /* 防止iframe滚动事件冒泡到父页面 */
        .tree-container-wrapper {
            position: relative;
            overflow: hidden;
        }
        
        .error-message {
            padding: 2rem;
            text-align: center;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="tree-header">
        <h1 class="tree-title"><?php echo htmlspecialchars($current_info['title']); ?></h1>
        <p class="tree-description"><?php echo htmlspecialchars($current_info['description']); ?></p>
        <div class="tree-stats">
            序列数量: <?php echo $current_info['sequences']; ?> | 
            更新时间: <?php echo $current_info['last_updated']; ?> | 
            显示模式: <?php echo htmlspecialchars($mode); ?> | 
            颜色方案: <?php echo htmlspecialchars($colorscheme); ?>
        </div>
        <?php if (isset($backup_mode) && $backup_mode): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 0.75rem; margin-top: 1rem; border-radius: 4px; color: #856404;">
            <strong>注意：</strong>正在显示iTOL演示页面。专用的系统发育树项目正在配置中。
        </div>
        <?php endif; ?>
    </div>
    
    <div class="tree-container-wrapper">
        <iframe 
            class="tree-container" 
            src="<?php echo htmlspecialchars($itol_url); ?>" 
            title="<?php echo htmlspecialchars($current_info['title']); ?>"
            frameborder="0"
            allowfullscreen>
            <div class="error-message">
                <h3>无法加载系统发育树</h3>
                <p>您的浏览器不支持iframe或网络连接出现问题</p>
                <p><a href="<?php echo htmlspecialchars($itol_url); ?>" target="_blank">点击这里直接访问iTOL</a></p>
            </div>
        </iframe>
    </div>
    
    <script>
        // 监听iframe加载状态
        const iframe = document.querySelector('.tree-container');
        
        iframe.addEventListener('load', function() {
            console.log('Tree loaded successfully');
            // 可以在这里添加成功加载的处理逻辑
        });
        
        iframe.addEventListener('error', function() {
            console.error('Failed to load tree');
            // 可以在这里添加错误处理逻辑
        });
        
        // 向父窗口发送加载完成消息（如果在iframe中使用）
        if (window.parent !== window) {
            window.parent.postMessage({
                type: 'tree_loaded',
                dataset: '<?php echo $dataset; ?>',
                url: '<?php echo $itol_url; ?>'
            }, '*');
        }
        
        // 防止iframe滚动事件冒泡到父页面
        function preventScrollBubbling() {
            const wrapper = document.querySelector('.tree-container-wrapper');
            
            if (wrapper) {
                // 阻止滚动事件冒泡
                wrapper.addEventListener('wheel', function(event) {
                    event.stopPropagation();
                    event.preventDefault();
                }, { passive: false });

                // 阻止触摸滚动事件冒泡（移动设备）
                wrapper.addEventListener('touchmove', function(event) {
                    event.stopPropagation();
                }, { passive: false });

                // 鼠标进入时禁用页面滚动
                wrapper.addEventListener('mouseenter', function() {
                    document.body.style.overflow = 'hidden';
                });

                // 鼠标离开时恢复页面滚动
                wrapper.addEventListener('mouseleave', function() {
                    document.body.style.overflow = 'auto';
                });
            }
        }
        
        // 初始化滚动保护
        document.addEventListener('DOMContentLoaded', preventScrollBubbling);
        preventScrollBubbling(); // 立即执行一次
    </script>
</body>
</html>
