# Aula 4 — Hands On - Dockerfile Boas Práticas

Este guia traz o **passo a passo completo** para montar uma imagem docker utilizando principios inicias de boas práticas (não tem step multi stage) e rodar a imagem em um container, usando os comandos mencionados na Aula 4

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit + Docker Instalado
- Acesso `sudo`
- Internet para baixar pacotes/imanges do repositório oficial da Docker / Hub
- Se quiser usar em VM, baixar essa VM (VirtualBox) ubuntu 24.04 com o docker instalado: <https://repo-aws-pferrari.s3.us-east-1.amazonaws.com/ubuntulab.ova>

---

## 1) Criar pasta raiz do "projeto/lab" do NGINX rodando no Docker

No seu host/vm, criar uma pasta onde irá adicionar os arquivos necessários para montar a imagem:

- 1. mkdir -p ``` /home/ubuntu/python_lab_aula04_02 ```
- 2. Estrutura da pasta:

```text
python_lab_aula04_02/
├── app/
│   ├── __init__.py
│   └── __main__.py
├── requirements.txt
├── Dockerfile
└── .dockerignore
```

- 3. Criar arquivo **app/__init__.py** com esse código python:

```python
# (vazio de propósito) marca o diretório como um pacote Python
```

- 4. Criar um arquivo **app/__main__.py** com esse código python:

```python
import click

@click.group(context_settings={"help_option_names": ["-h", "--help"]})
def cli():
    """Calculadora simples (add/sub)."""
    pass

@cli.command()
@click.argument("a", type=float)
@click.argument("b", type=float)
def add(a: float, b: float):
    """Soma: A + B"""
    click.echo(a + b)

@cli.command()
@click.argument("a", type=float)
@click.argument("b", type=float)
def sub(a: float, b: float):
    """Subtração: A - B"""
    click.echo(a - b)

if __name__ == "__main__":
    cli()
```

- 5. Criar arquivo **requirements.txt** com essa lib python:

```bash
click==8.1.7
```

- 6. Criar arquivo **.dockerignore** com esses path's / arquivos adicionados:

```bash
# VCS / IDE
.git
.idea
.vscode

# Python
__pycache__/
*.pyc
*.pyo
*.pyd

# Ambiente/segredos
.env
*.log

# Builds/venv
venv/
dist/
build/

# OS
.DS_Store
```



---

## 2) Criando a imagem (Dockerfile) da aplicação python (calc simples)

Nesse caso essa imagem do python é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/python_lab_aula04_02 ```

- 1. Criar o Dockerfile (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
# Base Image: Mínima + versão fixa (evita "latest")
FROM python:3.12.2-slim-bookworm

# LABEL/ENV (metadados + variáveis padrão)
LABEL org.opencontainers.image.title="python-calc-lab" \
      org.opencontainers.image.version="1.0.0" \
      org.opencontainers.image.description="Calculadora simples (adicao/subtracao) em Python com boas práticas de Dockerfile" \
      org.opencontainers.image.created="2026-03-05" \
      org.opencontainers.image.authors="Paulo Ferrari"

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    APP_ENV=prod \
    APP_DATA_DIR=/var/lib/app

# ARGs para usuário non-root
ARG APP_USER=appuser
ARG APP_UID=10001
ARG APP_GID=10001

# - Minimizar camadas e lixo:
# - Instala apenas o necessário
# - Limpa cache do apt na mesma layer
# - Non-root: cria usuário/grupo e ajusta permissões em caminhos previsíveis
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates; \
    rm -rf /var/lib/apt/lists/*; \
    groupadd -g "${APP_GID}" "${APP_USER}"; \
    useradd  -m -u "${APP_UID}" -g "${APP_GID}" -s /usr/sbin/nologin "${APP_USER}"; \
    mkdir -p /app /etc/app /var/lib/app; \
    chown -R "${APP_UID}:${APP_GID}" /app /etc/app /var/lib/app

# Paths previsíveis
WORKDIR /app

# Copia o manifesto primeiro (requirements.txt) depois instala as deps,
# depois copia o código (mudança no código NÃO invalida deps)
COPY requirements.txt requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# Cópia explícita do código da aplicação (evita vazar arquivos indevidos)
COPY --chown=${APP_UID}:${APP_GID} calc/ ./calc/

# Executar como non-root
USER ${APP_USER}

# Runtime saudável (exec form):
# ENTRYPOINT define o processo principal (PID 1)
ENTRYPOINT ["python", "-m", "calc"]

# Default (pode sobrescrever no docker run)
CMD ["--help"]
```

---

## 3) Construindo (Docker Build) a imagem, com base no seu Dockerfile

No Path onde está o **DOCKERFILE** que criamos, execute esse comando:

```bash
docker build -t python-calc:1.0 .
```

---

## 4) Rodando o container (Docker Run)

Agora vamos rodar o container, com base na imagem da aplicação python criada com o Dockerfile:

- 1. Roda o comando que está no CMD (--help):

```bash
docker run --rm python-calc:1.0
```

- 2. Roda o comando passando o comando da soma:

```bash
docker run --rm python-calc:1.0 add 10 5
```

- 3. Roda o comando passando o comando da subtração:

```bash
docker run --rm python-calc:1.0 sub 10 5
```

- Obs.: Aqui estou usando com a opção ```--rm```, pois é um lab, então assim que executarmos o comando o container será removido.

## 5) Rodando o container (Docker Run) de forma em que podemos "debugar" entrar no container (persistir a criação)


- 1. Roda o comando que está no CMD (--help):

```bash
docker run -d --name calc-debug --entrypoint /bin/sh python-calc:1.0 -c "sleep infinity"
```

- 2. Assim conseguimos entrar dentro do container:

```bash
docker exec -it calc-debug sh

#Com isso podemos executar alguns comandos dentro do container para validar e testar a aplicação:
python -m calc --help
python -m calc add 20 18
python -m calc sub 20 18
```

## 6) Dando um "Destroy" no LAB

Sequência de comando para apagar o lab criado no seu host/vm:

```bash
#Remover o container, com a opção -f (forçado, se o container estiver rodando)
docker rm -f calc-debug

#Remover a imagem
docker rmi -f python-calc:1.0

#Comando para limpar recuros não utilizados pelo docker:
docker system prune -a --volumes -f

#Verificar se foi removido
docker ps -a
docker images | docker image ls
```