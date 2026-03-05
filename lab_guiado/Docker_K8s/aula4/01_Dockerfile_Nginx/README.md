# Aula 4 — Hands On - Dockerfile Nginx

Este guia traz o **passo a passo completo** para montar uma imagem docker nginx e rodar a imagem em um container, usando os comandos mencionados na Aula 4

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit + Docker Instalado
- Acesso `sudo`
- Internet para baixar pacotes/imanges do repositório oficial da Docker / Hub
- Se quiser usar em VM, baixar essa VM (VirtualBox) ubuntu 24.04 com o docker instalado: <https://repo-aws-pferrari.s3.us-east-1.amazonaws.com/ubuntulab.ova>

---

## 1) Criar pasta raiz do "projeto/lab" do NGINX rodando no Docker

No seu host/vm, criar uma pasta onde irá adicionar os arquivos necessários para montar a imagem, colocar o arquivo index.html e subir o container (docker run)

- 1. mkdir -p ``` /home/ubuntu/nginx_lab_aula04_01 ```
- 2. Criar um arquivo **index.html** com esse código html:

```bash
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nginx Docker Lab</title>
    <style>
      body { font-family: Arial, Helvetica, sans-serif; margin: 40px; }
      .box { max-width: 700px; padding: 24px; border: 1px solid #ddd; border-radius: 12px; }
      code { background: #f5f5f5; padding: 2px 6px; border-radius: 6px; }
    </style>
  </head>
  <body>
    <div class="box">
      <h1>Nginx no Docker está funcionando!</h1>
      <p>Se você está vendo esta página, o container subiu e está servindo o <code>index.html</code> corretamente.</p>
      <p><strong>Path padrão:</strong> <code>/usr/share/nginx/html/index.html</code></p>
    </div>
  </body>
</html>
```

---

## 2) Criando a imagem do nginx

Nesse caso essa imagem do NGINX é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/nginx_lab_aula04_01 ```

- 1. Criar o Dockerfile (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:
    vi Dockerfile

```Dockerfile
# Imagem base que utilizaremos do nginx
FROM nginx:1.28.2-alpine-slim

# Parâmetros do build (ex: usuário/uid/gid), similar ao ENV, porém só existe durante a criação da imagem
ARG APP_USER=appuser
ARG APP_UID=10001
ARG APP_GID=10001

# Cria user non-root + prepara diretórios que o nginx usa em runtime
RUN addgroup -S -g ${APP_GID} ${APP_USER} \
 && adduser  -S -D -H -u ${APP_UID} -G ${APP_USER} ${APP_USER} \
 && mkdir -p /var/cache/nginx /tmp/nginx /tmp/client_temp /tmp/proxy_temp /tmp/fastcgi_temp /tmp/uwsgi_temp /tmp/scgi_temp \
 && chown -R ${APP_USER}:${APP_USER} /usr/share/nginx/html /etc/nginx /var/cache/nginx /tmp

# Diretório de trabalho (onde o COPY vai adicionar os arquivos)
WORKDIR /usr/share/nginx/html

# Copia o site e as configs (com owner:group correto)
COPY --chown=${APP_USER}:${APP_USER} index.html .
COPY --chown=${APP_USER}:${APP_USER} conf/nginx.conf /etc/nginx/nginx.conf
COPY --chown=${APP_USER}:${APP_USER} conf/default.conf /etc/nginx/conf.d/default.conf

# Troca para o nosso usuário appuser (non-root)
USER ${APP_USER}

# Processo principal (nginx) + args default
ENTRYPOINT ["nginx"]
CMD ["-g", "daemon off;"]
```
- Obs. Para consultar as versões (image tag) do Nginx (Imagens Default) segue o link: <https://hub.docker.com/_/nginx/tags>
- Exemplo Vulnerabilidade crítica: <https://hub.docker.com/_/nginx/tags?page=34>

---

## 3) Construindo (Docker Build) a imagem, com base no seu Dockerfile

No Path onde está o **DOCKERFILE** que criamos, execute esse comando:

```bash
docker build -t nginx-lab:1.0 .
```

---

## 4) Rodando o container (Docker Run)

Agora vamos rodar o container, com base na imagem nginx criada com o Dockerfile:

```bash
docker run --rm -d --name nginx-lab -p 8080:8080 nginx-lab:1.0
```
- Obs.: Aqui estou usando com a opção ```--rm```, pois é um lab, então assim que executarmos o comando ```docker stop nginx-lab``` o container será removido.
- Verificar logs depois de subir o container: ```docker logs -f nginx-lab```

## 5) Validando que o container está rodando como non-root (appuser)


```bash
docker exec -it nginx-lab sh -lc 'id && ps'
```


## 5) Valindado se o site "index.html" está no "ar"

Opção A (via curl):

```bash
curl -i http://localhost:8080
```

Opção B (Navegador):

```bash
http://localhost:8080
```

## 6) Dando um "Destroy" no LAB

Sequência de comando para apagar o lab criado no seu host/vm:

```bash
#Remover o container, com a opção -f (forçado, se o container estiver rodando)
docker rm -f nginx-lab

#Remover a imagem
docker rmi -f nginx-lab:1.0

#Comando para limpar recuros não utilizados pelo docker:
docker system prune -a --volumes -f

#Verificar se foi removido
docker ps -a
docker images | docker image ls
```


