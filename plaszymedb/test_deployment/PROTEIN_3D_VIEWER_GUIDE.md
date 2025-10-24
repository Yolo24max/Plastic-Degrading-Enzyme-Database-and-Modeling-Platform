# PlaszymeDB 蛋白质3D结构查看器 - 完整使用指南

## 🚀 项目概述

基于 Mol* 库构建的高性能蛋白质3D结构可视化功能，专为 PlaszymeDB 设计，支持基于 PLZ_ID 的一键加载和展示蛋白质结构。

### 核心特性

✅ **基于 PLZ_ID 自动加载**：通过主表的 PLZ_ID 作为主键关联对应的 .pdb 文件  
✅ **双数据源支持**：支持预测数据和实验数据的筛选切换  
✅ **交互式操作**：旋转、缩放、序列映射等基础操作  
✅ **嵌入式设计**：无缝集成到现有详情页面  
✅ **性能优化**：缓存机制、懒加载、错误恢复  
✅ **多种表示方式**：卡通、表面、球棍模式  
✅ **下载功能**：PDB文件和结构图片下载  

## 📁 文件结构

```
plaszymedb/
├── protein_viewer_optimized.js          # 优化版3D结构查看器 (推荐)
├── protein_3d_viewer.js                 # 基础版3D结构查看器
├── api_protein_structure.php            # 3D结构API接口
├── V9.html                              # 主页面（已集成3D功能）
├── test_protein_viewer.html             # 测试页面
├── pdb_predicted/                       # 预测数据
│   ├── pdb/                            # PDB文件
│   │   ├── 00d4a4bfbe.pdb             # 示例PDB文件
│   │   ├── 008a870d80.pdb
│   │   └── ...
│   └── json/                           # JSON元数据（可选）
└── pdb_experimental/                    # 实验数据
    ├── pdb/                            # PDB文件
    └── json/                           # JSON元数据（可选）
```

## 🛠 安装和配置

### 1. 依赖库

确保以下库已正确加载：

```html
<!-- Mol* Library -->
<script src="https://cdn.jsdelivr.net/npm/molstar@latest/build/viewer/molstar.js" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/molstar@latest/build/viewer/molstar.css">

<!-- 蛋白质3D结构查看器 (优化版) -->
<script src="protein_viewer_optimized.js"></script>
```

### 2. 数据文件组织

确保PDB文件按以下规则组织：

- **预测数据**：`pdb_predicted/pdb/{PLZ_ID}.pdb`
- **实验数据**：`pdb_experimental/pdb/{PLZ_ID}.pdb`

示例：
- `pdb_predicted/pdb/00d4a4bfbe.pdb`
- `pdb_experimental/pdb/00d4a4bfbe.pdb`

### 3. API接口配置

`api_protein_structure.php` 支持以下操作：

```php
// 获取结构信息
GET api_protein_structure.php?plz_id=00d4a4bfbe&type=predicted&action=info

// 获取PDB文件
GET api_protein_structure.php?plz_id=00d4a4bfbe&type=predicted&action=pdb

// 列出可用结构
GET api_protein_structure.php?type=predicted&action=list_available
```

## 💻 使用方法

### 1. 基础使用

```javascript
// 创建查看器实例
const viewer = new OptimizedProteinViewer('container-id', {
    onLoadStart: (plzId, dataType) => console.log('开始加载:', plzId),
    onLoadComplete: (plzId, dataType) => console.log('加载完成:', plzId),
    onLoadError: (plzId, dataType, error) => console.error('加载失败:', error)
});

// 加载结构
await viewer.loadStructure('00d4a4bfbe', 'predicted');
```

### 2. 在详情页面中使用

查看器已集成到 `V9.html` 的详情页面中：

```javascript
// 显示详情页面并加载3D结构
showDetailWithStructure('00d4a4bfbe');

// 或者使用原有的showDetail函数（会自动加载3D结构）
showDetail('00d4a4bfbe');
```

### 3. 测试功能

访问 `test_protein_viewer.html` 进行功能测试：

```javascript
// 测试可用的PLZ_ID
testStructureLoad('00d4a4bfbe');  // 示例ID
testStructureLoad('008a870d80');  // 另一个示例
```

## 🎮 用户界面操作

### 控制面板功能

1. **数据类型切换**
   - 预测数据：基于计算预测的结构
   - 实验数据：基于实验测定的结构

2. **视图控制**
   - 🔄 重置：恢复默认视角
   - 📺 全屏：全屏显示结构

3. **表示方式**
   - 卡通：蛋白质二级结构可视化
   - 表面：分子表面展示
   - 球棍：原子和键的详细视图

4. **下载功能**
   - 📁 PDB：下载原始PDB文件
   - 🖼️ 图片：下载当前视图截图

### 鼠标操作

- **左键拖拽**：旋转结构
- **滚轮**：缩放结构
- **右键拖拽**：平移结构
- **双击**：居中显示

## ⚡ 性能优化特性

### 1. 缓存机制

- 自动缓存已加载的结构数据
- 最大缓存5个结构
- 智能缓存清理策略
- 缓存命中率显示

### 2. 懒加载

- 查看器只有在需要时才初始化
- 使用 IntersectionObserver 检测可见性
- 减少页面初始加载时间

### 3. 错误恢复

- 自动重试机制（最多3次）
- 网络超时处理（30秒）
- 优雅的错误提示

### 4. 内存管理

- 自动清理不再使用的结构数据
- 查看器销毁时释放所有资源
- 防止内存泄漏

## 🔧 开发者API

### 创建查看器实例

```javascript
const viewer = new OptimizedProteinViewer(containerId, {
    // 基础配置
    layoutIsExpanded: false,
    layoutShowControls: true,
    layoutShowSequence: true,
    
    // 事件回调
    onInit: (viewer) => console.log('初始化完成'),
    onLoadStart: (plzId, dataType) => console.log('开始加载'),
    onLoadComplete: (plzId, dataType, data) => console.log('加载完成'),
    onLoadError: (plzId, dataType, error) => console.error('加载失败'),
    onCacheUpdate: (size, maxSize) => console.log(`缓存: ${size}/${maxSize}`)
});
```

### 主要方法

```javascript
// 加载结构
await viewer.loadStructure(plzId, dataType);

// 设置表示方式
await viewer.setRepresentation('cartoon' | 'surface' | 'ball-stick');

// 视图控制
await viewer.resetView();
viewer.toggleFullscreen();

// 下载功能
await viewer.downloadPDB();
await viewer.downloadImage();

// 状态查询
const state = viewer.getState();
const metrics = viewer.getPerformanceMetrics();

// 缓存管理
viewer.clearCache();

// 销毁查看器
viewer.destroy();
```

### 状态信息

```javascript
const state = viewer.getState();
console.log(state);
// {
//   isInitialized: true,
//   isLoading: false,
//   currentPlzId: "00d4a4bfbe",
//   currentDataType: "predicted",
//   currentRepresentation: "cartoon",
//   cacheSize: 3,
//   performanceMetrics: { ... }
// }
```

### 性能指标

```javascript
const metrics = viewer.getPerformanceMetrics();
console.log(metrics);
// {
//   initTime: 1234.56,      // 初始化时间(ms)
//   loadTimes: [100, 150],  // 加载时间数组
//   cacheHits: 5,           // 缓存命中次数
//   cacheMisses: 3,         // 缓存未命中次数
//   errors: 1,              // 错误次数
//   cacheHitRate: 0.625     // 缓存命中率
// }
```

## 🧪 测试指南

### 1. 功能测试

访问 `test_protein_viewer.html` 进行全面测试：

```javascript
// 基础功能测试
testLibraryLoad();      // 检查Mol*库
testAPIConnection();    // 测试API连接
initViewer();          // 初始化查看器

// 结构加载测试
loadStructure('00d4a4bfbe');    // 加载预测结构
loadStructure('00d4a4bfbe', 'experimental'); // 加载实验结构

// 控制功能测试
testRepresentation('cartoon');   // 测试表示方式
testResetView();                // 测试视图重置
testDownloadPDB();              // 测试PDB下载
```

### 2. 性能测试

```javascript
// 获取性能指标
const metrics = viewer.getPerformanceMetrics();
console.log('缓存命中率:', metrics.cacheHitRate);
console.log('平均加载时间:', metrics.loadTimes.reduce((a,b) => a+b) / metrics.loadTimes.length);
```

### 3. 错误测试

```javascript
// 测试不存在的PLZ_ID
loadStructure('NONEXISTENT_ID');

// 测试网络错误恢复
// (断开网络连接后尝试加载)
```

## 🐛 故障排除

### 常见问题

1. **结构无法加载**
   - 检查PDB文件是否存在于正确路径
   - 确认PLZ_ID格式正确
   - 查看浏览器控制台错误信息
   - 测试API接口是否正常响应

2. **Mol*库加载失败**
   - 检查网络连接
   - 确认CDN链接可访问
   - 查看浏览器开发者工具Network标签
   - 尝试使用本地Mol*库文件

3. **性能问题**
   - 清除浏览器缓存
   - 检查内存使用情况
   - 调整缓存大小设置
   - 使用性能分析工具

4. **API错误**
   - 确认PHP服务器正常运行
   - 检查文件路径权限
   - 查看服务器错误日志
   - 验证数据库连接

### 调试技巧

```javascript
// 启用详细日志
console.log('查看器状态:', viewer.getState());
console.log('性能指标:', viewer.getPerformanceMetrics());

// 检查缓存内容
console.log('缓存大小:', viewer.structureCache?.size);

// 监控事件
viewer.onLoadStart = (plzId, dataType) => {
    console.log(`[DEBUG] 开始加载: ${plzId} (${dataType})`);
};
```

## 🔄 版本更新

### v2.1 (当前版本) - 性能优化版

- ✅ 添加结构数据缓存
- ✅ 实现懒加载初始化
- ✅ 增强错误恢复机制
- ✅ 添加性能监控
- ✅ 优化内存管理

### v2.0 - 基础功能版

- ✅ 基于Mol*的3D结构可视化
- ✅ 支持预测/实验数据切换
- ✅ 多种表示方式
- ✅ 下载功能
- ✅ 嵌入式设计

## 📞 技术支持

如遇到问题，请：

1. 查看浏览器控制台错误信息
2. 检查网络连接和API响应
3. 参考本文档的故障排除部分
4. 使用测试页面验证功能

## 🎯 未来扩展

计划中的功能：

- [ ] 序列映射和高亮显示
- [ ] 结构比较功能
- [ ] 分子动力学轨迹播放
- [ ] 自定义样式和主题
- [ ] 结构注释和标记功能
- [ ] 多结构同时显示
- [ ] VR/AR支持

---

**PlaszymeDB Team** | 版本 2.1 | 2025年
