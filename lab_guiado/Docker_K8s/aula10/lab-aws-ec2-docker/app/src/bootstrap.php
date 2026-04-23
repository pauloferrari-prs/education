<?php

declare(strict_types=1);

use App\Database;

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(getenv('APP_SESSION_NAME') ?: 'LABPHPSESSID');
    session_start();
}

function config(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function app_name(): string
{
    return config('APP_NAME', 'SkyNalytix Lab PHP');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect_to(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function db(): PDO
{
    return Database::connection();
}

function fetch_products(): array
{
    $stmt = db()->query('SELECT id, name, short_description, description, price, image_url FROM products ORDER BY id ASC LIMIT 3');
    return $stmt->fetchAll();
}

function fetch_product(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, name, short_description, description, price, image_url FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();

    return $product ?: null;
}

function register_customer(array $data): array
{
    $name = trim($data['name'] ?? '');
    $email = trim(strtolower($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        return ['ok' => false, 'message' => 'Preencha nome, e-mail e senha.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Informe um e-mail válido.'];
    }

    $check = db()->prepare('SELECT id FROM customers WHERE email = :email');
    $check->execute(['email' => $email]);
    if ($check->fetch()) {
        return ['ok' => false, 'message' => 'Este e-mail já está cadastrado.'];
    }

    $stmt = db()->prepare('INSERT INTO customers (name, email, password_hash) VALUES (:name, :email, :password_hash)');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return ['ok' => true, 'message' => 'Cadastro realizado com sucesso. Faça o login.'];
}

function login_customer(array $data): array
{
    $email = trim(strtolower($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');

    if ($email === '' || $password === '') {
        return ['ok' => false, 'message' => 'Informe e-mail e senha.'];
    }

    $stmt = db()->prepare('SELECT id, name, email, password_hash FROM customers WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'message' => 'Usuário ou senha inválidos.'];
    }

    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    return ['ok' => true, 'message' => 'Login realizado com sucesso.'];
}

function currency_br(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function render_page(string $title, string $content): void
{
    $flash = flash_get();
    $user = current_user();
    $baseTitle = h(app_name());
    $pageTitle = h($title);

    echo '<!doctype html>';
    echo '<html lang="pt-BR">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $pageTitle . ' | ' . $baseTitle . '</title>';
    echo '<style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, Helvetica, sans-serif; background:#f5f7fb; color:#1f2937; }
        .container { width:min(1120px, 92vw); margin:0 auto; }
        header { background:#111827; color:#fff; padding:18px 0; }
        nav { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
        nav a { color:#fff; text-decoration:none; margin-right:14px; font-weight:600; }
        nav a:last-child { margin-right:0; }
        .hero { padding:36px 0 18px; }
        .hero-box { background:linear-gradient(135deg, #1d4ed8, #0f172a); color:#fff; border-radius:18px; padding:28px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px; margin:24px 0 40px; }
        .card { background:#fff; border-radius:16px; box-shadow:0 12px 28px rgba(15,23,42,.08); overflow:hidden; }
        .card img { width:100%; height:240px; object-fit:cover; display:block; background:#e5e7eb; }
        .card-body { padding:18px; }
        .price { color:#1d4ed8; font-size:1.15rem; font-weight:700; }
        .btn { display:inline-block; background:#1d4ed8; color:#fff; text-decoration:none; padding:10px 14px; border-radius:10px; font-weight:700; border:none; cursor:pointer; }
        .btn-outline { background:#fff; color:#1d4ed8; border:1px solid #1d4ed8; }
        .layout-2 { display:grid; grid-template-columns:1.2fr .8fr; gap:24px; margin:28px 0 40px; }
        .panel { background:#fff; border-radius:16px; box-shadow:0 12px 28px rgba(15,23,42,.08); padding:24px; }
        label { display:block; margin:12px 0 6px; font-weight:700; }
        input { width:100%; padding:12px 14px; border:1px solid #cbd5e1; border-radius:10px; font-size:1rem; }
        .flash { margin:20px 0 0; padding:14px 16px; border-radius:12px; font-weight:700; }
        .flash-success { background:#dcfce7; color:#166534; }
        .flash-error { background:#fee2e2; color:#991b1b; }
        .meta { display:flex; gap:12px; flex-wrap:wrap; color:#6b7280; font-size:.95rem; }
        footer { color:#6b7280; padding:26px 0 40px; }
        @media (max-width: 900px) { .layout-2 { grid-template-columns:1fr; } }
    </style>';
    echo '</head>';
    echo '<body>';
    echo '<header><div class="container"><nav>';
    echo '<div><a href="/">' . $baseTitle . '</a></div>';
    echo '<div>';
    echo '<a href="/">Home</a>';
    echo '<a href="/cadastro">Cadastro</a>';
    if ($user) {
        echo '<span style="margin-right:14px; color:#cbd5e1;">Olá, ' . h($user['name']) . '</span>';
        echo '<a href="/logout">Sair</a>';
    } else {
        echo '<a href="/login">Login</a>';
    }
    echo '</div>';
    echo '</nav></div></header>';
    echo '<main class="container">';

    if ($flash) {
        $class = ($flash['type'] ?? 'success') === 'error' ? 'flash-error' : 'flash-success';
        echo '<div class="flash ' . $class . '">' . h($flash['message'] ?? '') . '</div>';
    }

    echo $content;
    echo '</main>';
    echo '<footer><div class="container">Lab AWS + EC2 + Docker + ECR + RDS + S3 + Nginx</div></footer>';
    echo '</body></html>';
}
