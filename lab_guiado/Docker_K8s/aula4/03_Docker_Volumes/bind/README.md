# Aula 4 — Hands On - Volume: Bind

Este guia traz o **passo a passo completo** para montar uma imagem docker e executar com mount bind (Volume FS Host)

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit + Docker Instalado
- Acesso `sudo`
- Internet para baixar pacotes/imanges do repositório oficial da Docker / Hub
- Se quiser usar em VM, baixar essa VM (VirtualBox) ubuntu 24.04 com o docker instalado: <https://repo-aws-pferrari.s3.us-east-1.amazonaws.com/ubuntulab.ova>

---

## 1) Criar pasta raiz do "projeto/lab" do NGINX rodando no Docker

No seu host/vm, criar uma pasta onde irá adicionar os arquivos necessários para montar a imagem:

- 1. mkdir -p ``` /home/ubuntu/nginx_bind_lab_aula04_03 ```
- 2. Estrutura da pasta:

```text
nginx_bind_lab_aula04_03/
├── Dockerfile
├── nginx.conf
├── default.conf
├── app/
│   └── index.html
└── host-images/
    └── sample.jpg   (você vai colocar aqui)
```

- 3. Criar arquivo **index.html** com esse código html:

```html
<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8" />
    <title>Lab Bind Mount - NGINX</title>
  </head>
  <body>
    <h1>Lab Bind Mount (imagens no host)</h1>
    <p>Essa imagem vem do host via bind mount:</p>
    <img src="/images/sample.jpg" alt="sample" style="max-width: 520px; border: 1px solid #ccc;" />
  </body>
</html>
```

- 4. Criar um arquivo **nginx.conf** com essas informações:

```text
worker_processes auto;

events { worker_connections 1024; }

http {
  include       /etc/nginx/mime.types;
  default_type  application/octet-stream;

  access_log  /dev/stdout;
  error_log   /dev/stderr warn;

  sendfile        on;
  keepalive_timeout  65;

  # arquivos temporários em locais graváveis (non-root)
  client_body_temp_path /tmp/nginx/client_body;
  proxy_temp_path       /tmp/nginx/proxy;
  fastcgi_temp_path     /tmp/nginx/fastcgi;
  uwsgi_temp_path       /tmp/nginx/uwsgi;
  scgi_temp_path        /tmp/nginx/scgi;

  include /etc/nginx/conf.d/*.conf;
}
```

- 5. Criar um arquivo **default.conf** com essas informações:

```text
server {
  listen 8080;

  root /usr/share/nginx/html;
  index index.html;

  location / {
    try_files $uri $uri/ =404;
  }
}
```

- 6. Coloar uma imagem sample na pasta host-images

```text
No path: ./host-images colocar uma imagem sample.jpg = ./host-images/sample.jpg
```


---

## 2) Criando a imagem (Dockerfile) da aplicação python (calc simples)

Nesse caso essa imagem do nginx é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/nginx_bind_lab_aula04_03 ```

- 1. Criar o Dockerfile (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
# Imagem base que utilizaremos do nginx
FROM nginx:1.28.2-alpine-slim

# Parâmetros do build (ex: usuário/uid/gid), similar ao ENV, porém só existe durante a criação da imagem
ARG APP_USER=appuser
ARG APP_UID=10001
ARG APP_GID=10001

# usuário non-root
RUN addgroup -S -g ${APP_GID} ${APP_USER} \
 && adduser  -S -D -H -u ${APP_UID} -G ${APP_USER} ${APP_USER} \
 && mkdir -p /var/cache/nginx /tmp/nginx \
           /tmp/client_temp /tmp/proxy_temp /tmp/fastcgi_temp /tmp/uwsgi_temp /tmp/scgi_temp \
           /run /var/run \
 && chown -R ${APP_USER}:${APP_USER} /usr/share/nginx/html /var/cache/nginx /tmp /run /var/run

# Diretório de trabalho (onde o COPY vai adicionar os arquivos)
WORKDIR /usr/share/nginx/html

# app dentro da imagem (imutável até rebuild)
COPY --chown=${APP_USER}:${APP_USER} app/index.html .

# configs do nginx
COPY --chown=${APP_USER}:${APP_USER} nginx.conf /etc/nginx/nginx.conf
COPY --chown=${APP_USER}:${APP_USER} default.conf /etc/nginx/conf.d/default.conf

# Documenta a porta
EXPOSE 8080

# Troca para o nosso usuário appuser (non-root)
USER ${APP_USER}

# Processo principal (nginx) + args default
ENTRYPOINT ["nginx"]
CMD ["-g", "daemon off;"]
```

---

## 3) Construindo (Docker Build) a imagem, com base no seu Dockerfile

No Path onde está o **DOCKERFILE** que criamos, execute esse comando:

```bash
docker build -t nginx-bindmount-lab:1.0 .
```

---

## 4) Rodando o container (Docker Run)

Agora vamos rodar o container, com base na imagem da aplicação python criada com o Dockerfile:

- 1. Roda o comando que está no CMD (--help):

```bash
docker run -d --name nginx-bm -p 8080:8080 -v "$PWD/host-images:/usr/share/nginx/html/images:ro" nginx-bindmount-lab:1.0
```

- "Dissecando" o comando Docker Run (Options):
  - **a. --name:** Define um nome fixo para o container (em vez do docker gerar um aleatório) uso prático (facilita na hora de entrar "exec" no container ou consultar logs)
  - **b. -p:** Famoso Port Mapping - Faz o mapeamento de portas entre host e container - <PORTA_HOST>:<PORTA_CONTAINER>;
  - **c. -d:** Roda em background (detached mode) - sem esse comando, o container roda "preso" no terminal, vendo os logs da criação + container na tela (terminal).
  - **d. -v:** Opção no Docker para criar um Bind Mount ou um Volume. ro = readonly

## 5) Rodando o container (Docker Run) de forma em que podemos "debugar" entrar no container (persistir a criação)


- 1. Explorar o container:

```bash
docker exec -it nginx-bm sh
```

## 6) Valindado se o site "index.html" está no "ar"

Opção A (via curl):

```bash
curl -i http://localhost:8080
```

Opção B (Navegador):

```bash
http://localhost:8080
```

## 7) Dando um "Destroy" no LAB

Sequência de comando para apagar o lab criado no seu host/vm:

```bash
#Remover o container, com a opção -f (forçado, se o container estiver rodando)
docker rm -f nginx-bm

#Remover a imagem
docker rmi -f nginx-bindmount-lab:1.0

#Comando para limpar recuros não utilizados pelo docker:
docker system prune -a --volumes -f

#Verificar se foi removido
docker ps -a
docker images | docker image ls
```