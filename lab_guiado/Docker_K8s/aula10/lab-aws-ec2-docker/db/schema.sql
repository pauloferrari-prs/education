CREATE DATABASE IF NOT EXISTS labphp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE labphp;

CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    short_description VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(1024) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (name, short_description, description, price, image_url)
VALUES
('Fone Bluetooth Pro', 'Som limpo, bateria longa e design leve.', 'Fone Bluetooth com cancelamento de ruído básico, conexão estável e ótima autonomia para uso diário.', 299.90, 'https://SEU_BUCKET.s3.REGIAO.amazonaws.com/products/fone-bluetooth-pro.jpg'),
('Smartwatch Fit One', 'Monitoramento de saúde e notificações.', 'Relógio inteligente com monitor cardíaco, contagem de passos, notificações e resistência à água.', 499.90, 'https://SEU_BUCKET.s3.REGIAO.amazonaws.com/products/smartwatch-fit-one.jpg'),
('Mochila Urban Tech', 'Compartimentos para notebook e acessórios.', 'Mochila urbana para rotina de trabalho, com espaço acolchoado para notebook e bolso anti-furto.', 249.90, 'https://SEU_BUCKET.s3.REGIAO.amazonaws.com/products/mochila-urban-tech.jpg')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
short_description = VALUES(short_description),
description = VALUES(description),
price = VALUES(price),
image_url = VALUES(image_url);
