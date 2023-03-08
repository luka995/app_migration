<?php

echo <<<NGINX
server {
    listen 80;       
    server_name www.$sajtdomen;
    return 301 http://$sajtdomen/\$request_uri;
}
server {  

    listen       80;
    server_name  $sajtdomen;
    root         /var/www/$sajtnaziv;
    index index.php;
    #uklanja poslednju kosu crtu ukoliko postoji u URL-u
    rewrite ^/(.*)/$ /$1 permanent;
        
   # access_log  /var/www/$sajtnaziv/access.log;
   # error_log   /var/www/$sajtnaziv/error.log;
       
   location / {
        rewrite ^/strana/(\d+)/(.*)/?$ /strana.php?id=$1 last;
   }

   location /strana {
        rewrite ^/strana/(\d+)/(.*)/?$ /strana.php?id=$1 last;
   }


   location /video {
        rewrite ^/video/(\d+)/(.*)/?$ /video.php?id=$1 last;
   }

    location /galerija {
      rewrite ^/galerija/([0-9]+)/(.*)/?$ /galerija.php?do=view&id=$1 last;
    }

    location = /galerija {
      rewrite ^(.*)$ /galerija.php last;
    }

    location /videoc {
      rewrite ^/videoc/([^/]*)(/)([^/]*)(/?)([^/]*)(/?)([^/]*) /video.php?cid=$1&strana=$7 last;
    }

    location = /videoc {
      rewrite ^(.*)$ /video.php last;
    }

    location /downloadc {
      rewrite ^/downloadc/([^/]*)(/)([^/]*)(/?)([^/]*)(/?)([^/]*) /download.php?cid=$1&strana=$7 last;
    }

    location = /downloadc {
      rewrite ^(.*)$ /download.php last;
    }

    location /download {
      rewrite ^/download/([0-9]+)/(.*)/?$ /download.php?do=download&did=$1 last;
    }

    location /vestic {
      rewrite ^/vestic/strana/([^/]*)(/?) /vesti.php?strana=$1 last;
      rewrite ^/vestic/([0-9]+)/([^/]*)/([^/]*)/([0-9]+)(/?) /vesti.php?cid=$1&strana=$4 last;
      rewrite ^/vestic/([0-9]+)/([^/]*)(/?) /vesti.php?cid=$1&strana=$4 last;
    }

    location = /vestic {
      rewrite ^(.*)$ /vesti.php last;
    }

    location /vesti {
      rewrite ^/vesti/([0-9]+)/(.*)/?$ /vesti.php?do=view&vid=$1 last;
    }

    location /slika {
      rewrite ^/slika/([0-9]+)/?$ /galerija.php?do=slika&id=$1 last;
    }

    location = /Kontakt {
      rewrite ^(.*)$ /kontakt.php last;
      rewrite ^(.*)$ /kontakt.php last;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root/\$fastcgi_script_name;
	fastcgi_pass unix:/run/php/php5.6-fpm-$sajtnaziv.sock;
        try_files \$uri =404;
    }

  }
NGINX;