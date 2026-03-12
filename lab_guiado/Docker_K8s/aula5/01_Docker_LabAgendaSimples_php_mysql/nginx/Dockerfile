#Imagem Base nginx
FROM nginx:1.27-alpine

WORKDIR /var/www/html

#Cópia da configuração do Nginx apontando para o php-fpm
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

#Cópia do código fonrte da aplicação
COPY config.php /var/www/html/config.php
COPY db.php /var/www/html/db.php
COPY index.php /var/www/html/index.php

EXPOSE 80