# 系统发育树构建指南

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
