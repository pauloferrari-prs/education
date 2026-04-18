# Lab Docker Compose — Aplicação PHP + Nginx + MySQL

Este laboratório mostra como subir uma aplicação simples usando **Docker Compose**, separando os componentes em **3 containers**:

- **MySQL** para banco de dados;
- **PHP-FPM** para executar a aplicação PHP;
- **Nginx** para servir a aplicação web.

A stack usa uma **rede dedicada (`agenda-net`)**, um **volume nomeado (`agenda-mysql-data`)** para persistência do banco e **healthchecks** nos 3 serviços. A aplicação fica publicada na porta **8089** do host. 

## 1) Objetivo do laboratório

Ao final deste laboratório, você será capaz de:

- Subir uma aplicação multicontainer com `docker compose`;
- Entender a separação entre **web**, **aplicação** e **banco**;
- Validar containers, logs, rede, volume e healthcheck;
- Praticar o fluxo de **build + subida + teste + parada** da stack.

## 2) Arquitetura da solução

A stack é composta por:

- **mysql-agenda**: Container do MySQL com volume persistente;
- **agenda-php**: Container da aplicação PHP-FPM, conectado ao banco por variáveis de ambiente;
- **agenda-nginx**: Container web exposto na porta `8089:80`, responsável por atender as requisições HTTP.

O serviço PHP depende do MySQL com condição `service_healthy`, e o Nginx depende do container PHP. Isso ajuda a subir a stack em uma ordem mais segura. 

## 3) Estrutura do projeto

```text
├── compose.yaml
├── config.php
├── db
│   ├── Dockerfile
│   └── schema.sql
├── db.php
├── index.php
├── lab1.md
├── nginx
│   ├── default.conf
│   └── Dockerfile
└── php
    └── Dockerfile
```

### Papel de cada arquivo

- **compose.yaml**: Define os serviços, rede, volume, limites de CPU/memória, healthchecks e dependências entre os containers;
- **index.php**: página principal da aplicação;
- **config.php**: Arquivo de configuração da aplicação;
- **db.php**: Arquivo de conexão/acesso ao banco de dados;
- **db/Dockerfile**: Imagem customizada do MySQL;
- **db/schema.sql**: Script SQL usado para criar a estrutura inicial do banco;
- **php/Dockerfile**: Imagem da aplicação PHP-FPM;
- **nginx/Dockerfile**: Imagem do Nginx;
- **nginx/default.conf**: Configuração do Nginx para servir a aplicação.

## 4) Pré-requisitos

Antes de começar, garanta que você tenha:

- Docker instalado;
- Docker Compose Plugin disponível (`docker compose version`);
- Acesso ao terminal da sua máquina ou VM com permissão sudo (super user);
- Internet para baixar dependências e imagens base.

## 5) Entendendo o Compose

No arquivo `compose.yaml`, a stack foi definida com 3 serviços:

### mysql-agenda

- Build da imagem a partir de `./db/Dockerfile`;
- Nome da imagem: `mysql-agenda-custom:1.0`;
- Volume nomeado em `/var/lib/mysql` para persistir dados;
- Healthcheck com `mysqladmin ping`;
- Limite de `1.0` CPU e `512m` de memória.

### agenda-php

- Build usando `./php/Dockerfile`;
- Recebe variáveis de ambiente com host, porta, banco, usuário e senha;
- Depende do MySQL com `condition: service_healthy`;
- Healthcheck com `php-fpm -t`;
- Limite de `0.50` CPU e `256m` de memória.

### agenda-nginx

- Build usando `./nginx/Dockerfile`;
- Publica a aplicação em `8089:80`;
- Depende do serviço PHP;
- Healthcheck acessando `http://127.0.0.1/`;
- Limite de `0.25` CPU e `128m` de memória.

## 6) Subindo a aplicação

Para subir a stack já com build das imagens:

```bash
docker compose up -d --build
```

Esse comando:

- Monta as imagens customizadas do banco, PHP e Nginx;
- Cria a rede `agenda-net`;
- Cria o volume `agenda-mysql-data`;
- Sobe os containers em background (detached mode).

## 7) Validando a stack

Verifique se os containers estão em execução:

```bash
docker compose ps
```

Acompanhe os logs:

```bash
docker compose logs -f
```

Teste a aplicação no navegador ou com `curl`:

```bash
curl http://localhost:8089
```

Se tudo estiver correto, o Nginx deve responder pela porta publicada no host.

## 8) Fluxo resumido do laboratório

```bash
# Subir a stack com build
 docker compose up -d --build

# Verificar containers
 docker compose ps

# Acompanhar logs
 docker compose logs -f

# Testar aplicação
 curl http://localhost:8089

# Derrubar a stack
 docker compose down
```

## 9) Inspeções úteis no laboratório

### Inspecionar o container do banco

```bash
docker inspect mysql-agenda
```

### Inspecionar a rede da aplicação

```bash
docker network inspect agenda-net
```

### Subir novamente sem rebuild

```bash
docker compose up -d
```

## 10) O que este laboratório ensina na prática

Com esse laboratório, você observa na prática que:

- Cada container tem uma responsabilidade específica;
- Containers se comunicam por **rede interna**;
- O banco precisa de **persistência**;
- Healthcheck ajuda a validar se o serviço está pronto;
- O `docker compose` simplifica o ciclo de vida de aplicações com vários containers.

## 11) Troubleshooting básico

### A aplicação não sobe

Verifique os logs:

```bash
docker compose logs -f
```

### A porta 8089 não responde

Confirme se o container do Nginx está em execução:

```bash
docker compose ps
```

### O banco não inicia corretamente

Inspecione o container MySQL:

```bash
docker inspect mysql-agenda
```

### Quero apagar tudo e começar do zero

```bash
docker compose down -v
```

> Atenção: o `-v` remove também o volume do banco, apagando os dados persistidos.

## 12) Encerrando o laboratório

Para parar e remover os containers da stack:

```bash
docker compose down
```

Se quiser subir novamente depois, basta executar:

```bash
docker compose up -d
```

## 13) Conclusão

Este laboratório é uma excelente introdução ao uso de **Docker Compose** no mundo real. Em vez de subir tudo manualmente container por container, você define a aplicação como uma **stack declarativa**, com rede, volume, dependências e validações de saúde em um único arquivo.

Aproximando você de cenários reais de operação, troubleshooting e organização de ambientes multicontainer.

---

### Observação: Explicação do compose.yaml, está dento do próprio arquivo em comentários (#)