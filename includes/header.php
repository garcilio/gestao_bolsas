<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Fallback caso BASE_URL não esteja definida no db.php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/gestao_bolsas/public');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Gestão de Bolsas e Apoios — UAc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --danger-bg: #fee2e2;
            --danger-text: #991b1b;
            --warning-bg: #fef3c7;
            --warning-text: #92400e;
            --info-bg: #e0f2fe;
            --info-text: #075985;
            --radius: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            width: 100%;
            min-height: 100vh;
            background-color: var(--bg);
            color: var(--text-main);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
        }

        header.site-header {
            width: 100%;
            background-color: #ffffff;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            display: block;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.9rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .brand-logo-badge {
            background-color: var(--primary);
            color: white;
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        nav.site-nav {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        nav.site-nav a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.45rem 0.8rem;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        nav.site-nav a:hover {
            color: var(--primary);
            background-color: #f1f5f9;
        }

        .btn-nav-primary {
            background-color: var(--primary) !important;
            color: #ffffff !important;
        }

        .btn-nav-primary:hover {
            background-color: var(--primary-hover) !important;
        }

        .btn-nav-logout {
            color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: block;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            width: 100%;
        }

        h2 { font-size: 1.35rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem; }
        h3 { font-size: 1.15rem; font-weight: 600; margin-bottom: 0.8rem; }
        p.text-subtitle { color: var(--text-muted); font-size: 0.92rem; margin-bottom: 1.25rem; }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: flex-end;
        }

        .form-group { display: flex; flex-direction: column; }

        label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.35rem;
        }

        input[type="text"], input[type="email"], input[type="password"],
        input[type="number"], input[type="date"], select, textarea {
            width: 100%;
            padding: 0.55rem 0.75rem;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--text-main);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 6px;
            outline: none;
            transition: border-color 0.15s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-group { display: flex; gap: 0.5rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 0.55rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-primary { background-color: var(--primary); color: #ffffff; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: #f1f5f9; color: #334155; border-color: var(--border); }
        .btn-secondary:hover { background-color: #e2e8f0; }
        .btn-danger { background-color: #ef4444; color: #ffffff; }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 1rem;
            font-size: 0.92rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #fcfcfd; }

        .badge {
            display: inline-flex;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-aberta, .badge-aprovada { background-color: var(--success-bg); color: var(--success-text); }
        .badge-expirada, .badge-rejeitada { background-color: var(--danger-bg); color: var(--danger-text); }
        .badge-submetida { background-color: var(--info-bg); color: var(--info-text); }
        .badge-em-analise { background-color: var(--warning-bg); color: var(--warning-text); }
        .badge-aguardar, .badge-aguardar-documentacao { background-color: #ffedd5; color: #9a3412; }

        .actions-cell { display: flex; gap: 0.5rem; align-items: center; }

        .alert {
            padding: 0.85rem 1.15rem;
            border-radius: 6px;
            margin-bottom: 1.35rem;
            font-size: 0.92rem;
            font-weight: 500;
        }
        .alert-error { background-color: var(--danger-bg); color: var(--danger-text); border: 1px solid #fecaca; }
        .alert-success { background-color: var(--success-bg); color: var(--success-text); border: 1px solid #bbf7d0; }

        .form-row { display: flex; gap: 1.25rem; }
        .form-col { flex: 1; }

        @media (max-width: 900px) {
            .filter-grid { grid-template-columns: 1fr; }
            .form-row { flex-direction: column; gap: 0; }
            .header-content { flex-direction: column; gap: 0.75rem; }
        }
    </style>
</head>
<body>
<header class="site-header">
    <div class="header-content">
        <a href="<?= BASE_URL ?>/index.php" class="brand-logo">
            <span class="brand-logo-badge">ESTA</span>
            <span>Gestão de Bolsas <span style="color: var(--primary);">UAc</span></span>
        </a>
        <nav class="site-nav">
            <a href="<?= BASE_URL ?>/index.php">Bolsas Abertas</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>/registo.php">Criar Conta</a>
                <a href="<?= BASE_URL ?>/login.php" class="btn-nav-primary">Entrar</a>
            <?php else: ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'candidato'): ?>
                    <a href="<?= BASE_URL ?>/candidato/dashboard.php">Candidaturas</a>
                    <a href="<?= BASE_URL ?>/candidato/perfil.php">Perfil</a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'tecnico'): ?>
                    <a href="<?= BASE_URL ?>/tecnico/dashboard.php">Painel Técnico</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/logout.php" class="btn-nav-logout">Sair (<?= sanitize($_SESSION['username'] ?? '') ?>)</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">