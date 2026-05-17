document.addEventListener('DOMContentLoaded', () => {
    const campoPesquisa = document.querySelector('.search-text');

    if (!campoPesquisa) {
        return;
    }

    const paginas = [
        {
            titulo: 'Início',
            url: 'inicio.php',
            termos: ['inicio', 'home', 'principal']
        },
        {
            titulo: 'Simulação',
            url: 'simulacao.php',
            termos: ['simulacao', 'simular', 'energia', 'hidreletrica']
        },
        {
            titulo: 'Histórico',
            url: 'historico.php',
            termos: ['historico', 'registro']
        },
        {
            titulo: 'Análise Preditiva',
            url: 'analise_preditiva.php',
            termos: ['analise', 'preditiva', 'previsao', 'grafico']
        },
        {
            titulo: 'Dados Históricos',
            url: 'dados_historicos.php',
            termos: ['dados', 'historicos', 'dados historicos']
        },
        {
            titulo: 'Configurações',
            url: 'configuracoes.php',
            termos: ['config', 'configuracao', 'configuracoes']
        },
        {
            titulo: 'Configurações de Perfil',
            url: 'configuracoes_perfil.php',
            termos: ['perfil', 'usuario', 'conta', 'nome', 'senha', 'email']
        },
        {
            titulo: 'Configurações do Sistema',
            url: 'configuracoes_sistema.php',
            termos: ['sistema', 'tema', 'notificacoes', 'configuracoes sistema']
        }
    ];

    const caixa = campoPesquisa.closest('.caixa_de_texto');
    const lista = document.createElement('div');
    lista.className = 'resultados-pesquisa';
    lista.setAttribute('role', 'listbox');
    lista.hidden = true;

    caixa.appendChild(lista);
    campoPesquisa.setAttribute('autocomplete', 'off');
    campoPesquisa.setAttribute('aria-label', 'Pesquisar funcionalidades do sistema');

    const normalizar = (texto) => texto
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const buscarPaginas = (termo) => {
        const busca = normalizar(termo.trim());

        if (!busca) {
            return [];
        }

        return paginas.filter((pagina) => {
            const campos = [pagina.titulo, ...pagina.termos];
            return campos.some((campo) => normalizar(campo).includes(busca));
        }).slice(0, 6);
    };

    const abrirPagina = (url) => {
        window.location.href = url;
    };

    const esconderResultados = () => {
        lista.hidden = true;
        lista.innerHTML = '';
    };

    const renderizarResultados = () => {
        const resultados = buscarPaginas(campoPesquisa.value);
        lista.innerHTML = '';

        if (resultados.length === 0) {
            esconderResultados();
            return;
        }

        resultados.forEach((pagina) => {
            const botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'resultado-pesquisa';
            botao.setAttribute('role', 'option');
            botao.textContent = pagina.titulo;
            botao.addEventListener('mousedown', (event) => {
                event.preventDefault();
                abrirPagina(pagina.url);
            });
            botao.addEventListener('click', () => abrirPagina(pagina.url));
            lista.appendChild(botao);
        });

        lista.hidden = false;
    };

    campoPesquisa.addEventListener('input', renderizarResultados);

    campoPesquisa.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            esconderResultados();
            campoPesquisa.blur();
            return;
        }

        if (event.key === 'Enter') {
            const primeiroResultado = buscarPaginas(campoPesquisa.value)[0];
            if (primeiroResultado) {
                event.preventDefault();
                abrirPagina(primeiroResultado.url);
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (!caixa.contains(event.target)) {
            esconderResultados();
        }
    });

    campoPesquisa.addEventListener('focus', renderizarResultados);
});
