#!/bin/bash
echo "============================================"
echo "获取 EC2 公网访问地址"
echo "============================================"
echo ""

# 方法1: 从 EC2 元数据获取
echo "方法1: EC2 元数据服务"
PUBLIC_IP=$(curl -s --max-time 3 http://169.254.169.254/latest/meta-data/public-ipv4)
if [ -n "$PUBLIC_IP" ] && [ "$PUBLIC_IP" != "404" ]; then
    echo "  公网 IPv4: $PUBLIC_IP"
else
    echo "  ✗ 未能从元数据获取"
fi
echo ""

# 方法2: 使用外部服务
echo "方法2: 外部服务 (ipinfo.io)"
PUBLIC_IP2=$(curl -s --max-time 3 https://ipinfo.io/ip)
if [ -n "$PUBLIC_IP2" ]; then
    echo "  公网 IP: $PUBLIC_IP2"
else
    echo "  ✗ 未能从 ipinfo.io 获取"
fi
echo ""

# 方法3: 使用 ifconfig.me
echo "方法3: 外部服务 (ifconfig.me)"
PUBLIC_IP3=$(curl -s --max-time 3 https://ifconfig.me)
if [ -n "$PUBLIC_IP3" ]; then
    echo "  公网 IP: $PUBLIC_IP3"
else
    echo "  ✗ 未能从 ifconfig.me 获取"
fi
echo ""

# 检查 Apache 是否监听外部连接
echo "Apache 监听端口："
sudo netstat -tlnp | grep :80 || ss -tlnp | grep :80
echo ""

# 选择一个可用的 IP
FINAL_IP=""
if [ -n "$PUBLIC_IP" ] && [ "$PUBLIC_IP" != "404" ]; then
    FINAL_IP=$PUBLIC_IP
elif [ -n "$PUBLIC_IP2" ]; then
    FINAL_IP=$PUBLIC_IP2
elif [ -n "$PUBLIC_IP3" ]; then
    FINAL_IP=$PUBLIC_IP3
fi

if [ -n "$FINAL_IP" ]; then
    echo "============================================"
    echo "✓ 找到公网 IP: $FINAL_IP"
    echo "============================================"
    echo ""
    echo "请在浏览器中访问以下 URL："
    echo ""
    echo "1. API 信息查询:"
    echo "   http://$FINAL_IP/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=info"
    echo ""
    echo "2. 下载 PDB 文件:"
    echo "   http://$FINAL_IP/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=pdb"
    echo ""
    echo "3. 获取 JSON 元数据:"
    echo "   http://$FINAL_IP/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=json"
    echo ""
    echo "4. 测试不存在的 PLZ_ID (应返回错误):"
    echo "   http://$FINAL_IP/api_protein_structure.php?plz_id=test123&type=predicted&action=info"
    echo ""
    
    # 从服务器自身测试
    echo "============================================"
    echo "从服务器测试公网访问"
    echo "============================================"
    echo ""
    
    echo "测试 1: 获取结构信息"
    curl -s "http://$FINAL_IP/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=info" | head -20
    echo ""
    echo ""
    
    echo "测试 2: 获取 PDB 文件头"
    curl -s "http://$FINAL_IP/api_protein_structure.php?plz_id=866554aa77&type=predicted&action=pdb" | head -5
    echo ""
    
else
    echo "============================================"
    echo "⚠️ 无法获取公网 IP"
    echo "============================================"
    echo ""
    echo "可能的原因："
    echo "  1. EC2 实例没有分配公网 IP"
    echo "  2. 使用的是 VPC 私有子网"
    echo "  3. 需要通过 Elastic IP 或负载均衡器访问"
    echo ""
    echo "请检查："
    echo "  - EC2 控制台中的实例详情"
    echo "  - 是否分配了公网 IPv4 地址"
    echo "  - 安全组是否允许 HTTP (端口 80) 入站流量"
fi

echo ""
echo "============================================"
echo "检查完成"
echo "============================================"

