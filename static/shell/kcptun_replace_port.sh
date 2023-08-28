#!/bin/bash -x

xray_config='/root/kcptun/config.json'
listen=$(cat $xray_config | jq .listen)
read -p "当前监听端口值为：$listen,请输入要替换的值：" listen_port
read -p "请输入firewall需要关闭的端口：" close_port
read -p "请输入firewall需要打开的端口：" open_port

context=$(jq --arg listen $listen_port '.listen = $listen' config.json)
echo $context | jq > $xray_config
systemctl restart kcptun

firewall-cmd --zone=public --remove-port="$close_port"/tcp --permanent
firewall-cmd --zone=public --add-port="$open_port"/tcp --permanent
firewall-cmd --reload
