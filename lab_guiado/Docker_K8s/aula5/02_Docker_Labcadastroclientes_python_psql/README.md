# Aula 5 — Hands On - Cadastro de Clientes com Python Flask + PostgreSQL

Este guia traz o **passo a passo completo** para construir uma aplicação simples de cadastro de cliente em Python Flask, validar primeiro localmente, depois empacotar em uma imagem Docker e executar a aplicação conectando em um banco de dados PostgreSQL também em container.

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit + Docker Instalado
- Acesso `sudo`
- Internet para baixar pacotes/imanges do repositório oficial da Docker / Hub
- Se quiser usar em VM, baixar essa VM (VirtualBox) ubuntu 24.04 com o docker instalado: <https://repo-aws-pferrari.s3.us-east-1.amazonaws.com/ubuntulab.ova>

---

## 1) Criar pasta raiz do "projeto/lab" e seus arquivos (Código Fonte Aplicação e configs)

No seu host/vm, criar uma pasta onde irá adicionar os arquivos necessários para montar a imagem:

- 1. ```mkdir -p ``` /home/ubuntu/lab_aula5_cadastroclientes ```
```bash
mkdir -p /home/ubuntu/lab_aula5_cadastroclientes
cd /home/ubuntu/lab_aula5_cadastroclientes
mkdir -p phyton db
```

- 2. Estrutura da pasta:

```text
lab_aula5_cadastroclientes/
├── .env
├── .dockerignore
├── app.py
├── requirements.txt
├── db/
│   ├── Dockerfile
│   └── schema.sql
└── python/
    └── Dockerfile
```

- 3. Criar arquivo **/.env** :

```text
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=clientes_db
DB_USER=clientes
DB_PASSWORD=clientes123
```

- 4. Criar arquivo **/.dockerignore** com esses path's / arquivos adicionados:

```text
.env
.git
.gitignore
README.md
__pycache__/
*.pyc
.venv/
venv/
*.pyo
*.pyd
.pytest_cache/
```

- 5. Criar arquivo **db/schema.sql**:

```SQL
CREATE TABLE IF NOT EXISTS customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

- 6. Criar arquivo **/app.py**:

```python
import os
from flask import Flask, request, redirect, render_template_string
import psycopg2


def load_env(path=".env"):
    if not os.path.exists(path):
        return

    with open(path, "r") as f:
        for line in f:
            line = line.strip()

            if not line or line.startswith("#") or "=" not in line:
                continue

            key, value = line.split("=", 1)
            os.environ.setdefault(key.strip(), value.strip())


load_env()

app = Flask(__name__)

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = os.getenv("DB_PORT", "5432")
DB_NAME = os.getenv("DB_NAME", "clientes_db")
DB_USER = os.getenv("DB_USER", "clientes")
DB_PASSWORD = os.getenv("DB_PASSWORD", "clientes123")


def get_connection():
    return psycopg2.connect(
        host=DB_HOST,
        port=DB_PORT,
        dbname=DB_NAME,
        user=DB_USER,
        password=DB_PASSWORD
    )


HTML = """
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Clientes</title>
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
    <h1>Cadastro de Clientes</h1>

    <form method="POST" action="/add">
        <label>Nome</label>
        <input type="text" name="name" required>

        <label>E-mail</label>
        <input type="email" name="email" required>

        <label>Telefone</label>
        <input type="text" name="phone" required>

        <button type="submit">Salvar cliente</button>
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
            {% if customers %}
                {% for customer in customers %}
                    <tr>
                        <td>{{ customer[0] }}</td>
                        <td>{{ customer[1] }}</td>
                        <td>{{ customer[2] }}</td>
                        <td>{{ customer[3] }}</td>
                        <td>{{ customer[4] }}</td>
                        <td><a class="delete" href="/delete/{{ customer[0] }}">Excluir</a></td>
                    </tr>
                {% endfor %}
            {% else %}
                <tr>
                    <td colspan="6">Nenhum cliente cadastrado.</td>
                </tr>
            {% endif %}
        </tbody>
    </table>
</body>
</html>
"""


@app.route("/")
def index():
    conn = get_connection()
    cur = conn.cursor()
    cur.execute("SELECT id, name, email, phone, created_at FROM customers ORDER BY id DESC")
    customers = cur.fetchall()
    cur.close()
    conn.close()
    return render_template_string(HTML, customers=customers)


@app.route("/add", methods=["POST"])
def add_customer():
    name = request.form["name"]
    email = request.form["email"]
    phone = request.form["phone"]

    conn = get_connection()
    cur = conn.cursor()
    cur.execute(
        "INSERT INTO customers (name, email, phone) VALUES (%s, %s, %s)",
        (name, email, phone)
    )
    conn.commit()
    cur.close()
    conn.close()

    return redirect("/")


@app.route("/delete/<int:customer_id>")
def delete_customer(customer_id):
    conn = get_connection()
    cur = conn.cursor()
    cur.execute("DELETE FROM customers WHERE id = %s", (customer_id,))
    conn.commit()
    cur.close()
    conn.close()

    return redirect("/")


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
```

- 9. Criar arquivo **requirements.txt**:

```bash
Flask==3.0.2
psycopg2-binary==2.9.9
```
---

## 2) Preparando o ambiente Docker:

No Path do seu **CONTEXT** para build da aplicação (``` /home/ubuntu/lab_aula5_cadastroclientes/ ```), execute esses 2 comandos:

- 1. Criar rede Docker: Essa rede permitirá a comunicação entre os containers.
```bash
docker network create clientes-net
```

- 2. Criar o volume docker do PSQL: Para persistir os dados.
```bash
docker volume create clientes-pg-data
```

---

## 3) Criando a imagem (Dockerfile) do banco de dados PSQL

Nesse caso essa imagem do banco PSQL é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/lab_aula5_cadastroclientes/db ```

- 1. Criar o Dockerfile (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
# Base Image: PSQL
FROM postgres:16

# Tudo que estiver em /docker-entrypoint-initdb.d/ será executado na inicialização do PSQL somente na primeira vez.
COPY schema.sql /docker-entrypoint-initdb.d/01-schema.sql
```

---

## 4) Construindo (Docker Build) a imagem do banco de dados PSQL, com base no Dockerfile

No Path do seu **CONTEXT** principal do projeto (``` /home/ubuntu/lab_aula5_cadastroclientes/ ```), execute esse comando:

```bash
docker build -t clientes-postgres:1.0 ./db
```

---

## 5) Rodando o container (Docker Run) do banco PSQL


Agora vamos rodar o container, com base na imagem da aplicação do banco criada com o Dockerfile:

- 1. Comando Docker run
```bash
docker run -d \
  --name postgres-clientes \
  --network clientes-net \
  -p 5432:5432 \
  -e POSTGRES_DB=clientes_db \
  -e POSTGRES_USER=clientes \
  -e POSTGRES_PASSWORD=clientes123 \
  -v clientes-pg-data:/var/lib/postgresql/data \
  clientes-postgres:1.0
```

- 2. Verificar se o banco subiu corretamente

```bash
docker logs -f postgres-clientes
```
```bash
docker exec -it postgres-clientes psql -U clientes -d clientes_db -c "SELECT * FROM customers;"
```

---

## 6) Teste local da aplicação Python

Aqui a ideia é mostrar primeiro a aplicação funcionando fora do container, mas conectando no PSQL que já está em container.

- 1. Instalar Python localmente no host (UBUNTU)

```bash
sudo apt update
sudo apt install -y python3 python3-pip python3-venv python3-full
```

- 2. Rodar a aplicação localmente, porém utilizando um virtual environment  (venv)``` /home/ubuntu/lab_aula5_cadastroclientes/ ```

```bash
python3 -m venv .venv
```
```bash
source .venv/bin/activate
```
```bash
pip install -r requirements.txt
```
```bash
python3 app.py
```

- 3. Abrir no navegador:

```bash
http://localhost:5000
```

- 4. Validar os registros no banco, após cadastrar alguns contatos no sistema:

```bash
docker exec -it postgres-clientes psql -U clientes -d clientes_db -c "SELECT * FROM customers;"
```

---

## 7) Criando a imagem (Dockerfile) do Python (Aplicação)

Nesse caso essa imagem da aplicação (Código fonte), Python é apenas para fins de prática (exemplo / teste) não deve ser aplicado de forma alguma em PRODUÇÃO!

Na pasta que você criou: ``` /home/ubuntu/lab_aula5_cadastroclientes/python ```

- 1. Criar o Dockerfile Python (Arquivo para automatizar a criação de uma imagem Docker personalizada), com o conteúdo a seguir:

```Dockerfile
#Imagem Base Python
FROM python:3.12-slim

WORKDIR /app

#Copiando dependências (libs) que serão instaladas
COPY requirements.txt /app/requirements.txt

#Instalando as libs (dependências)
RUN pip install --no-cache-dir -r requirements.txt

#Cópia do código fonrte da aplicação
COPY app.py /app/app.py

EXPOSE 5000

#Criando o container com o comando para executar a aplicação
CMD ["python", "app.py"]
```

---

## 8) Construindo (Docker Build) a imagem da aplicação (python), com base no Dockerfile

No Path do seu **CONTEXT** principal do projeto (``` /home/ubuntu/lab_aula5_cadastroclientes/ ```), execute esse comando:

- Python
```bash
docker build -t clientes-flask:1.0 -f python/Dockerfile .
```

---

## 9) Rodando o container (Docker Run) da aplicação


Agora vamos rodar o container, com base na imagem da aplicação (python) criada com o Dockerfile:

- 1. Comando Docker run para o python (Aplicação)
```bash
docker run -d \
  --name flask-clientes \
  --network clientes-net \
  -p 5000:5000 \
  -e DB_HOST=postgres-clientes \
  -e DB_PORT=5432 \
  -e DB_NAME=clientes_db \
  -e DB_USER=clientes \
  -e DB_PASSWORD=clientes123 \
  clientes-flask:1.0
```

- 2. Verificar se a aplicação subiu corretamente

```bash
docker ps
```
```bash
docker logs flask-clientes
```
```bash
docker logs postgres-clientes
```
```bash
docker stats
```

- 3. Abrir no navegador
```bash
http://localhost:5000
```

- 4. Validar os registros no banco, após cadastrar alguns contatos no sistema:

```bash
docker exec -it postgres-clientes psql -U clientes -d clientes_db -c "SELECT * FROM customers;"
```

---

## 10) Dando um "Destroy" no LAB

Sequência de comando para apagar o lab criado no seu host/vm:

```bash
#Remover o container, com a opção -f (forçado, se o container estiver rodando)
docker rm -f flask-clientes postgres-clientes

#Remover o Volume Criado
docker volume rm clientes-pg-data

#Remover o docker networks criado
docker network rm clientes-net

#Comando para limpar recuros não utilizados pelo docker:
docker system prune -a --volumes -f

#Verificar se foi removido
docker ps -a
docker images | docker image ls
```
