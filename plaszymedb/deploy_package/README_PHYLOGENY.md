# PlaszymeDB 系统发育树集成说明

## 概述
PlaszymeDB的PHYLOGENY页面现已实现与iTOL (Interactive Tree of Life) 平台的集成，采用分层架构模式提供交互式系统发育树可视化功能。系统现已完全集成数据库，动态显示实时的序列统计信息。

## 技术架构

### 分层架构模式
- **前端层**: PlaszymeDB网站的iframe容器，动态加载iTOL共享项目
- **中间层**: PHP后端处理URL重写和参数传递，映射iTOL共享资源
- **后端层**: iTOL平台提供的实际系统发育树数据和可视化

### 文件结构
```
plaszymedb/
├── V9.html                 # 主页面文件，包含更新的PHYLOGENY页面
├── interactive_tree.php    # 中间层PHP处理文件，现已集成数据库
├── api_dataset_stats.php   # 数据集统计API端点
├── db_config.php           # 数据库配置文件
├── search.php              # 搜索功能，共享数据库连接
├── .htaccess              # URL重写规则，包含新的API路由
├── logs/                  # 访问日志目录
└── README_PHYLOGENY.md    # 本说明文件
```

## 功能特性

### 1. 动态树图加载
- 通过iframe容器动态加载iTOL共享项目
- 支持多种树图数据集（综合、PET、PE&PP、EC3.1、细菌、真菌）
- 实时参数调整和视图更新
- **NEW**: 序列统计信息实时从数据库获取，确保数据准确性

### 2. 交互式控制面板
- **数据集选择**: 6种不同的酶类数据集
- **显示模式**: 矩形、圆形、无根树布局
- **颜色方案**: 按塑料类型、EC编号、宿主生物、Bootstrap值着色
- **操作按钮**: 更新、重置、全屏、导出功能

### 3. URL重写支持
支持以下URL格式：
- `/interactive_tree/comprehensive` - 加载综合数据集
- `/interactive_tree/pet/circular` - 指定数据集和显示模式
- `/interactive_tree/ec31/rectangular/plastic` - 完整参数指定
- `/api/tree/pet` - JSON API接口
- `/api/dataset-stats` - **NEW**: 获取实时数据集统计信息

### 4. 快速访问链接
页面底部提供6个快速访问按钮，支持一键切换不同数据集：
- 🌍 所有酶类 (动态统计)
- 🧪 PET降解酶 (动态统计)  
- ⚗️ 酯酶类 (动态统计)
- 🦠 细菌酶 (动态统计)
- 🍄 真菌酶 (动态统计)
- 🔬 PE&PP降解酶 (动态统计)

**注意**: 序列数量现在实时从数据库获取，随数据库更新自动变化。

## 配置说明

### 1. iTOL项目映射
在`interactive_tree.php`中配置iTOL共享项目ID：
```php
$itol_projects = [
    'comprehensive' => 'aixuewang2024',
    'pet' => 'plz_pet_enzymes',
    'pe_pp' => 'plz_pe_pp_degraders',
    'ec31' => 'plz_esterases',
    'bacterial' => 'plz_bacterial',
    'fungal' => 'plz_fungal'
];
```

### 2. 服务器配置
确保Apache服务器启用以下模块：
- mod_rewrite (URL重写)
- mod_expires (缓存控制)
- mod_deflate (压缩)

### 3. 日志记录
系统会自动记录访问日志到`logs/tree_access.log`，包含：
- 访问时间
- 数据集选择
- 显示参数
- 用户IP和User Agent

## 使用方法

### 1. 基本使用
1. 访问PlaszymeDB主页
2. 点击导航栏中的"PHYLOGENY"
3. 使用控制面板选择数据集和显示参数
4. 点击"Update Tree"按钮更新视图

### 2. 直接URL访问
可以直接访问特定的树图：
- `http://yoursite.com/plaszymedb/interactive_tree/pet`
- `http://yoursite.com/plaszymedb/interactive_tree.php?dataset=comprehensive&mode=circular`

### 3. API调用
获取JSON格式的树图信息：
```javascript
fetch('/plaszymedb/api/tree/pet')
  .then(response => response.json())
  .then(data => console.log(data));
```

## 样式和交互

### 1. 响应式设计
- 支持桌面和移动设备
- 自适应布局调整
- 触摸友好的控制界面

### 2. 加载状态
- 显示加载动画和进度信息
- 错误处理和重试机制
- 网络连接状态检测

### 3. 用户体验
- 平滑的页面切换动画
- 直观的控制面板布局
- 实时的视觉反馈

## 维护和更新

### 1. 数据更新
当数据库中添加新的酶序列时：
1. 重新生成系统发育树
2. 上传到iTOL平台
3. 更新`interactive_tree.php`中的项目映射
4. 更新序列数量统计

### 2. 性能监控
- 监控`logs/tree_access.log`了解使用情况
- 检查iframe加载性能
- 优化缓存设置

### 3. 安全考虑
- 验证所有用户输入参数
- 防止XSS和CSRF攻击
- 限制访问频率

## 故障排除

### 常见问题
1. **树图无法加载**: 检查网络连接和iTOL服务状态
2. **参数无效**: 验证数据集名称和显示参数
3. **权限错误**: 检查文件权限和目录访问权限
4. **缓存问题**: 清除浏览器缓存或使用强制刷新

### 调试信息
启用JavaScript控制台查看详细的加载信息和错误消息。

## 未来改进

### 计划功能
- [ ] 离线树图缓存
- [ ] 自定义颜色方案
- [ ] 批量导出功能
- [ ] 用户个性化设置
- [ ] 移动端优化

### 性能优化
- [ ] CDN加速
- [ ] 图片压缩优化
- [ ] 异步加载优化
- [ ] 缓存策略改进

---

*最后更新: 2025年1月* 
