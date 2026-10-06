// Alterna o campo de senha entre oculto e visível e troca o ícone do olho
function mostrar_senha() {
    var senhaInput = document.querySelector('input[name="senha"]');
    var botao = document.getElementById('btn-olho');
    var olhoAberto = botao.querySelector('.icone-olho');
    var olhoFechado = botao.querySelector('.icone-olho-fechado');
    var mostrar = senhaInput.type === 'password';

    senhaInput.type = mostrar ? 'text' : 'password';
    // Em SVG a propriedade .hidden não existe; é preciso mexer no atributo
    olhoAberto.toggleAttribute('hidden', mostrar);
    olhoFechado.toggleAttribute('hidden', !mostrar);
    botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
}