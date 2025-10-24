# PlaszymeDB 结构数据路径更新总结

## 📋 更新概述

**日期:** 2025-10-08  
**目的:** 将3D结构数据路径更新为统一的 `structure_data` 文件夹  
**状态:** ✅ 完成

---

## 🎯 主要改动

### 1. 目录结构调整

**新结构:**
```
structure_data/
├── predicted_xid/              # 预测结构 (474个)
│   ├── pdb/                    # X0001.pdb - X0474.pdb
│   ├── json/                   # 元数据
│   └── pred_metadata_XID.csv   # PLZ_ID → XID 映射
│
└── experimental_xid/           # 实验结构 (33个)
    ├── pdb/                    # X0001.pdb, X0008.pdb, etc.
    ├── json/                   # 元数据
    └── exp_metadata_XID.csv   # PLZ_ID → XID 映射
```

### 2. 核心功能实现

#### a) PLZ_ID 到 XID 自动映射

**问题:** PDB文件使用XID命名（如X0001.pdb），但前端使用PLZ_ID（如866554aa77）

**解决方案:**
- 在 `api_protein_structure.php` 中添加映射函数
- 从metadata CSV文件读取映射关系
- 使用静态缓存提高性能

```php
function loadPlzToXidMapping($csvPath) {
    static $cache = [];
    // 从CSV加载PLZ_ID → XID映射
    // 缓存映射关系避免重复读取
}
```

#### b) 路径配置更新

**修改前:**
```php
$pdb_dir = $base_path . '/pdb_predicted/pdb/';
```

**修改后:**
```php
$pdb_dir = $base_path . '/structure_data/predicted_xid/pdb/';
```

#### c) 增强的错误处理

```php
if (!isset($mapping[$plz_id])) {
    throw new Exception("未找到PLZ_ID '{$plz_id}' 对应的结构数据");
}
```

---

## 📁 修改的文件

### 核心文件

| 文件 | 状态 | 说明 |
|------|------|------|
| `api_protein_structure.php` | ✅ 已修改 | 添加映射功能，更新路径 |
| `protein_viewer_optimized.js` | ✅ 无需修改 | 前端代码保持不变 |

### 新增文件

| 文件 | 类型 | 说明 |
|------|------|------|
| `test_structure_api.php` | 测试 | 后端API完整测试 |
| `test_3d_viewer.html` | 测试 | 前端3D查看器测试 |
| `结构数据配置说明.md` | 文档 | 完整技术文档 |
| `结构数据快速使用指南.md` | 文档 | 快速上手指南 |
| `STRUCTURE_DATA_UPDATE_SUMMARY.md` | 文档 | 本总结文档 |

---

## 🔍 技术细节

### API工作流程

```
1. 前端请求
   ↓
   GET api_protein_structure.php?plz_id=866554aa77&type=predicted&action=pdb
   ↓
2. API处理
   ├─ 读取 pred_metadata_XID.csv
   ├─ 查找映射: 866554aa77 → X0001
   ├─ 定位文件: structure_data/predicted_xid/pdb/X0001.pdb
   └─ 返回PDB数据
   ↓
3. 前端显示
   └─ Mol* 加载并渲染3D结构
```

### 数据类型支持

| 类型 | 参数值 | 文件数 | Metadata文件 |
|------|--------|--------|--------------|
| 预测结构 | `predicted` | 474 | `pred_metadata_XID.csv` |
| 实验结构 | `experimental` | 33 | `exp_metadata_XID.csv` |

### 性能优化

1. **静态缓存**
   - 同一请求中多次调用不重复读取CSV
   - 减少文件I/O操作

2. **流式传输**
   - PDB文件使用 `readfile()` 直接输出
   - 不占用PHP内存

3. **懒加载**
   - 只在需要时读取映射关系
   - 减少不必要的计算

---

## 🧪 测试方法

### 快速测试（推荐）

**打开3D查看器测试页面：**
```
http://localhost/plaszymedb/test_3d_viewer.html
```

**操作步骤：**
1. 点击任意"快速测试示例"按钮
2. 观察3D结构是否正常加载
3. 尝试旋转、缩放操作

### 完整测试

**打开API测试页面：**
```
http://localhost/plaszymedb/test_structure_api.php
```

**检查项目：**
- ✅ 目录结构完整性
- ✅ Metadata文件存在性
- ✅ PLZ_ID到XID映射准确性
- ✅ API响应正确性
- ✅ PDB文件可访问性

### 主页面测试

**打开主页：**
```
http://localhost/plaszymedb/V9.html
```

**测试流程：**
1. 搜索或浏览酶
2. 打开详情页
3. 点击"3D Structure"标签
4. 验证结构正常显示
5. 测试表示方式切换

---

## 📊 测试用例

### 预测结构测试

| PLZ_ID | XID | 期望结果 |
|--------|-----|----------|
| 866554aa77 | X0001 | ✅ 正常加载 |
| 0c1b4dd3c4 | X0004 | ✅ 正常加载 |
| 9a48d1ff76 | X0005 | ✅ 正常加载 |

**测试命令：**
```bash
curl "http://localhost/plaszymedb/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=info"
```

### 实验结构测试

| PLZ_ID | XID | PDB ID | 期望结果 |
|--------|-----|--------|----------|
| bd7ef0ab46 | X0223 | 8AIR | ✅ 正常加载 |
| ba04d67a0c | X0321 | 6SBN | ✅ 正常加载 |
| 2cbb6fa0a4 | X0200 | 1AGY | ✅ 正常加载 |

**测试命令：**
```bash
curl "http://localhost/plaszymedb/api_protein_structure.php?plz_id=bd7ef0ab46&type=experimental&action=info"
```

---

## ✅ 验证清单

### 部署前验证

- [x] `structure_data` 文件夹已正确放置
- [x] PDB文件命名使用XID格式
- [x] Metadata CSV文件包含PLZ_ID和protein_id列
- [x] `api_protein_structure.php` 已更新
- [x] 文件权限正确（可读）

### 功能验证

- [x] API能正确读取metadata文件
- [x] PLZ_ID到XID映射正确
- [x] 预测结构加载正常
- [x] 实验结构加载正常
- [x] 错误处理完善
- [x] 性能符合预期

### 用户体验验证

- [x] 前端无需修改
- [x] 3D查看器正常工作
- [x] 表示方式切换无错误
- [x] 控制台无500错误
- [x] 加载速度可接受

---

## 🔄 向后兼容性

### 前端兼容性

✅ **完全兼容**  
前端代码（`protein_viewer_optimized.js`）无需任何修改，仍然使用PLZ_ID调用API。

### API接口兼容性

✅ **完全兼容**  
API接口保持不变：
```
api_protein_structure.php?plz_id={PLZ_ID}&type={TYPE}&action={ACTION}
```

### 数据兼容性

✅ **需要Metadata文件**  
必须提供正确格式的metadata CSV文件，包含PLZ_ID和protein_id列。

---

## 🚨 注意事项

### 1. Metadata文件格式

**必须包含的列：**
- `PLZ_ID`: PlaszymeDB标识符
- `protein_id`: XID标识符

**示例：**
```csv
PLZ_ID,protein_id,pLDDT,pTM,pdb_path,json_path
866554aa77,X0001,97.7,0.95,pdb/X0001.pdb,json/X0001.json
```

### 2. 文件命名规范

**PDB文件必须使用XID命名：**
- ✅ 正确: `X0001.pdb`, `X0223.pdb`
- ❌ 错误: `866554aa77.pdb`, `PLZ001.pdb`

### 3. 路径配置

**确保路径一致：**
- API中的路径: `structure_data/predicted_xid/pdb/`
- 实际文件位置: `C:\xampp\htdocs\plaszymedb\structure_data\predicted_xid\pdb\`

---

## 🎓 最佳实践

### 添加新结构

1. **准备文件**
   - PDB文件命名为 `{XID}.pdb`
   - 放入对应的pdb文件夹

2. **更新Metadata**
   - 在CSV文件中添加新行
   - 确保PLZ_ID和protein_id正确

3. **测试**
   - 使用test_structure_api.php验证
   - 在test_3d_viewer.html中加载测试

### 性能监控

- 使用浏览器开发者工具监控加载时间
- 大型PDB文件可能需要较长加载时间
- 考虑使用CDN加速静态资源

### 错误排查

1. **检查控制台**
   - 浏览器F12查看错误
   - 查看Network标签的请求状态

2. **运行诊断**
   - 访问 `test_structure_api.php`
   - 查看详细的系统状态

3. **查看日志**
   - Apache错误日志: `C:\xampp\apache\logs\error.log`
   - PHP错误信息

---

## 📈 未来改进建议

### 短期

1. **添加结构质量评分**
   - 在API响应中包含pLDDT/分辨率
   - 前端显示质量指标

2. **实现结构比较**
   - 支持预测vs实验结构对比
   - 叠加显示

### 长期

1. **数据库存储映射**
   - 将PLZ_ID-XID映射存入数据库
   - 提高查询性能

2. **CDN集成**
   - 将PDB文件上传到CDN
   - 减轻服务器负载

3. **增量更新**
   - 支持结构数据的增量更新
   - 版本管理

---

## 🎉 完成状态

### 所有任务完成 ✅

- ✅ 修改 `api_protein_structure.php`
- ✅ 实现PLZ_ID到XID映射
- ✅ 创建测试页面
- ✅ 编写完整文档
- ✅ 测试预测结构加载
- ✅ 测试实验结构加载

### 交付物

1. **核心功能**
   - 更新的API文件
   - PLZ_ID到XID自动映射
   - 新路径配置

2. **测试工具**
   - API测试页面
   - 3D查看器测试页面

3. **文档**
   - 技术配置说明
   - 快速使用指南
   - 更新总结

---

## 📞 支持

如有问题，请：

1. **查看文档**
   - `结构数据配置说明.md` - 技术细节
   - `结构数据快速使用指南.md` - 使用指南

2. **运行测试**
   - `test_structure_api.php` - 后端测试
   - `test_3d_viewer.html` - 前端测试

3. **检查控制台**
   - 浏览器开发者工具
   - Apache错误日志

---

**最后更新:** 2025-10-08  
**版本:** 1.0  
**状态:** ✅ 生产就绪

---

## 🙏 致谢

感谢您使用PlaszymeDB！希望这次更新能为您的研究工作带来便利。

如有任何问题或建议，欢迎随时反馈！

