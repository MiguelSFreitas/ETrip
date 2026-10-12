/**
 * tema.js
 *
 * Alterna entre modo claro e escuro, salvando a escolha no localStorage
 * pra lembrar na próxima visita. O index.php já aplica o tema salvo
 * antes de desenhar a página (ver script inline no <head>), então aqui
 * só cuidamos do clique no botão.
 */

document.addEventListener('DOMContentLoaded', function () {
    const botao = document.getElementById('toggle-tema');
    if (!botao) return;

    function atualizarIcone() {
        const escuro = document.documentElement.getAttribute('data-theme') === 'dark';
        botao.textContent = escuro ? '☀️' : '🌙';
    }

    atualizarIcone();

    botao.addEventListener('click', function () {
        const estaEscuro = document.documentElement.getAttribute('data-theme') === 'dark';

        if (estaEscuro) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('etrip-tema', 'light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('etrip-tema', 'dark');
        }

        atualizarIcone();
    });
});