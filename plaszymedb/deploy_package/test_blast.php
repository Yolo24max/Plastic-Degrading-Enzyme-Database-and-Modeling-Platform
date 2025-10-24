<?php
/**
 * BLAST 功能测试文件
 * 用于验证BLAST搜索API是否正常工作
 */

header('Content-Type: text/html; charset=utf-8');

// 测试序列
$testSequence = "MNFPRASRLMQAAVLGGLMAVSAAATAQTNPYARGPNPTAASLEASAGPFTVRSFTVSRPSGYGAGTVYYPTNAGGTVGAIAIVPGYTARQSSIKWWGPRLASHGFVVITIDTNSTLDQPSSRSSQQMAALRQVASLNGTSSSPIYGKVDTARMGVMGWSMGGGGSLISAANNPSLKAAAPQAPWDSSTNFSSVTVPTLIFACENDSIAPVNSSALPIYDSMSRNAKQFLEINGGSHSCANSGNSNQALIGKKGVAWMKRFMDNDTRYSTFACENPNSTRVSDFRTANCSLEDYYFMKESL";

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLAST测试 - PlaszymeDB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .result {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .error {
            background: #ffeaa7;
            border: 1px solid #fdcb6e;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        button {
            background: #005eb8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        button:hover {
            background: #004494;
        }
        textarea {
            width: 100%;
            height: 100px;
            font-family: monospace;
            font-size: 12px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        pre {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            white-space: pre-wrap;
            font-size: 12px;
        }
        .nav-link {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .nav-link:hover {
            background: #5a6268;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="V9.html" class="nav-link">← 返回主页面</a>
        
        <h1>🧬 PlaszymeDB BLAST功能测试</h1>
        <p>此页面用于测试BLAST序列搜索功能是否正常工作。</p>
        
        <!-- 测试1: 数据库连接 -->
        <div class="test-section">
            <h3>测试1: 数据库连接</h3>
            <button onclick="testDatabaseConnection()">测试数据库连接</button>
            <div id="db-result"></div>
        </div>
        
        <!-- 测试2: BLAST API -->
        <div class="test-section">
            <h3>测试2: BLAST API测试</h3>
            <p>测试序列 (PETase示例):</p>
            <textarea id="testSequence"><?php echo $testSequence; ?></textarea>
            <br>
            <button onclick="testBlastAPI()">运行BLAST搜索</button>
            <div id="blast-result"></div>
        </div>
        
        <!-- 测试3: 序列清理 -->
        <div class="test-section">
            <h3>测试3: 序列清理功能</h3>
            <p>测试带FASTA格式的序列:</p>
            <textarea id="fastaSequence">>PETase_test
MNFPRASRLMQAAVLGGLMAVSAAATAQTNPYARGPNPTAASLEASAGPFTVRSFTVSRPSGYGAGTVYYPTNAGGTVGAIAIVPGYTARQSSIKWWGPRLASHGFVVITIDTNSTLDQPSSRSSQQMAALRQVASLNGTSSSPIYGKVDTARMGVMGWSMGGGGSLISAANNPSLKAAAPQAPWDSSTNFSSVTVPTLIFACENDSIAPVNSSALPIYDSMSRNAKQFLEINGGSHSCANSGNSNQALIGKKGVAWMKRFMDNDTRYSTFACENPNSTRVSDFRTANCSLEDYYFMKESL</textarea>
            <br>
            <button onclick="testSequenceCleaning()">测试序列清理</button>
            <div id="clean-result"></div>
        </div>
        
        <!-- 测试4: 自定义搜索 -->
        <div class="test-section">
            <h3>测试4: 自定义BLAST搜索</h3>
            <p>输入您自己的序列进行测试:</p>
            <textarea id="customSequence" placeholder="请输入蛋白质序列..."></textarea>
            <br>
            <label>
                最大结果数: 
                <select id="maxResults">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                </select>
            </label>
            <label>
                相似性阈值: 
                <select id="threshold">
                    <option value="high">高 (>70%)</option>
                    <option value="medium" selected>中 (>30%)</option>
                    <option value="low">低 (>10%)</option>
                    <option value="all">全部</option>
                </select>
            </label>
            <br>
            <button onclick="testCustomBlast()">运行自定义搜索</button>
            <div id="custom-result"></div>
        </div>
    </div>

    <script>
        // 测试数据库连接
        async function testDatabaseConnection() {
            const resultDiv = document.getElementById('db-result');
            resultDiv.innerHTML = '<p>正在测试数据库连接...</p>';
            
            try {
                const response = await fetch('blast_search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sequence: '',
                        test_connection: true
                    })
                });
                
                const text = await response.text();
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>响应状态: ${response.status}</h4>
                        <pre>${text}</pre>
                    </div>
                `;
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>连接错误</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        // 测试BLAST API
        async function testBlastAPI() {
            const sequence = document.getElementById('testSequence').value;
            const resultDiv = document.getElementById('blast-result');
            resultDiv.innerHTML = '<p>正在运行BLAST搜索...</p>';
            
            try {
                const response = await fetch('blast_search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sequence: sequence,
                        max_results: 10,
                        threshold: 'medium',
                        plastic_filter: 'all',
                        structure_filter: 'all'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="result">
                            <h4>搜索成功！</h4>
                            <p><strong>查询长度:</strong> ${data.query_length} aa</p>
                            <p><strong>找到结果:</strong> ${data.total_found} 个</p>
                            <p><strong>搜索时间:</strong> ${data.search_time?.toFixed(3)} 秒</p>
                            <h5>前5个结果:</h5>
                            <pre>${JSON.stringify(data.results.slice(0, 5), null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>搜索失败</h4>
                            <p>${data.error}</p>
                        </div>
                    `;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>请求错误</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        // 测试序列清理
        async function testSequenceCleaning() {
            const sequence = document.getElementById('fastaSequence').value;
            const resultDiv = document.getElementById('clean-result');
            resultDiv.innerHTML = '<p>正在测试序列清理...</p>';
            
            try {
                const response = await fetch('blast_search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sequence: sequence,
                        max_results: 5,
                        threshold: 'all'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="result">
                            <h4>序列清理成功！</h4>
                            <p><strong>原始长度:</strong> ${sequence.length} 字符</p>
                            <p><strong>清理后长度:</strong> ${data.query_length} aa</p>
                            <p><strong>成功清理FASTA格式</strong></p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>清理失败</h4>
                            <p>${data.error}</p>
                        </div>
                    `;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>请求错误</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        // 测试自定义BLAST
        async function testCustomBlast() {
            const sequence = document.getElementById('customSequence').value.trim();
            const maxResults = document.getElementById('maxResults').value;
            const threshold = document.getElementById('threshold').value;
            const resultDiv = document.getElementById('custom-result');
            
            if (!sequence) {
                resultDiv.innerHTML = '<div class="error"><p>请输入序列</p></div>';
                return;
            }
            
            resultDiv.innerHTML = '<p>正在运行自定义搜索...</p>';
            
            try {
                const response = await fetch('blast_search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sequence: sequence,
                        max_results: parseInt(maxResults),
                        threshold: threshold,
                        plastic_filter: 'all',
                        structure_filter: 'all'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let resultsHtml = '';
                    if (data.results.length > 0) {
                        resultsHtml = '<h5>搜索结果:</h5>';
                        data.results.forEach((result, i) => {
                            resultsHtml += `
                                <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                    <strong>${i+1}. ${result.plz_id} - ${result.enzyme_name || 'Unknown'}</strong><br>
                                    相同度: ${result.identity}% | 得分: ${result.score} | 覆盖度: ${result.coverage}%<br>
                                    宿主: <em>${result.host_organism || 'N/A'}</em> | 塑料: ${result.plastic || 'N/A'}
                                </div>
                            `;
                        });
                    } else {
                        resultsHtml = '<p>没有找到匹配的结果</p>';
                    }
                    
                    resultDiv.innerHTML = `
                        <div class="result">
                            <h4>搜索完成！</h4>
                            <p><strong>查询长度:</strong> ${data.query_length} aa</p>
                            <p><strong>找到结果:</strong> ${data.total_found} 个</p>
                            <p><strong>搜索时间:</strong> ${data.search_time?.toFixed(3)} 秒</p>
                            ${resultsHtml}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>搜索失败</h4>
                            <p>${data.error}</p>
                        </div>
                    `;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>请求错误</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>
