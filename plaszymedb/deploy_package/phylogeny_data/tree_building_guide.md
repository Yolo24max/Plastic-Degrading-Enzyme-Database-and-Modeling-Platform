# 系统发育树构建实用指南

## 🎯 推荐方案（按难易程度排序）

### 方案1：在线工具构建（最简单）

#### 1.1 使用NGPhylogeny.fr（推荐新手）
- 网址：https://ngphylogeny.fr/
- 步骤：
  1. 上传FASTA文件（建议先用fungal_enzymes.fasta测试）
  2. 选择"One Click Workflows" > "PhyML + SMS"
  3. 等待构建完成
  4. 下载.tree文件

#### 1.2 使用CIPRES Science Gateway
- 网址：https://www.phylo.org/
- 免费注册账户
- 适合较大数据集
- 支持多种算法

### 方案2：本地软件安装

#### 2.1 IQ-TREE（命令行，专业推荐）
下载地址：http://www.iqtree.org/
Windows版本：
1. 下载 iqtree-2.x.x-Windows.zip
2. 解压到 C:\IQ-TREE\
3. 将 C:\IQ-TREE\ 添加到系统PATH
4. 使用命令：
```bash
iqtree -s comprehensive_enzymes.fasta -m MFP -bb 1000 -nt AUTO
```

#### 2.2 MEGA（图形界面，用户友好）
下载地址：https://www.megasoftware.net/
步骤：
1. 下载并安装MEGA
2. 打开MEGA，选择"Phylogeny"
3. 导入FASTA文件
4. 选择构建方法（Maximum Likelihood推荐）
5. 导出树文件

### 方案3：使用conda安装IQ-TREE
```bash
conda install -c bioconda iqtree
```

## 📋 操作建议

### 测试顺序（按文件大小）：
1. **fungal_enzymes.fasta** (3KB, 10条序列) - 用于测试
2. **ec31_esterases.fasta** (54KB, 124条序列)
3. **bacterial_enzymes.fasta** (71KB, 154条序列)
4. **pet_enzymes.fasta** (72KB, 206条序列)
5. **pe_pp_enzymes.fasta** (116KB, 308条序列)
6. **comprehensive_enzymes.fasta** (306KB, 757条序列) - 最后处理

### 期望的输出文件：
- *.treefile 或 *.tree（用于iTOL上传）
- *.log（构建日志）
- *.iqtree（详细报告）

## 🚀 快速开始建议

**立即可以尝试：**
1. 访问 https://ngphylogeny.fr/
2. 上传 `fungal_enzymes.fasta`
3. 运行默认分析
4. 下载结果树文件
5. 测试上传到iTOL

这样您可以快速验证整个流程是否正确！
