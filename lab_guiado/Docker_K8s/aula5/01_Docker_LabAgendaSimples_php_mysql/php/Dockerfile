#Imagem Base php
FROM php:8.2-fpm

WORKDIR /var/www/html

#Instalação de dependências (pdo no caso é o driver de conexão para o PHP conectar no banco)
RUN docker-php-ext-install pdo pdo_mysql

#Cópia do código fonrte da aplicação
COPY config.php /var/www/html/config.php
COPY db.php /var/www/html/db.php
COPY index.php /var/www/html/index.php

EXPOSE 9000