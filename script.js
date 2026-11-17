const botaoAdicionar = document.getElementById('adicionar-produto');
const listaProdutos = document.getElementById('lista-produtos');
const modcoeloProduto = document.getElementById('modcoelo-produto');

if (botaoAdicionarProduto && listaProdutos && modeloProduto) {
    botaoAdicionarProduto.addEventListener('click', function () {
        listaProdutos.appendChild(modeloProduto.content.cloneNode(true));
    });

    listaProdutos.addEventListener('click', function (evento) {
        if (evento.target.classList.contains('remover-produto')) {
            const linhas = listaProdutos.querySelectorAll('.produto-linha');
            if (linhas.length > 1) {
                evento.target.closest('.produto-linha').remove();
            }
        }
    });
}
