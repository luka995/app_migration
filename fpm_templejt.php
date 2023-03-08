<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

echo <<<FPM

[$sajtnaziv]
user = $sajtnaziv
group = $sajtnaziv
listen = /var/run/php/php5.6-fpm-$sajtnaziv.sock
listen.owner = www-data
listen.group = www-data
php_admin_value[disable_functions] = exec,passthru,shell_exec,system
php_admin_flag[allow_url_fopen] = on
pm = dynamic
pm.max_children = 15
pm.start_servers = 4
pm.min_spare_servers = 1
pm.max_spare_servers = 7
chdir = /

FPM;