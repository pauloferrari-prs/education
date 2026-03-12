# Aula 5 — Hands On - Agenda de Contatos com PHP + Nginx + PHP-FPM + MySQL

Este guia traz o **passo a passo completo** para construir uma aplicação simples de agenda de contatos em PHP, validar primeiro localmente, depois empacotar em uma imagem Docker e executar a aplicação conectando em um banco de dados MySQL também em container.

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit + Docker Instalado
- Acesso `sudo`
- Internet para baixar pacotes/imanges do repositório oficial da Docker / Hub
- Se quiser usar em VM, baixar essa VM (VirtualBox) ubuntu 24.04 com o docker instalado: <https://repo-aws-pferrari.s3.us-east-1.amazonaws.com/ubuntulab.ova>

---

## 1) Criar pasta raiz do "projeto/lab" e seus arquivos (Código Fonte Aplicação e configs)

No seu host/vm, criar uma pasta onde irá adicionar os arquivos necessários para montar a imagem:

- 1. ```mkdir -p ``` /home/ubuntu/lab_aula5_agendasimples ```
```bash
mkdir -p /home/ubuntu/lab_aula5_agendasimples
cd /home/ubuntu/lab_aula5_agendasimples
mkdir -p php nginx db
```

- 2. Estrutura da pasta:

```text
lab_aula5_agendasimples/
├── .env
├── .dockerignore
├── config.php
├── db.php
├── index.php
├── php/
│   └── Dockerfile
├── nginx/
│   ├── Dockerfile
│   └── default.conf
└── db/
    ├── Dockerfile
    └── schema.sql
```

- 3. Criar arquivo **/.env** :

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agenda_db
DB_USERNAME=agenda
DB_PASSWORD=agenda123
```

- 4. Criar arquivo **/.dockerignore** com esses path's / arquivos adicionados:

```text
.env
.git
.gitignore
README.md
```

- 5. Criar arquivo **db/schema.sql**:

```SQL
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

- 6. Criar arquivo **/config.php**:

```php
<?php

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

function envValue(string $key, ?string $default = null): string
{
    $value = getenv($key);
    return $value === false ? ($default ?? '') : $value;
}
```

- 7. Criar arquivo **/db.php**:

```php
<?php

require_once __DIR__ . '/config.php';

$host = envValue('DB_HOST', '127.0.0.1');
$port = envValue('DB_PORT', '3306');
$db   = envValue('DB_DATABASE', 'agenda_db');
$user = envValue('DB_USERNAME', 'agenda');
$pass = envValue('DB_PASSWORD', 'agenda123');

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erro de conexão com o banco: ' . $e->getMessage());
}
```

- 8. Criar arquivo **/index.php**:

```php
<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name !== '' && $email !== '' && $phone !== '') {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $phone]);
    }

    header('Location: /');
    exit;
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$_GET['delete']]);

    header('Location: /');
    exit;
}

$contacts = $pdo->query("SELECT * FROM contacts ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Agenda de Contatos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        form, table {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        input {
            padding: 10px;
            margin: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            padding: 10px 16px;
            border: none;
            background: #0077cc;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        a.delete {
            color: #c00;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Agenda de Contatos</h1>

    <form method="POST">
        <label>Nome</label>
        <input type="text" name="name" required>

        <label>E-mail</label>
        <input type="email" name="email" required>

        <label>Telefone</label>
        <input type="text" name="phone" required>

        <button type="submit">Salvar contato</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Criado em</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($contacts) > 0): ?>
                <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td><?= htmlspecialchars($contact['id']) ?></td>
                        <td><?= htmlspecialchars($contact['name']) ?></td>
                        <td><?= htmlspecialchars($contact['email']) ?></td>
                        <td><?= htmlspecialchars($contact['phone']) ?></td>
                        <td><?= htmlspecialchars($contact['created_at']) ?></td>
                        <td>
                            <a class="delete" href="/?delete=<?= $contact['id'] ?>" onclick="return confirm('Excluir contato?')">
                                Excluir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Nenhum contato cadastrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
```

- 9. Criar arquivo **nginx/default.conf**:

```Nginx
server {
    listen 80;
    server_name localhost;

    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass agenda-php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/html$fastcgi_script_name;
    }
}
```
---

## 2) Preparando o ambiente Docker:

No Path do seu **CONTEXT** para build da aplicação (``` /home/ubuntu/lab_aula5_agendasimples/ ```), execute esses 2 comandos:

- 1. Criar rede Docker: Essa rede permitirá a comunicação entre os containers.
```bash
docker network create agenda-net
```

- 2. Criar o volume docker do MySQL: Para persistir os dados.
```bash
docker volume create agenda-mysql-data
```

---

## 3) Criando a imagem (Dockerfile) do banco de dados MySQL

Nesse caso essa imagem do banco MySQL é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/lab_aula5_agendasimples/db ```

- 1. Criar o Dockerfile (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
# Base Image: mysql
FROM mysql:8.0

# Tudo que estiver em /docker-entrypoint-initdb.d/ será executado na inicialização do MySQL somente na primeira vez.
COPY schema.sql /docker-entrypoint-initdb.d/01-schema.sql
```

---

## 4) Construindo (Docker Build) a imagem do banco de dados MySQL, com base no Dockerfile

No Path do seu **CONTEXT** principal do projeto (``` /home/ubuntu/lab_aula5_agendasimples/ ```), execute esse comando:

```bash
docker build -t mysql-agenda-custom:1.0 ./db
```

---

## 5) Rodando o container (Docker Run) do banco MySQL


Agora vamos rodar o container, com base na imagem da aplicação do banco criada com o Dockerfile:

- 1. Comando Docker run
```bash
docker run -d \
  --name mysql-agenda \
  --network agenda-net \
  -p 3306:3306 \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=agenda_db \
  -e MYSQL_USER=agenda \
  -e MYSQL_PASSWORD=agenda123 \
  -v agenda-mysql-data:/var/lib/mysql \
  mysql-agenda-custom:1.0
```

- 2. Verificar se o banco subiu corretamente

```bash
docker logs -f mysql-agenda
```
```bash
docker exec -it mysql-agenda mysql -uagenda -pagenda123 -D agenda_db -e "SELECT * FROM contacts;"
```
```bash
docker exec -it mysql-agenda sh
```

---

## 6) Teste local da aplicação PHP

Aqui a ideia é mostrar primeiro a aplicação funcionando fora do container, mas conectando no MySQL que já está em container.

- 1. Instalar PHP localmente no host (UBUNTU)

```bash
sudo apt update
sudo apt install -y php-cli php-mysql
```

- 2. Rodar a aplicação localmente: ``` /home/ubuntu/lab_aula5_agendasimples/ ```

```bash
php -S localhost:8000
```

- 3. Abrir no navegador:

```bash
http://localhost:8000
```

- 4. Validar os registros no banco, após cadastrar alguns contatos no sistema:

```bash
docker exec -it mysql-agenda mysql -uagenda -pagenda123 -D agenda_db -e "SELECT * FROM contacts;"
```

---

## 7) Criando a imagem (Dockerfile) do PHP (Aplicação) + NGINX

Nesse caso essa imagem da aplicação (Código fonte), PHP e NGINX é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/lab_aula5_agendasimples/php ```

- 1. Criar o Dockerfile PHP (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
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
```

Na pasta que você criou: ``` /home/ubuntu/lab_aula5_agendasimples/nginx ```

- 1. Criar o Dockerfile Nginx (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
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
```

---

## 8) Construindo (Docker Build) a imagem da aplicação (php) e do Nginx, com base no Dockerfile

No Path do seu **CONTEXT** principal do projeto (``` /home/ubuntu/lab_aula5_agendasimples/ ```), execute esse comando:

- PHP
```bash
docker build -t agenda-php-fpm:1.0 -f php/Dockerfile .
```
- NGINX
```bash
docker build -t agenda-nginx:1.0 -f nginx/Dockerfile .
```

---

## 9) Rodando o container (Docker Run) da aplicação + nginx


Agora vamos rodar o container, com base na imagem da aplicação (php) e nginx criada com o Dockerfile:

- 1. Comando Docker run para o PHP (Aplicação)
```bash
docker run -d \
  --name agenda-php \
  --network agenda-net \
  -e DB_HOST=mysql-agenda \
  -e DB_PORT=3306 \
  -e DB_DATABASE=agenda_db \
  -e DB_USERNAME=agenda \
  -e DB_PASSWORD=agenda123 \
  agenda-php-fpm:1.0
```

- 1. Comando Docker run para o NGINX (WebServer)
```bash
docker run -d \
  --name agenda-nginx \
  --network agenda-net \
  -p 8080:80 \
  agenda-nginx:1.0
```

- 2. Verificar se a aplicação subiu corretamente

```bash
docker ps
```
```bash
docker logs agenda-nginx
```
```bash
docker logs agenda-php
```
```bash
docker logs mysql-agenda
```
```bash
docker stats
```

- 3. Abrir no navegador
```bash
http://localhost:8000
```

- 4. Validar os registros no banco, após cadastrar alguns contatos no sistema:

```bash
docker exec -it mysql-agenda mysql -uagenda -pagenda123 -D agenda_db -e "SELECT * FROM contacts;"
```

---

## 10) Dando um "Destroy" no LAB

Sequência de comando para apagar o lab criado no seu host/vm:

```bash
#Remover o container, com a opção -f (forçado, se o container estiver rodando)
docker rm -f agenda-nginx agenda-php mysql-agenda

#Remover o Volume Criado
docker volume rm agenda-mysql-data

#Remover o docker networks criado
docker network rm agenda-net

#Comando para limpar recuros não utilizados pelo docker:
docker system prune -a --volumes -f

#Verificar se foi removido
docker ps -a
docker images | docker image ls
```