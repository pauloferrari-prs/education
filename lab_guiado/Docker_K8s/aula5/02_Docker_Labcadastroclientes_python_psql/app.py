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