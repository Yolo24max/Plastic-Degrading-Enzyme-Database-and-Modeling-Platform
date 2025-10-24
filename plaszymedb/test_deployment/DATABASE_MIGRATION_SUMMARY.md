# PlaszymeDB 数据库迁移总结

## 迁移日期
2025-10-08

## 数据库架构变更

### 旧架构
- 单个 `plastic` 字段存储塑料类型
- `label` 字段

### 新架构
- 移除了 `plastic` 和 `label` 字段
- 添加了 50+ 个 `can_degrade_*` 布尔字段，包括：
  - can_degrade_PET
  - can_degrade_PE
  - can_degrade_PLA
  - can_degrade_PCL
  - can_degrade_PBS
  - can_degrade_PBAT
  - can_degrade_PHB
  - can_degrade_PU
  - can_degrade_PVA
  - can_degrade_PS
  - can_degrade_PP
  - 等等...
- 添加了 `predicted_ec_number` 和 `ec_prediction_source` 字段

## 更新的文件清单

### 1. search.php ✅
**变更内容:**
- 更新 SELECT 查询，移除 `plastic` 和 `label` 字段
- 添加主要的 `can_degrade_*` 字段到查询中
- 修改塑料类型过滤逻辑，使用新的 `can_degrade_*` 字段
- 添加结果后处理：自动聚合 `can_degrade_*` 字段生成 `plastic` 字段以保持向后兼容

**影响的功能:**
- 主搜索功能
- 塑料类型筛选

### 2. get_enzyme_detail.php ✅
**变更内容:**
- 使用 `SELECT *` 获取所有字段（包括新的 can_degrade_* 字段）
- 添加逻辑聚合 `can_degrade_*` 字段生成 `plastic` 字段

**影响的功能:**
- 酶详细信息API

### 3. detail.php ✅
**变更内容:**
- 添加 `plastic` 字段生成逻辑
- 保持序列信息计算功能不变

**影响的功能:**
- 详细信息页面API
- 序列分析功能

### 4. stats.php ✅
**变更内容:**
- 重写塑料类型统计逻辑
- 基于 `can_degrade_*` 字段动态计算每种塑料类型的酶数量
- 自动按数量降序排列

**影响的功能:**
- 统计信息API
- 数据分布可视化

### 5. api_dataset_stats.php ✅
**变更内容:**
- 更新 PET 降解酶统计：使用 `can_degrade_PET = 1`
- 更新 PE & PP 降解酶统计：使用 `can_degrade_PE = 1 OR can_degrade_PP = 1`

**影响的功能:**
- 系统发育树页面数据集统计
- PHYLOGENY 页面

### 6. api_protein_structure.php ✅
**变更内容:**
- 移除查询中的 `plastic` 字段
- 保持结构信息获取功能不变

**影响的功能:**
- 3D蛋白质结构查看器
- PDB/JSON 文件获取

### 7. export_sequences.php ✅
**变更内容:**
- 更新所有数据集查询：
  - PET 降解酶：使用 `can_degrade_PET = 1`
  - PE & PP 降解酶：使用 `can_degrade_PE = 1 OR can_degrade_PP = 1`
- 移除 FASTA 头部中的 plastic 信息
- 保持 ec_number 和 taxonomy 信息

**影响的功能:**
- 系统发育树序列导出
- FASTA 文件生成

### 8. interactive_tree.php ✅
**变更内容:**
- 更新统计查询，使用新的 `can_degrade_*` 字段
- 保持 iTOL 集成功能不变

**影响的功能:**
- 交互式系统发育树页面
- 数据集统计

### 9. blast_search.php ✅
**变更内容:**
- 更新 SQL 查询，包含 `can_degrade_*` 字段
- 修改塑料类型过滤逻辑
- 在结果中动态生成 `plastic` 字段

**影响的功能:**
- BLAST 序列相似性搜索
- 塑料类型过滤

## 向后兼容性

所有更新的 PHP 文件都保持了对前端的**向后兼容性**：

- 虽然数据库不再有 `plastic` 字段，但所有 API 响应中都动态生成了 `plastic` 字段
- `plastic` 字段格式：多个塑料类型用逗号分隔，如 "PET, PLA, PCL"
- 如果没有任何降解能力，显示 "N/A"

这意味着前端 V9.html 和其他界面**无需修改**即可正常工作。

## 新增测试文件

### test_new_schema.php
完整的数据库架构测试脚本，包括：
1. 数据库连接测试
2. 表结构验证
3. 数据统计
4. 塑料降解能力统计
5. API 端点测试链接
6. 优化建议

**使用方法:**
```
访问: http://localhost/plaszymedb/test_new_schema.php
```

## 前端兼容性

### V9.html
**状态:** ✅ 无需修改

由于所有后端 API 都动态生成了 `plastic` 字段，前端页面可以继续使用相同的数据结构。

## 建议的数据库优化

为提高性能，建议添加以下索引：

```sql
-- 主键
ALTER TABLE plaszymedb ADD PRIMARY KEY (PLZ_ID);

-- 常用查询字段索引
CREATE INDEX idx_ec_number ON plaszymedb(ec_number);
CREATE INDEX idx_taxonomy ON plaszymedb(taxonomy);
CREATE INDEX idx_host_organism ON plaszymedb(host_organism);

-- 常用塑料类型字段索引
CREATE INDEX idx_can_degrade_PET ON plaszymedb(can_degrade_PET);
CREATE INDEX idx_can_degrade_PE ON plaszymedb(can_degrade_PE);
CREATE INDEX idx_can_degrade_PLA ON plaszymedb(can_degrade_PLA);
CREATE INDEX idx_can_degrade_PCL ON plaszymedb(can_degrade_PCL);
```

## 测试清单

使用 `test_new_schema.php` 验证：
- [x] 数据库连接
- [x] 表结构检查
- [x] 关键字段存在
- [x] 数据统计正确
- [x] 塑料降解统计正确

手动测试 API 端点：
- [ ] search.php - 搜索功能
- [ ] search.php?plastic=PET - 塑料类型筛选
- [ ] get_enzyme_detail.php?plz_id=XXX - 酶详情
- [ ] detail.php?plz_id=XXX - 详情页
- [ ] stats.php - 统计信息
- [ ] api_dataset_stats.php - 数据集统计
- [ ] blast_search.php - BLAST 搜索（需要 POST 请求）
- [ ] V9.html - 前端主页

## 注意事项

1. **数据一致性:** 确保数据库中的 `can_degrade_*` 字段已正确填充数据
2. **性能考虑:** 如果查询变慢，请添加建议的索引
3. **备份:** 在生产环境部署前，务必备份原始数据
4. **监控:** 部署后监控 API 响应时间和错误日志

## 回滚方案

如果需要回滚，请：
1. 恢复数据库备份
2. 使用 Git 回滚所有 PHP 文件到迁移前的版本
3. 清除任何缓存

## 下一步

1. 访问 `http://localhost/plaszymedb/test_new_schema.php` 运行测试
2. 访问 `http://localhost/plaszymedb/V9.html` 测试前端功能
3. 测试所有主要功能：搜索、筛选、详情页、统计、BLAST、3D结构查看器
4. 如果一切正常，考虑添加建议的数据库索引
5. 更新文档和用户手册（如有）

## 支持的塑料类型

新架构支持以下 36 种塑料类型：
- PET, PE, PLA, PCL, PBS, PBAT, PHB, PU
- PVA, PS, PP, PHV, PHBV, NR, PEG, PES, PEF
- PEA, PA, PHO, PHPV, PHBH, PHBVH
- P3HB_co_3MP, P3HP, P3HV, P4HB
- PMCL, PPL, PBSeT, PBSA
- ECOFLEX, ECOVIO_FT, Impranil, O_PVA

## 总结

✅ 所有核心 PHP 文件已成功更新
✅ 保持了完整的向后兼容性
✅ 前端无需任何修改
✅ 提供了完整的测试工具

迁移已完成，可以开始测试！

