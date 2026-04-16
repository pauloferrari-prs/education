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