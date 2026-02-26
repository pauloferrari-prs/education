# Aula 3 — Hands On Imagem x Container

Este guia traz o **passo a passo completo** para montar uma imagem docker "default" nginx e rodar a imagem em um container.

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit + Docker Instalado
- Acesso `sudo`
- Internet para baixar pacotes/imanges do repositório oficial da Docker / Hub

---

## 1) Criar pasta raiz do "projeto/lab" do NGINX rodando no Docker

No seu host/vm, criar uma pasta onde irá adicionar os arquivos necessários para montar a imagem, colocar o arquivo index.html e subir o container (docker run)

- 1. mkdir -p ``` /home/ubuntu/nginx_sample ```
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

## 2) Criando a imagem simples (Default apenas para lab) do nginx

Nesse caso essa imagem do NGINX é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/nginx_sample ```

- 1. Criar o Dockerfile (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:
    vi Dockerfile

```bash
# Aqui estamos usando uma imagem base do Nginx (Servidor Web)
FROM nginx:latest

# Copie a página HTML sample que criamos, para o diretório padrão do Nginx
COPY index.html /usr/share/nginx/html
```
- Obs. Para consultar as versões (image tag) do Nginx (Imagens Default) segue o link: <https://hub.docker.com/_/nginx/tags>
- Exemplo Vulnerabilidade crítica: <https://hub.docker.com/_/nginx/tags?page=34>

---

## 3) Construindo (Docker Build) a imagem, com base no seu Dockerfile

No Path onde está o **DOCKERFILE** que criamos, execute esse comando:

```bash
docker build -t webserver-nginx-sample .
```

- Obs.: Caso não esteja no path /home/ubuntu/nginx_sample, você pode indicar o path no comando docker build (Aplicação prática - Scripts e Pipelines):

```bash
docker build -t webserver-nginx-sample -f /home/ubuntu/nginx_sample/Dockerfile /home/ubuntu/nginx_sample
```

- "Dissecando" o comando Docker Build:

```bash
docker build -t <image-tag:versão> -f <path/to/Dockerfile> <path/to/build/context>
ex.: docker build -t webserver-nginx-sample:1.0 -f /home/ubuntu/nginx_sample/Dockerfile /home/ubuntu/nginx_sample
```

  - **a. -t:** Define o nome da imagem, opcionalmente poder conter a tag da versão: ```<image-tag:version-tag>```
  - **b. -f:** Indica qual Dockerfile (Path completo) usar, podendo também adicionar o contexto (pasta do build): ```<path/to/Dockerfile> <path/to/build/context>```

---

## 4) Rodando o container (Docker Run)

Agora vamos rodar o container, com base na imagem nginx criada com o Dockerfile:

```bash
docker run --name nginx-sample -p 8080:80 -d webserver-nginx-sample
```

- "Dissecando" o comando Docker Run (Options):
  - **a. --name:** Define um nome fixo para o container (em vez do docker gerar um aleatório) uso prático (facilita na hora de entrar "exec" no container ou consultar logs)
  - **b. -p:** Famoso Port Mapping - Faz o mapeamento de portas entre host e container - <PORTA_HOST>:<PORTA_CONTAINER>;
  - **c. -d:** Roda em background (detached mode) - sem esse comando, o container roda "preso" no terminal, vendo os logs da criação + container na tela (terminal).

- Verificar logs depois de subir o container: ```docker logs -f nginx-sample```

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
docker rm -f nginx-sample

#Remover a imagem
docker rmi -f webserver-nginx-sample
  #Se sua imagem tiver tag de versão, tem que apontar no comando
  docker rmi -f webserver-nginx-sample:latest

#Comando para limpar recuros não utilizados pelo docker:
docker system prune -a --volumes -f

#Verificar se foi removido
docker ps -a
docker images | docker image ls
```


