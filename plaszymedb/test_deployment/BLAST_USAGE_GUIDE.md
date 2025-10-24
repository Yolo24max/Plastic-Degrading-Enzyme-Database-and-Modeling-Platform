# PlaszymeDB BLAST功能使用指南

## 功能概述

PlaszymeDB BLAST功能提供快速的蛋白质序列相似性搜索，能够在1,245个精选的塑料降解酶数据库中找到与查询序列最相似的酶。

## 主要特性

- **快速搜索**: 基于优化的序列比对算法，几秒内完成搜索
- **灵活参数**: 支持多种搜索阈值和过滤条件
- **详细结果**: 提供相同度、得分、覆盖度和比对预览
- **智能检测**: 首页搜索框自动识别蛋白质序列
- **多种格式**: 支持FASTA格式和原始氨基酸序列

## 使用方法

### 1. 通过导航菜单访问

1. 点击导航栏中的 "BLAST" 链接
2. 进入序列相似性搜索页面

### 2. 通过首页搜索

1. 在首页搜索框中输入蛋白质序列（>20个字符）
2. 系统自动识别并跳转到BLAST页面
3. 序列已预填充，可直接搜索

### 3. 输入序列

支持两种格式：

**FASTA格式：**
```
>PETase_example
MNFPRASRLMQAAVLGGLMAVSAAATAQTNPYARGPNPTAASLEASAGPF...
```

**原始序列：**
```
MNFPRASRLMQAAVLGGLMAVSAAATAQTNPYARGPNPTAASLEASAGPF...
```

### 4. 设置搜索参数

- **最大结果数**: 10-100个结果
- **相似性阈值**: 高(>70%) / 中(>30%) / 低(>10%) / 全部
- **塑料类型过滤**: 按特定塑料类型筛选
- **结构过滤**: 仅显示有3D结构的酶

### 5. 查看结果

结果表格包含：
- 排名和酶名称
- 宿主生物和塑料类型
- 相同度百分比和比对得分
- 序列覆盖度
- 比对预览（显示匹配区域）
- 详情查看按钮

## 技术特性

### 序列比对算法

- 基于BLOSUM62替代矩阵
- 使用滑动窗口局部比对
- 优化的相似性评分系统

### 性能优化

- 快速序列清理和验证
- 高效的数据库查询
- 智能的结果排序和过滤

### 错误处理

- 自动序列格式验证
- 详细的错误提示信息
- 网络连接错误恢复

## API接口

### 请求格式

```php
POST /blast_search.php
Content-Type: application/json

{
    "sequence": "MNFPRASRLMQ...",
    "max_results": 25,
    "threshold": "medium",
    "plastic_filter": "all",
    "structure_filter": "all"
}
```

### 响应格式

```json
{
    "success": true,
    "query_length": 290,
    "total_found": 15,
    "search_time": 0.234,
    "results": [
        {
            "plz_id": "PLZ_001",
            "enzyme_name": "PETase",
            "host_organism": "Ideonella sakaiensis",
            "plastic": "PET",
            "ec_number": "3.1.1.101",
            "score": 485,
            "identity": 92.5,
            "coverage": 98.2,
            "alignment_preview": {
                "query": "MNFPRASRLMQ...",
                "match": "|||||||||||...",
                "subject": "MNFPRASRLMQ..."
            }
        }
    ]
}
```

## 文件结构

```
plaszymedb/
├── blast_search.php          # BLAST API后端
├── V9.html                   # 集成了BLAST页面的主界面
├── test_blast.php            # BLAST功能测试页面
└── BLAST_USAGE_GUIDE.md      # 使用指南（本文件）
```

## 测试功能

访问 `test_blast.php` 进行功能测试：

1. **数据库连接测试**: 验证数据库连接
2. **API功能测试**: 使用示例序列测试
3. **序列清理测试**: 验证FASTA格式处理
4. **自定义搜索**: 测试自己的序列

## 常见问题

### Q: 搜索很慢怎么办？
A: 尝试减少最大结果数或提高相似性阈值。

### Q: 没有找到结果？
A: 检查序列是否正确，尝试降低相似性阈值。

### Q: 序列格式错误？
A: 确保只包含标准氨基酸字母（A-Z），避免数字和特殊字符。

### Q: 如何解读结果？
A: 
- **相同度>70%**: 高度相似，可能是同源酶
- **相同度30-70%**: 中等相似，可能有相似功能
- **相同度<30%**: 低相似，需要进一步验证

## 技术支持

如有问题或建议，请：
1. 检查浏览器控制台错误信息
2. 使用测试页面验证功能
3. 联系开发团队

## 更新日志

- **v1.0** (2025-01): 初始版本，基础BLAST功能
- 支持序列相似性搜索
- 集成到主界面
- 提供测试工具

## 性能基准

- **数据库大小**: 1,245个酶序列
- **平均搜索时间**: 0.2-2秒
- **支持序列长度**: 10-5000个氨基酸
- **最大结果数**: 100个

---

*PlaszymeDB BLAST功能基于生物信息学最佳实践开发，为塑料降解酶研究提供高效的序列分析工具。*
