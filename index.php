<?php
declare(strict_types=1);

require_once __DIR__ . '/seguranca.php';

iniciarSessaoSegura();
$csrfToken = obterTokenCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AcadFlow - Sistema de Gestão Acadêmica">
    <title>AcadFlow | Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="app-container">

        <!-- MENU LATERAL -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">A</div>
                    <div class="logo-text">
                        <strong>AcadFlow</strong>
                        <span>Gestão Acadêmica</span>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Navegação principal">
                <span class="nav-label">MENU PRINCIPAL</span>

                <a href="#dashboard"
                   class="nav-item active"
                   data-page="dashboard"
                   aria-current="page">
                    <span class="nav-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="9" rx="1"/>
                            <rect x="14" y="3" width="7" height="5" rx="1"/>
                            <rect x="14" y="12" width="7" height="9" rx="1"/>
                            <rect x="3" y="16" width="7" height="5" rx="1"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                <a href="#alunos"
                   class="nav-item"
                   data-page="alunos">
                    <span class="nav-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    <span>Alunos</span>
                </a>

                <a href="#cadastro"
                   class="nav-item"
                   data-page="cadastro">
                    <span class="nav-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M12 5v14"/>
                            <path d="M5 12h14"/>
                        </svg>
                    </span>
                    <span>Novo Cadastro</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer-content">
                    <strong>AcadFlow</strong>
                    <span>Sistema de Gestão Acadêmica</span>
                    <small>Versão 1.0</small>
                </div>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="main-content">

            <!-- CABEÇALHO -->
            <header class="topbar">
                <div class="topbar-info">
                    <p class="breadcrumb">Sistema Acadêmico / Visão Geral</p>
                    <h1>Painel de Controle</h1>
                </div>

                <div class="topbar-actions">
                    <button type="button"
                            id="theme-toggle"
                            class="theme-toggle"
                            aria-label="Alternar tema"
                            title="Alternar tema">
                        ☾
                    </button>
                </div>
            </header>

            <!-- DASHBOARD -->
            <section id="dashboard" class="page-section active">

                <div class="section-heading">
                    <div>
                        <h2>Visão geral</h2>
                        <p>Acompanhe os principais indicadores acadêmicos.</p>
                    </div>

                    <button type="button"
                            class="btn btn-primary"
                            data-navigate="cadastro">
                        + Novo aluno
                    </button>
                </div>

                <!-- INDICADORES -->
                <div class="stats-grid">

                    <article class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </span>
                            <span class="stat-label">Alunos cadastrados</span>
                        </div>

                        <div class="stat-card-content">
                            <h3 id="total-alunos">0</h3>
                            <p>Total de alunos registrados</p>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                    <path d="M8 7h8"/>
                                    <path d="M8 11h6"/>
                                </svg>
                            </span>
                            <span class="stat-label">Cursos disponíveis</span>
                        </div>

                        <div class="stat-card-content">
                            <h3 id="total-cursos">0</h3>
                            <p>Cursos com alunos cadastrados</p>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M22 10v6"/>
                                    <path d="M2 10l10-5 10 5-10 5-10-5z"/>
                                    <path d="M6 12v5c3.5 3 8.5 3 12 0v-5"/>
                                </svg>
                            </span>
                            <span class="stat-label">Matrículas registradas</span>
                        </div>

                        <div class="stat-card-content">
                            <h3 id="total-matriculas">0</h3>
                            <p>Registros acadêmicos ativos</p>
                        </div>
                    </article>

                </div>

                <!-- ALUNOS RECENTES -->
                <div class="content-card">
                    <div class="card-header">
                        <div>
                            <h3>Alunos recentes</h3>
                            <p>Últimos alunos cadastrados no sistema.</p>
                        </div>

                        <button type="button"
                                class="btn btn-secondary"
                                data-navigate="alunos">
                            Ver todos
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Matrícula</th>
                                    <th>Curso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="dashboard-alunos">
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        Carregando alunos...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            <!-- GERENCIAMENTO DE ALUNOS -->
            <section id="alunos" class="page-section">

                <div class="section-heading">
                    <div>
                        <h2>Alunos cadastrados</h2>
                        <p>Consulte e gerencie os registros acadêmicos.</p>
                    </div>

                    <button type="button"
                            class="btn btn-primary"
                            data-navigate="cadastro">
                        + Novo aluno
                    </button>
                </div>

                <div class="content-card">

                    <div class="table-toolbar">
                        <div class="search-box">
                            <label for="search-alunos" class="sr-only">
                                Pesquisar alunos
                            </label>
                            <input type="search"
                                   id="search-alunos"
                                   placeholder="Pesquisar por nome ou matrícula..."
                                   autocomplete="off">
                        </div>

                        <div class="filter-box">
                            <label for="filter-curso" class="sr-only">
                                Filtrar por curso
                            </label>
                            <select id="filter-curso">
                                <option value="">Todos os cursos</option>
                                <option value="ADS">
                                    Análise e Desenvolvimento de Sistemas
                                </option>
                                <option value="SI">
                                    Sistemas de Informação
                                </option>
                                <option value="CC">
                                    Ciência da Computação
                                </option>
                                <option value="ES">
                                    Engenharia de Software
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Matrícula</th>
                                    <th>Curso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-alunos">
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        Carregando alunos...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </section>

            <!-- CADASTRO E EDIÇÃO -->
            <section id="cadastro" class="page-section">

                <div class="section-heading">
                    <div>
                        <h2>Cadastro de aluno</h2>
                        <p>Preencha os dados para cadastrar ou atualizar um aluno.</p>
                    </div>
                </div>

                <div class="content-card">

                    <form id="form-aluno"
                          action="processar.php"
                          method="POST">

                        <!-- ID UTILIZADO NA EDIÇÃO -->
                        <input type="hidden"
                               id="aluno-id"
                               name="id"
                               value="">

                        <!-- PROTEÇÃO CSRF -->
                        <input type="hidden"
                               id="csrf-token"
                               name="csrf_token"
                               value="<?= htmlspecialchars(
                                   $csrfToken,
                                   ENT_QUOTES,
                                   'UTF-8'
                               ) ?>">

                        <div class="form-group">
                            <label for="nome">
                                Nome completo
                                <span class="required">*</span>
                            </label>

                            <input type="text"
                                   id="nome"
                                   name="nome"
                                   placeholder="Digite o nome completo"
                                   maxlength="120"
                                   autocomplete="name"
                                   required>

                            <small class="form-hint">
                                Informe o nome completo do aluno.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="matricula">
                                Matrícula
                                <span class="required">*</span>
                            </label>

                            <input type="text"
                                   id="matricula"
                                   name="matricula"
                                   placeholder="Ex.: 20260001"
                                   inputmode="numeric"
                                   pattern="[0-9]+"
                                   maxlength="20"
                                   required>

                            <small class="form-hint">
                                Utilize apenas números.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="curso">
                                Curso
                                <span class="required">*</span>
                            </label>

                            <select id="curso"
                                    name="curso"
                                    required>
                                <option value="">Selecione um curso</option>
                                <option value="ADS">
                                    Análise e Desenvolvimento de Sistemas
                                </option>
                                <option value="SI">
                                    Sistemas de Informação
                                </option>
                                <option value="CC">
                                    Ciência da Computação
                                </option>
                                <option value="ES">
                                    Engenharia de Software
                                </option>
                            </select>

                            <small class="form-hint">
                                Selecione o curso do aluno.
                            </small>
                        </div>

                        <div class="form-actions">
                            <button type="button"
                                    class="btn btn-secondary"
                                    data-navigate="alunos">
                                Cancelar
                            </button>

                            <button type="submit"
                                    class="btn btn-primary">
                                Salvar aluno
                            </button>
                        </div>

                    </form>

                </div>
            </section>

        </main>
    </div>

    <!-- NOTIFICAÇÕES -->
    <div class="toast-container"
         role="status"
         aria-live="polite"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/js/script.js"></script>

</body>
</html>