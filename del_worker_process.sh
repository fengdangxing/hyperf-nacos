#!/bin/bash
#必须使用该方法触发-才会及时下线删除
php_process=`ps -ef |grep $1 | awk '{print $1}'`;
for line in $php_process;do kill -9 $line;done