<?php

declare(strict_types=1);

use function App\config;

require_once __DIR__ . '/../src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'app' => app_name(),
        'time' => gmdate('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($path === '/') {
    $products = fetch_products();

    ob_start();
    ?>
    <section class="hero">
      <div class="hero-box">
        <h1 style="margin-top:0;"><?= h(app_name()) ?></h1>
        <p>Lab prático com EC2 Ubuntu 24.04, Docker, Compose, ECR, RDS MySQL, S3 e Nginx em container.</p>
        <div class="meta">
          <span>Domínio: <?= h(config('APP_URL', 'http://localhost')) ?></span>
          <span>RDS externo</span>
          <span>Imagens no S3</span>
        </div>
      </div>
    </section>

    <section>
      <h2>Produtos em destaque</h2>
      <div class="grid">
        <?php foreach ($products as $product): ?>
          <article class="card">
            <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>">
            <div class="card-body">
              <h3 style="margin-top:0;"><?= h($product['name']) ?></h3>
              <p><?= h($product['short_description']) ?></p>
              <p class="price"><?= currency_br((float)$product['price']) ?></p>
              <a class="btn" href="/produto?id=<?= (int)$product['id'] ?>">Ver produto</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
    render_page('Home', (string)ob_get_clean());
    exit;
}

if ($path === '/produto') {
    $id = (int)($_GET['id'] ?? 0);
    $product = $id > 0 ? fetch_product($id) : null;

    if (!$product) {
        http_response_code(404);
        render_page('Produto não encontrado', '<div class="panel"><h2>Produto não encontrado</h2><p>Verifique o ID informado.</p><a class="btn" href="/">Voltar</a></div>');
        exit;
    }

    ob_start();
    ?>
    <section class="layout-2">
      <div class="panel">
        <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>" style="width:100%; max-height:520px; object-fit:cover; border-radius:12px;">
      </div>
      <div class="panel">
        <h1 style="margin-top:0;"><?= h($product['name']) ?></h1>
        <p class="price"><?= currency_br((float)$product['price']) ?></p>
        <p><?= h($product['description']) ?></p>
        <div class="meta">
          <span>ID: <?= (int)$product['id'] ?></span>
          <span>Mídia servida do S3</span>
        </div>
        <p style="margin-top:20px;"><a class="btn" href="/login">Entrar</a> <a class="btn btn-outline" href="/cadastro">Criar conta</a></p>
      </div>
    </section>
    <?php
    render_page('Produto', (string)ob_get_clean());
    exit;
}

if ($path === '/cadastro') {
    if (is_post()) {
        $result = register_customer($_POST);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        redirect_to($result['ok'] ? '/login' : '/cadastro');
    }

    ob_start();
    ?>
    <section class="layout-2">
      <div class="panel">
        <h1 style="margin-top:0;">Cadastro de cliente</h1>
        <p>Os dados serão gravados no RDS MySQL externo à EC2.</p>
        <form method="post" action="/cadastro">
          <label>Nome</label>
          <input type="text" name="name" placeholder="Seu nome completo" required>
          <label>E-mail</label>
          <input type="email" name="email" placeholder="voce@empresa.com" required>
          <label>Senha</label>
          <input type="password" name="password" placeholder="********" required>
          <div style="margin-top:18px;"><button class="btn" type="submit">Cadastrar</button></div>
        </form>
      </div>
      <div class="panel">
        <h2 style="margin-top:0;">O que este fluxo demonstra</h2>
        <ul>
          <li>Conexão da aplicação em container com RDS MySQL</li>
          <li>Configuração via variáveis de ambiente</li>
          <li>Nginx fazendo proxy para PHP-FPM</li>
          <li>Deploy com imagem vinda do ECR</li>
        </ul>
      </div>
    </section>
    <?php
    render_page('Cadastro', (string)ob_get_clean());
    exit;
}

if ($path === '/login') {
    if (is_post()) {
        $result = login_customer($_POST);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        redirect_to($result['ok'] ? '/' : '/login');
    }

    ob_start();
    ?>
    <section class="layout-2">
      <div class="panel">
        <h1 style="margin-top:0;">Login do cliente</h1>
        <form method="post" action="/login">
          <label>E-mail</label>
          <input type="email" name="email" placeholder="voce@empresa.com" required>
          <label>Senha</label>
          <input type="password" name="password" placeholder="********" required>
          <div style="margin-top:18px;"><button class="btn" type="submit">Entrar</button></div>
        </form>
      </div>
      <div class="panel">
        <h2 style="margin-top:0;">Credenciais de teste</h2>
        <p>Crie um usuário pela tela de cadastro e teste o login.</p>
        <p><a class="btn btn-outline" href="/cadastro">Ir para cadastro</a></p>
      </div>
    </section>
    <?php
    render_page('Login', (string)ob_get_clean());
    exit;
}

if ($path === '/logout') {
    unset($_SESSION['user']);
    flash_set('success', 'Sessão encerrada com sucesso.');
    redirect_to('/');
}

http_response_code(404);
render_page('Página não encontrada', '<div class="panel"><h2>404</h2><p>A página solicitada não existe.</p><a class="btn" href="/">Voltar para Home</a></div>');
