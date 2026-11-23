"use strict";

/* ==========================================================
   ACADFLOW — CONTROLE DA INTERFACE
   Versão 2.0

   Funcionalidades:
   - Navegação entre páginas
   - Dashboard e indicadores
   - Listagem de alunos
   - Pesquisa e filtros
   - Cadastro e edição
   - Exclusão de alunos
   - Proteção CSRF na exclusão
   - Tema claro/escuro
   - Notificações
========================================================== */

const AcadFlow = {
  /* ======================================================
       1. ESTADO DA APLICAÇÃO
    ====================================================== */

  alunos: [],

  paginaAtual: "dashboard",

  temaAtual: "light",

  /* ======================================================
       2. INICIALIZAÇÃO
    ====================================================== */

  iniciar() {
    this.configurarTema();

    this.configurarNavegacao();

    this.configurarFormulario();

    this.configurarPesquisa();

    this.configurarFiltro();

    this.exibirMensagemURL();

    this.carregarAlunos();

    this.navegarPara(window.location.hash.replace("#", "") || "dashboard");
  },

  /* ======================================================
       3. NAVEGAÇÃO
    ====================================================== */

  configurarNavegacao() {
    document.querySelectorAll("[data-page]").forEach((link) => {
      link.addEventListener("click", (evento) => {
        evento.preventDefault();

        const pagina = link.dataset.page;

        if (pagina === "cadastro") {
          this.limparFormulario();
        }

        this.navegarPara(pagina);
      });
    });

    document.querySelectorAll("[data-navigate]").forEach((botao) => {
      botao.addEventListener("click", () => {
        const pagina = botao.dataset.navigate;

        if (pagina === "cadastro") {
          this.limparFormulario();
        }

        this.navegarPara(pagina);
      });
    });

    window.addEventListener("hashchange", () => {
      const pagina = window.location.hash.replace("#", "");

      this.navegarPara(pagina || "dashboard");
    });
  },

  navegarPara(pagina) {
    const paginasPermitidas = ["dashboard", "alunos", "cadastro"];

    if (!paginasPermitidas.includes(pagina)) {
      pagina = "dashboard";
    }

    this.paginaAtual = pagina;

    document.querySelectorAll(".page-section").forEach((secao) => {
      secao.classList.toggle("active", secao.id === pagina);
    });

    document.querySelectorAll(".nav-item").forEach((item) => {
      const ativo = item.dataset.page === pagina;

      item.classList.toggle("active", ativo);

      if (ativo) {
        item.setAttribute("aria-current", "page");
      } else {
        item.removeAttribute("aria-current");
      }
    });

    const titulo = document.querySelector(".topbar h1");

    const breadcrumb = document.querySelector(".breadcrumb");

    const titulos = {
      dashboard: "Painel de Controle",
      alunos: "Gerenciamento de Alunos",
      cadastro: "Cadastro de Aluno",
    };

    const breadcrumbs = {
      dashboard: "Sistema Acadêmico / Visão Geral",
      alunos: "Sistema Acadêmico / Alunos",
      cadastro: "Sistema Acadêmico / Cadastro",
    };

    if (titulo) {
      titulo.textContent = titulos[pagina];
    }

    if (breadcrumb) {
      breadcrumb.textContent = breadcrumbs[pagina];
    }

    if (window.location.hash !== `#${pagina}`) {
      history.replaceState(
        null,
        "",
        `${window.location.pathname}${window.location.search}#${pagina}`,
      );
    }
  },

  /* ======================================================
       4. TEMA CLARO / ESCURO
    ====================================================== */

  configurarTema() {
    const botao = document.getElementById("theme-toggle");

    const temaSalvo = localStorage.getItem("acadflow-theme");

    this.temaAtual = temaSalvo === "dark" ? "dark" : "light";

    this.aplicarTema(this.temaAtual);

    if (!botao) {
      return;
    }

    botao.addEventListener("click", () => {
      const novoTema = this.temaAtual === "light" ? "dark" : "light";

      this.aplicarTema(novoTema);

      localStorage.setItem("acadflow-theme", novoTema);
    });
  },

  aplicarTema(tema) {
    this.temaAtual = tema;

    document.documentElement.setAttribute("data-theme", tema);

    const botao = document.getElementById("theme-toggle");

    if (botao) {
      botao.textContent = tema === "dark" ? "☀" : "☾";

      botao.setAttribute(
        "aria-label",
        tema === "dark" ? "Ativar tema claro" : "Ativar tema escuro",
      );
    }
  },

  /* ======================================================
       5. CARREGAR ALUNOS
    ====================================================== */

  async carregarAlunos() {
    try {
      const resposta = await fetch("listar.php", {
        method: "GET",
        cache: "no-store",
        headers: {
          Accept: "application/json",
        },
      });

      if (!resposta.ok) {
        throw new Error("Não foi possível consultar os alunos.");
      }

      const resultado = await resposta.json();

      if (!resultado.sucesso) {
        throw new Error(resultado.mensagem || "Erro ao carregar alunos.");
      }

      this.alunos = Array.isArray(resultado.dados) ? resultado.dados : [];

      this.atualizarDashboard();

      this.renderizarAlunos();
    } catch (erro) {
      console.error("[AcadFlow] Erro ao carregar alunos:", erro);

      this.alunos = [];

      this.atualizarDashboard();

      this.renderizarAlunos();

      this.notificar("Não foi possível carregar os alunos.", "error");
    }
  },

  /* ======================================================
       6. INDICADORES DO DASHBOARD
    ====================================================== */

  atualizarDashboard() {
    const totalAlunos = this.alunos.length;

    const cursos = new Set(
      this.alunos.map((aluno) => aluno.curso).filter(Boolean),
    );

    const totalCursos = cursos.size;

    const totalMatriculas = this.alunos.filter((aluno) =>
      Boolean(aluno.matricula),
    ).length;

    const indicadorAlunos = document.getElementById("total-alunos");

    const indicadorCursos = document.getElementById("total-cursos");

    const indicadorMatriculas = document.getElementById("total-matriculas");

    if (indicadorAlunos) {
      indicadorAlunos.textContent = totalAlunos;
    }

    if (indicadorCursos) {
      indicadorCursos.textContent = totalCursos;
    }

    if (indicadorMatriculas) {
      indicadorMatriculas.textContent = totalMatriculas;
    }
  },

  /* ======================================================
       7. NOMES DOS CURSOS
    ====================================================== */

  obterNomeCurso(codigo) {
    const cursos = {
      ADS: "Análise e Desenvolvimento de Sistemas",
      SI: "Sistemas de Informação",
      CC: "Ciência da Computação",
      ES: "Engenharia de Software",
    };

    return cursos[codigo] || codigo || "Não informado";
  },

  /* ======================================================
       8. ESCAPAR TEXTO HTML
    ====================================================== */

  escaparHTML(valor) {
    return String(valor ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  },

  /* ======================================================
       9. RENDERIZAR TABELAS
    ====================================================== */

  renderizarAlunos() {
    const tabelaDashboard = document.getElementById("dashboard-alunos");

    const tabelaAlunos = document.getElementById("lista-alunos");

    if (tabelaDashboard) {
      const recentes = [...this.alunos]
        .sort((a, b) => {
          const dataA = a.criado_em || "";

          const dataB = b.criado_em || "";

          return dataB.localeCompare(dataA);
        })
        .slice(0, 5);

      this.preencherTabela(tabelaDashboard, recentes);
    }

    if (tabelaAlunos) {
      const alunosFiltrados = this.filtrarAlunos();

      this.preencherTabela(tabelaAlunos, alunosFiltrados);
    }
  },

  preencherTabela(tabela, alunos) {
    tabela.innerHTML = "";

    if (!alunos.length) {
      const linha = document.createElement("tr");

      const celula = document.createElement("td");

      celula.colSpan = 4;

      celula.className = "empty-state";

      celula.textContent = "Nenhum aluno encontrado.";

      linha.appendChild(celula);

      tabela.appendChild(linha);

      return;
    }

    alunos.forEach((aluno) => {
      const linha = document.createElement("tr");

      const nome = document.createElement("td");

      nome.textContent = aluno.nome || "";

      const matricula = document.createElement("td");

      matricula.textContent = aluno.matricula || "";

      const curso = document.createElement("td");

      curso.textContent = this.obterNomeCurso(aluno.curso);

      const acoes = document.createElement("td");

      const grupoAcoes = document.createElement("div");

      grupoAcoes.className = "action-buttons";

      const botaoEditar = document.createElement("button");

      botaoEditar.type = "button";

      botaoEditar.className = "action-btn edit";

      botaoEditar.textContent = "Editar";

      botaoEditar.addEventListener("click", () => {
        this.editarAluno(aluno);
      });

      const botaoExcluir = document.createElement("button");

      botaoExcluir.type = "button";

      botaoExcluir.className = "action-btn delete";

      botaoExcluir.textContent = "Excluir";

      botaoExcluir.addEventListener("click", () => {
        this.solicitarExclusao(aluno);
      });

      grupoAcoes.append(botaoEditar, botaoExcluir);

      acoes.appendChild(grupoAcoes);

      linha.append(nome, matricula, curso, acoes);

      tabela.appendChild(linha);
    });
  },

  /* ======================================================
       10. PESQUISA
    ====================================================== */

  configurarPesquisa() {
    const campo = document.getElementById("search-alunos");

    if (!campo) {
      return;
    }

    campo.addEventListener("input", () => {
      this.renderizarAlunos();
    });
  },

  /* ======================================================
       11. FILTRO POR CURSO
    ====================================================== */

  configurarFiltro() {
    const campo = document.getElementById("filter-curso");

    if (!campo) {
      return;
    }

    campo.addEventListener("change", () => {
      this.renderizarAlunos();
    });
  },

  filtrarAlunos() {
    const pesquisa =
      document
        .getElementById("search-alunos")
        ?.value.toLocaleLowerCase("pt-BR")
        .trim() || "";

    const curso = document.getElementById("filter-curso")?.value || "";

    return this.alunos.filter((aluno) => {
      const nome = String(aluno.nome || "").toLocaleLowerCase("pt-BR");

      const matricula = String(aluno.matricula || "").toLocaleLowerCase(
        "pt-BR",
      );

      const correspondePesquisa =
        nome.includes(pesquisa) || matricula.includes(pesquisa);

      const correspondeCurso = !curso || aluno.curso === curso;

      return correspondePesquisa && correspondeCurso;
    });
  },

  /* ======================================================
       12. FORMULÁRIO
    ====================================================== */

  configurarFormulario() {
    const formulario = document.getElementById("form-aluno");

    if (!formulario) {
      return;
    }

    formulario.addEventListener("submit", (evento) => {
      const id = document.getElementById("aluno-id")?.value.trim() || "";

      const nome = document.getElementById("nome")?.value.trim() || "";

      const matricula =
        document.getElementById("matricula")?.value.trim() || "";

      const curso = document.getElementById("curso")?.value || "";

      if (!nome || !matricula || !curso) {
        evento.preventDefault();

        this.notificar("Preencha todos os campos obrigatórios.", "error");

        return;
      }

      if (!/^[0-9]{1,20}$/.test(matricula)) {
        evento.preventDefault();

        this.notificar("A matrícula deve conter apenas números.", "error");

        return;
      }

      const token = this.obterTokenCSRF();

      if (!token) {
        evento.preventDefault();

        this.notificar(
          "Token de segurança indisponível. Atualize a página.",
          "error",
        );

        return;
      }

      formulario.action = id ? "editar.php" : "processar.php";
    });
  },

  limparFormulario() {
    const formulario = document.getElementById("form-aluno");

    if (!formulario) {
      return;
    }

    formulario.reset();

    const campoId = document.getElementById("aluno-id");

    if (campoId) {
      campoId.value = "";
    }

    formulario.action = "processar.php";

    /*
     * O token CSRF é preservado.
     * Ele pertence à sessão e não deve ser apagado.
     */
  },

  /* ======================================================
       13. EDITAR ALUNO
    ====================================================== */

  editarAluno(aluno) {
    const campoId = document.getElementById("aluno-id");

    const campoNome = document.getElementById("nome");

    const campoMatricula = document.getElementById("matricula");

    const campoCurso = document.getElementById("curso");

    if (!campoId || !campoNome || !campoMatricula || !campoCurso) {
      this.notificar("Não foi possível abrir o formulário de edição.", "error");

      return;
    }

    campoId.value = aluno.id || "";

    campoNome.value = aluno.nome || "";

    campoMatricula.value = aluno.matricula || "";

    campoCurso.value = aluno.curso || "";

    this.navegarPara("cadastro");

    campoNome.focus();
  },

  /* ======================================================
       14. OBTER TOKEN CSRF
    ====================================================== */

  obterTokenCSRF() {
    const campo = document.getElementById("csrf-token");

    if (!campo) {
      return "";
    }

    return campo.value.trim();
  },

  /* ======================================================
       15. EXCLUIR ALUNO COM PROTEÇÃO CSRF
    ====================================================== */

  async solicitarExclusao(aluno) {
    if (!aluno || !aluno.id) {
      this.notificar("Identificador do aluno inválido.", "error");

      return;
    }

    const confirmado = window.confirm(
      `Deseja realmente excluir o aluno "${aluno.nome}"?\n\n` +
        "Essa ação não poderá ser desfeita.",
    );

    if (!confirmado) {
      return;
    }

    const tokenCSRF = this.obterTokenCSRF();

    if (!tokenCSRF) {
      this.notificar(
        "Token de segurança indisponível. Atualize a página.",
        "error",
      );

      return;
    }

    const dados = new URLSearchParams();

    dados.append("id", aluno.id);

    /*
     * NOVA PROTEÇÃO:
     * Enviamos o token CSRF junto com o ID.
     */

    dados.append("csrf_token", tokenCSRF);

    try {
      const resposta = await fetch("excluir.php", {
        method: "POST",

        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },

        body: dados.toString(),

        credentials: "same-origin",
      });

      if (!resposta.ok) {
        throw new Error("Não foi possível concluir a exclusão.");
      }

      const resultado = await resposta.json();

      if (!resultado.sucesso) {
        throw new Error(
          resultado.mensagem || "Não foi possível excluir o aluno.",
        );
      }

      this.notificar(
        resultado.mensagem || "Aluno excluído com sucesso!",
        "success",
      );

      await this.carregarAlunos();
    } catch (erro) {
      console.error("[AcadFlow] Erro na exclusão:", erro);

      this.notificar(erro.message || "Erro ao excluir aluno.", "error");
    }
  },

  /* ======================================================
       16. NOTIFICAÇÕES
    ====================================================== */

  notificar(mensagem, tipo = "success") {
    const container = document.querySelector(".toast-container");

    if (!container) {
      console.log(`[AcadFlow] ${tipo}: ${mensagem}`);

      return;
    }

    const toast = document.createElement("div");

    toast.className = `toast ${tipo}`;

    toast.textContent = mensagem;

    container.appendChild(toast);

    setTimeout(() => {
      toast.remove();
    }, 4500);
  },

  /* ======================================================
       17. MENSAGENS DO BACKEND
    ====================================================== */

  exibirMensagemURL() {
    const parametros = new URLSearchParams(window.location.search);

    const status = parametros.get("status");

    const mensagem = parametros.get("mensagem");

    if (!status || !mensagem) {
      return;
    }

    this.notificar(mensagem, status === "sucesso" ? "success" : "error");

    parametros.delete("status");

    parametros.delete("mensagem");

    const consulta = parametros.toString();

    const novaURL =
      window.location.pathname +
      (consulta ? `?${consulta}` : "") +
      window.location.hash;

    history.replaceState(null, "", novaURL);
  },
};

/* ==========================================================
   18. INICIAR APLICAÇÃO
========================================================== */

document.addEventListener("DOMContentLoaded", () => {
  AcadFlow.iniciar();
});
