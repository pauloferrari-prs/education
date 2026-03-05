# Aula 4 — Hands On - Volume: Bind

Este guia traz o **passo a passo completo** para:

- 1. Criar uma imagem MySQL com um script de inicialização (schema + dados).
- 2. Rodar o container com o volume mysql_data
- 3. Remover o container e subir outro (com outro nome) apontando para o mesmo volume (mysql_data), provando persistência.

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit + Docker Instalado
- Acesso `sudo`
- Internet para baixar pacotes/imanges do repositório oficial da Docker / Hub
- Se quiser usar em VM, baixar essa VM (VirtualBox) ubuntu 24.04 com o docker instalado: <https://repo-aws-pferrari.s3.us-east-1.amazonaws.com/ubuntulab.ova>

---

## 1) Criar pasta raiz do "projeto/lab" do NGINX rodando no Docker

No seu host/vm, criar uma pasta onde irá adicionar os arquivos necessários para montar a imagem:

- 1. mkdir -p ``` /home/ubuntu/mysql_volume_lab_aula04_03 ```
- 2. Estrutura da pasta:

```text
mysql_volume_lab_aula04_03/
├── Dockerfile
└── init/
    └── 01_schema.sql
```

- 3. Criar arquivo **01_schema.sql** com esse código sql:

```sql
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE
);

INSERT INTO customers (name, email)
VALUES ('Maria', 'maria@example.com');
```


---

## 2) Criando a imagem (Dockerfile) da aplicação mysql

Nesse caso essa imagem do mysql é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/mysql_volume_lab_aula04_03 ```

- 1. Criar o Dockerfile (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
FROM mysql:8.0.36

LABEL org.opencontainers.image.title="mysql-volume-lab" \
      org.opencontainers.image.version="1.0.0"

# Script roda somente no 1º start (quando /var/lib/mysql está vazio)
COPY init/ /docker-entrypoint-initdb.d/
```

---

## 3) Construindo (Docker Build) a imagem, com base no seu Dockerfile

No Path onde está o **DOCKERFILE** que criamos, execute esse comando:

```bash
docker build -t mysql-volume-lab:1.0 .
```

---

## 3) Criando o volume mysql_data


```bash
docker volume create mysql_data
```


## 5) Rodando o container (Docker Run)

Agora vamos rodar o container, com base na imagem da aplicação python criada com o Dockerfile:

- 1. Roda o comando que está no CMD (--help):

```bash
docker run -d --name mysql-vol -p 3306:3306 \
  -e MYSQL_ROOT_PASSWORD='rootpass' \
  -e MYSQL_DATABASE='labdb' \
  -e MYSQL_USER='lab' \
  -e MYSQL_PASSWORD='labpass' \
  -v mysql_data:/var/lib/mysql \
  mysql-volume-lab:1.0
```

- "Dissecando" o comando Docker Run (Options):
  - **a. --name:** Define um nome fixo para o container (em vez do docker gerar um aleatório) uso prático (facilita na hora de entrar "exec" no container ou consultar logs)
  - **b. -p:** Famoso Port Mapping - Faz o mapeamento de portas entre host e container - <PORTA_HOST>:<PORTA_CONTAINER>;
  - **c. -d:** Roda em background (detached mode) - sem esse comando, o container roda "preso" no terminal, vendo os logs da criação + container na tela (terminal).
  - **d. -v:** Opção no Docker para criar um Bind Mount ou um Volume. ro = readonly
  - **d. -e:** Serve para definir as variáveis de ambiente dentro do container.


## 6) Consultando dados dentro do Banco

- 1. Executando SELECT em uma tabela:
```bash
docker exec -it mysql-vol mysql -ulab -plabpass -D labdb \
  -e "SELECT * FROM customers;"
```

- 2. Inserindo INSERT INTO em uma tabela:
```bash
docker exec -it mysql-vol mysql -ulab -plabpass -D labdb \
  -e "INSERT INTO customers(name,email) VALUES('Paulo','paulo@example.com');"
```

## 7) Provando persistência

- 1. Apagar o container
```bash
docker rm -f mysql-vol
```

- 2. Suba de novo o container com outro nome, mas apontando para o mesmo volume:
```bash
docker run -d --name mysql-vol_2 -p 3306:3306 \
  -e MYSQL_ROOT_PASSWORD='rootpass' \
  -e MYSQL_DATABASE='labdb' \
  -e MYSQL_USER='lab' \
  -e MYSQL_PASSWORD='labpass' \
  -v mysql_data:/var/lib/mysql \
  mysql-volume-lab:1.0
```

- 3. Executando SELECT em uma tabela:
```bash
docker exec -it mysql-vol_2 mysql -ulab -plabpass -D labdb \
  -e "SELECT * FROM customers;"
```


## 8) Dando um "Destroy" no LAB

Sequência de comando para apagar o lab criado no seu host/vm:

```bash
#Remover o container, com a opção -f (forçado, se o container estiver rodando)
docker rm -f mysql-vol

#Comando para limpar recuros não utilizados pelo docker:
docker system prune -a --volumes -f

#Apagar Volume
docker volume rm mysql_data

#Verificar se foi removido
docker ps -a
docker images | docker image ls
```