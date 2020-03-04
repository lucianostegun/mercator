#!/bin/bash
HOSTNAMES="backend.skyplay worker1.skyplay worker2.skyplay worker3.skyplay worker4.skyplay worker5.skyplay"
SCRIPT="cd /var/www/skyplay.sky.com.br; rm -rf /var/www/skyplay.sky.com.br/web/temp/*; ls -la /var/www/skyplay.sky.com.br/web/temp; rm -rf /var/www/skyplay.sky.com.br/log/cron/*.log /var/www/skyplay.sky.com.br/log/cron/*-*; echo  > /var/www/skyplay.sky.com.br/log/debug.log; chmod 777 /var/www/skyplay.sky.com.br/log/debug.log"
# SCRIPT="setenforce Permissive"
# SCRIPT="crontab -u apache -r"
# SCRIPT="crontab -u apache /var/www/skyplay.sky.com.br/cron/worker/crontab"
# SCRIPT="chmod +x /var/www/skyplay.sky.com.br/cron/worker/*"
# SCRIPT="crontab -u apache -l"
# SCRIPT="shutdown -r now"
# SCRIPT="svn up /var/www/skyplay.sky.com.br/"
# SCRIPT="svn st /var/www/skyplay.sky.com.br/"
# SCRIPT="cat /etc/hosts"
# SCRIPT="sed -i \"s/SetEnv CONFIG_DB_HOSTNAME volkkerine/SetEnv CONFIG_DB_HOSTNAME 192.168.1.20/gi\" /etc/httpd/conf.d/00-skyplay.sky.com.br.conf"
# SCRIPT="sed -i \"s/SetEnv CONFIG_BACKEND_HOSTNAME cron.skyplay/SetEnv CONFIG_BACKEND_HOSTNAME backend.skyplay/gi\" /etc/httpd/conf.d/00-skyplay.sky.com.br.conf"
# SCRIPT="rm -rf /var/log/httpd/*; rm -rf /var/www/skyplay.sky.com.br/log/*.log; rm -rf /var/www/skyplay.sky.com.br/log/cron/*.log; service httpd restart; touch /var/www/skyplay.sky.com.br/log/php_errors.log; echo  > /var/www/skyplay.sky.com.br/log/php_errors.log; chmod 777 /var/www/skyplay.sky.com.br/log/php_errors.log"
key=0
for HOSTNAME in ${HOSTNAMES} ; do
#    echo "Acessando host ${HOSTNAMES[$key]}"
    echo "##### ${HOSTNAME} #####"
    echo ${SCRIPT}
    ssh root@${HOSTNAME} ${SCRIPT}
    echo ""
    echo ""
    key=$key+1
done
