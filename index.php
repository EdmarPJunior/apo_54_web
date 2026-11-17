<?php
session_start();

if (!isset($_SESSION['produtos'])) {
    $_SESSION['produtos'] = [];
}
if (!isset($_SESSION['transportadores'])) {
    $_SESSION['transportadores'] = [];
}
if (!isset($_SESSION['fretes'])) {
    $_SESSION['fretes'] = [];
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function voltar($pagina)
{
    header('Location: index.php?page=' . urlencode($pagina));
    exit;
}

function enderecoValido($endereco)
{
    foreach (['rua', 'numero', 'cep', 'cidade', 'estado'] as $campo) {
        if (trim($endereco[$campo] ?? '') === '') {
            return false;
        }
    }

    return true;
}

function enderecoTexto($endereco)
{
    return implode(', ', [
        $endereco['rua'],
        'nº ' . $endereco['numero'],
        $endereco['cep'],
        $endereco['cidade'] . '/' . $endereco['estado'],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir_produto') {
        $id = trim($_POST['id'] ?? '');
        if (isset($_SESSION['produtos'][$id])) {
            unset($_SESSION['produtos'][$id]);
            $_SESSION['mensagem'] = 'Produto excluído com sucesso.';
        } else {
            $_SESSION['mensagem'] = 'O produto selecionado não foi encontrado.';
        }

        voltar('produtos');
    }

    if ($acao === 'excluir_transportador') {
        $id = trim($_POST['id'] ?? '');
        if (isset($_SESSION['transportadores'][$id])) {
            unset($_SESSION['transportadores'][$id]);
            $_SESSION['mensagem'] = 'Transportador excluído com sucesso.';
        } else {
            $_SESSION['mensagem'] = 'O transportador selecionado não foi encontrado.';
        }

        voltar('transportadores');
    }

    if ($acao === 'salvar_produto') {
        $nome = trim($_POST['nome'] ?? '');
        $unidade = trim($_POST['unidade'] ?? '');
        $id = trim($_POST['id'] ?? '');

        if ($nome === '' || $unidade === '') {
            $_SESSION['mensagem'] = 'Informe o nome do produto e a unidade.';
            voltar('produtos');
        }

        if ($id !== '' && isset($_SESSION['produtos'][$id])) {
            $_SESSION['produtos'][$id] = ['id' => $id, 'nome' => $nome, 'unidade' => $unidade];
            $_SESSION['mensagem'] = 'Produto atualizado com sucesso.';
        } else {
            $id = uniqid('produto_');
            $_SESSION['produtos'][$id] = ['id' => $id, 'nome' => $nome, 'unidade' => $unidade];
            $_SESSION['mensagem'] = 'Produto cadastrado com sucesso.';
        }

        voltar('produtos');
    }

    if ($acao === 'salvar_transportador') {
        $campos = ['nome', 'documento', 'rua', 'numero', 'cep', 'cidade', 'estado'];
        $transportador = [];
        foreach ($campos as $campo) {
            $transportador[$campo] = trim($_POST[$campo] ?? '');
        }
        $id = trim($_POST['id'] ?? '');

        foreach ($campos as $campo) {
            if ($transportador[$campo] === '') {
                $_SESSION['mensagem'] = 'Preencha todos os campos do transportador.';
                voltar('transportadores');
            }
        }

        if ($id !== '' && isset($_SESSION['transportadores'][$id])) {
            $transportador['id'] = $id;
            $_SESSION['transportadores'][$id] = $transportador;
            $_SESSION['mensagem'] = 'Transportador atualizado com sucesso.';
        } else {
            $id = uniqid('transportador_');
            $transportador['id'] = $id;
            $_SESSION['transportadores'][$id] = $transportador;
            $_SESSION['mensagem'] = 'Transportador cadastrado com sucesso.';
        }

        voltar('transportadores');
    }

    if ($acao === 'salvar_frete') {
        $id = trim($_POST['id'] ?? '');
        $data = trim($_POST['data'] ?? '');
        $transportadorId = trim($_POST['transportador_id'] ?? '');
        $produtoIds = $_POST['produto_id'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];
        $origem = [];
        $destino = [];

        foreach (['rua', 'numero', 'cep', 'cidade', 'estado'] as $campo) {
            $origem[$campo] = trim($_POST['origem_' . $campo] ?? '');
            $destino[$campo] = trim($_POST['destino_' . $campo] ?? '');
        }

        $produtosDoFrete = [];
        foreach ($produtoIds as $indice => $produtoId) {
            $quantidade = trim($quantidades[$indice] ?? '');
            if ($produtoId !== '' && isset($_SESSION['produtos'][$produtoId]) && is_numeric($quantidade) && (float) $quantidade > 0) {
                $produto = $_SESSION['produtos'][$produtoId];
                $produtosDoFrete[] = [
                    'nome' => $produto['nome'],
                    'unidade' => $produto['unidade'],
                    'quantidade' => $quantidade,
                ];
            }
        }

        if ($data === '' || !isset($_SESSION['transportadores'][$transportadorId]) || count($produtosDoFrete) === 0 || !enderecoValido($origem) || !enderecoValido($destino)) {
            $_SESSION['mensagem'] = 'Confira a data, o transportador, pelo menos um produto com quantidade válida e os endereços.';
            header('Location: index.php?page=frete' . ($id !== '' ? '&id=' . urlencode($id) : ''));
            exit;
        }

        $transportador = $_SESSION['transportadores'][$transportadorId];
        $frete = [
            'id' => $id !== '' && isset($_SESSION['fretes'][$id]) ? $id : uniqid('frete_'),
            'data' => $data,
            'transportador' => $transportador['nome'],
            'origem' => $origem,
            'destino' => $destino,
            'produtos' => $produtosDoFrete,
        ];
        $_SESSION['fretes'][$frete['id']] = $frete;
        $_SESSION['mensagem'] = $id !== '' ? 'Frete atualizado com sucesso.' : 'Frete emitido com sucesso.';
        header('Location: index.php?page=detalhe&id=' . urlencode($frete['id']));
        exit;
    }
}

$pagina = $_GET['page'] ?? 'inicio';
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
$produtoEdicao = $_SESSION['produtos'][$_GET['id'] ?? ''] ?? null;
$transportadorEdicao = $_SESSION['transportadores'][$_GET['id'] ?? ''] ?? null;
$freteEdicao = $_SESSION['fretes'][$_GET['id'] ?? ''] ?? null;
$freteVisualizacao = $_SESSION['fretes'][$_GET['id'] ?? ''] ?? null;

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Fretes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <aside class="col-md-3 col-lg-2 sidebar p-3">
                <div class="brand">Sistema de Fretes</div>
                <nav>
                    <a class="<?= $pagina === 'inicio' ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-house"></i> Voltar para tela inicial
                    </a>

                    <a class="<?= $pagina === 'produtos' ? 'active' : '' ?>" href="index.php?page=produtos">
                        <i class="bi bi-plus-square"></i> Cadastro de Produtos
                    </a>

                    <a class="<?= $pagina === 'transportadores' ? 'active' : '' ?>" href="index.php?page=transportadores">
                        <i class="bi bi-truck"></i> Cadastro do Transportador
                    </a>

                    <a class="<?= $pagina === 'frete' ? 'active' : '' ?>" href="index.php?page=frete">
                        <i class="bi bi-box-seam"></i> Gerar Frete
                    </a>

                    <a class="<?= $pagina === 'consulta' ? 'active' : '' ?>" href="index.php?page=consulta">
                        <i class="bi bi-search"></i> Consultar Fretes
                    </a>
                </nav>
            </aside>
            <main class="col-md-9 col-lg-10 content">
                <?php if ($mensagem !== '' && $pagina !== 'inicio'): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= e($mensagem) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <?php if ($pagina === 'inicio'): ?>
                    <div class="tela-inicial">
                        <?php if (is_file(__DIR__ . '/imagens/frete2.jpg')): ?>
                            <img src="imagens/frete2.jpg" alt="Imagem inicial do sistema de fretes">
                        <?php else: ?>
                            <p class="text-secondary">Coloque uma imagem com o nome <strong>frete2.jpg</strong> na pasta <strong>imagens</strong>.</p>
                        <?php endif; ?>
                    </div>

                <?php elseif ($pagina === 'produtos'): ?>
                    <?php $editandoProduto = $produtoEdicao !== null; ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-1">Cadastro de Produtos</h1>
                            <p class="text-secondary mb-0"><?= $editandoProduto ? 'Edite os dados do produto.' : 'Cadastre e consulte os produtos disponíveis.' ?></p>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h5"><?= $editandoProduto ? 'Editar produto' : 'Novo produto' ?></h2>
                            <form method="post" class="row g-3">
                                <input type="hidden" name="acao" value="salvar_produto">
                                <input type="hidden" name="id" value="<?= e($produtoEdicao['id'] ?? '') ?>">
                                <div class="col-md-7"><label class="form-label" for="nome">Nome do produto</label><input class="form-control" id="nome" name="nome" required value="<?= e($produtoEdicao['nome'] ?? '') ?>"></div>
                                <div class="col-md-3"><label class="form-label" for="unidade">Unidade</label><input class="form-control" id="unidade" name="unidade" placeholder="Ex.: kg, caixa, unidade" required value="<?= e($produtoEdicao['unidade'] ?? '') ?>"></div>
                                <div class="col-md-2 d-flex align-items-end gap-2">
                                    <button class="btn btn-primary" type="submit"><?= $editandoProduto ? 'Salvar' : 'Salvar e novo' ?></button>
                                    <?php if ($editandoProduto): ?><a class="btn btn-outline-secondary" href="index.php?page=produtos">Cancelar</a><?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Produtos cadastrados</h2>
                            <?php if (count($_SESSION['produtos']) === 0): ?><p class="text-secondary mb-0">Nenhum produto cadastrado.</p>
                            <?php else: ?><div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Unidade</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($_SESSION['produtos'] as $produto): ?><tr>
                                                    <td><?= e($produto['nome']) ?></td>
                                                    <td><?= e($produto['unidade']) ?></td>
                                                    <td>
                                                        <div class="d-flex justify-content-end gap-2"><a class="btn btn-sm btn-outline-primary" href="index.php?page=produtos&id=<?= urlencode($produto['id']) ?>">Editar</a>
                                                            <form method="post" onsubmit="return confirm('Deseja excluir este produto?')"><input type="hidden" name="acao" value="excluir_produto"><input type="hidden" name="id" value="<?= e($produto['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button></form>
                                                        </div>
                                                    </td>
                                                </tr><?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div><?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($pagina === 'transportadores'): ?>
                    <?php $editandoTransportador = $transportadorEdicao !== null; ?>
                    <h1 class="h3 mb-1">Cadastro do Transportador</h1>
                    <p class="text-secondary mb-4">Cadastre os dados do transportador e seu endereço.</p>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h5"><?= $editandoTransportador ? 'Editar transportador' : 'Novo transportador' ?></h2>
                            <form method="post" class="row g-3">
                                <input type="hidden" name="acao" value="salvar_transportador">
                                <input type="hidden" name="id" value="<?= e($transportadorEdicao['id'] ?? '') ?>">
                                <div class="col-md-6"><label class="form-label" for="nome">Nome</label><input class="form-control" id="nome" name="nome" required value="<?= e($transportadorEdicao['nome'] ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="documento">CNPJ/CPF</label><input class="form-control" id="documento" name="documento" required value="<?= e($transportadorEdicao['documento'] ?? '') ?>"></div>
                                <div class="col-md-5"><label class="form-label" for="rua">Rua</label><input class="form-control" id="rua" name="rua" required value="<?= e($transportadorEdicao['rua'] ?? '') ?>"></div>
                                <div class="col-md-2"><label class="form-label" for="numero">Número</label><input class="form-control" id="numero" name="numero" required value="<?= e($transportadorEdicao['numero'] ?? '') ?>"></div>
                                <div class="col-md-2"><label class="form-label" for="cep">CEP</label><input class="form-control" id="cep" name="cep" required value="<?= e($transportadorEdicao['cep'] ?? '') ?>"></div>
                                <div class="col-md-2"><label class="form-label" for="cidade">Cidade</label><input class="form-control" id="cidade" name="cidade" required value="<?= e($transportadorEdicao['cidade'] ?? '') ?>"></div>
                                <div class="col-md-1"><label class="form-label" for="estado">UF</label><input class="form-control" id="estado" name="estado" maxlength="2" required value="<?= e($transportadorEdicao['estado'] ?? '') ?>"></div>
                                <div class="col-12"><button class="btn btn-primary" type="submit">Salvar</button>
                                    <?php if ($editandoTransportador): ?> <a class="btn btn-outline-secondary" href="index.php?page=transportadores">Cancelar</a><?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Transportadores cadastrados</h2>
                            <?php if (count($_SESSION['transportadores']) === 0): ?><p class="text-secondary mb-0">Nenhum transportador cadastrado.</p>
                            <?php else: ?><div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>CNPJ/CPF</th>
                                                <th>Cidade/UF</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($_SESSION['transportadores'] as $transportador): ?><tr>
                                                    <td><?= e($transportador['nome']) ?></td>
                                                    <td><?= e($transportador['documento']) ?></td>
                                                    <td><?= e($transportador['cidade']) ?>/<?= e($transportador['estado']) ?></td>
                                                    <td>
                                                        <div class="d-flex justify-content-end gap-2"><a class="btn btn-sm btn-outline-primary" href="index.php?page=transportadores&id=<?= urlencode($transportador['id']) ?>">Editar</a>
                                                            <form method="post" onsubmit="return confirm('Deseja excluir este transportador?')"><input type="hidden" name="acao" value="excluir_transportador"><input type="hidden" name="id" value="<?= e($transportador['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button></form>
                                                        </div>
                                                    </td>
                                                </tr><?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div><?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($pagina === 'frete'): ?>
                    <?php
                    $freteEmEdicao = $freteEdicao !== null;
                    $produtosSelecionados = $freteEdicao['produtos'] ?? [];
                    ?>
                    <h1 class="h3 mb-1"><?= $freteEmEdicao ? 'Editar Frete' : 'Gerar Frete' ?></h1>
                    <p class="text-secondary mb-4">Informe os dados do transporte, os produtos e os endereços.</p>
                    <?php if (count($_SESSION['produtos']) === 0 || count($_SESSION['transportadores']) === 0): ?>
                        <div class="alert alert-warning">Para gerar um frete, cadastre pelo menos um produto e um transportador.
                            <a href="index.php?page=produtos">Produtos</a> · <a href="index.php?page=transportadores">Transportadores</a>
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="acao" value="salvar_frete">
                            <input type="hidden" name="id" value="<?= e($freteEdicao['id'] ?? '') ?>">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h2 class="h5 mb-3">Dados do frete</h2>
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label" for="data">Data</label><input class="form-control" type="date" id="data" name="data" required value="<?= e($freteEdicao['data'] ?? date('Y-m-d')) ?>"></div>
                                        <div class="col-md-8"><label class="form-label" for="transportador_id">Transportador</label><select class="form-select" id="transportador_id" name="transportador_id" required>
                                                <option value="">Selecione</option>
                                                <?php foreach ($_SESSION['transportadores'] as $transportador): ?><option value="<?= e($transportador['id']) ?>" <?= (($freteEdicao['transportador'] ?? '') === $transportador['nome']) ? 'selected' : '' ?>><?= e($transportador['nome']) ?> — <?= e($transportador['documento']) ?></option><?php endforeach; ?>
                                            </select></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h2 class="h5 mb-0">Produtos</h2><button class="btn btn-sm btn-outline-primary" type="button" id="adicionar-produto">Adicionar produto</button>
                                    </div>
                                    <div id="lista-produtos">
                                        <?php foreach ($produtosSelecionados as $produtoFrete): ?>
                                            <div class="row g-2 align-items-end produto-linha mb-2">
                                                <div class="col-md-8"><label class="form-label">Produto</label><select class="form-select" name="produto_id[]" required>
                                                        <option value="">Selecione</option><?php foreach ($_SESSION['produtos'] as $produto): ?><option value="<?= e($produto['id']) ?>" <?= $produto['nome'] === $produtoFrete['nome'] ? 'selected' : '' ?>><?= e($produto['nome']) ?> (<?= e($produto['unidade']) ?>)</option><?php endforeach; ?>
                                                    </select></div>
                                                <div class="col-md-3"><label class="form-label">Quantidade</label><input class="form-control" type="number" step="any" min="0.01" name="quantidade[]" required value="<?= e($produtoFrete['quantidade']) ?>"></div>
                                                <div class="col-md-1"><button class="btn btn-outline-danger remover-produto" type="button" aria-label="Remover produto">×</button></div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($produtosSelecionados) === 0): ?>
                                            <div class="row g-2 align-items-end produto-linha mb-2">
                                                <div class="col-md-8"><label class="form-label">Produto</label><select class="form-select" name="produto_id[]" required>
                                                        <option value="">Selecione</option><?php foreach ($_SESSION['produtos'] as $produto): ?><option value="<?= e($produto['id']) ?>"><?= e($produto['nome']) ?> (<?= e($produto['unidade']) ?>)</option><?php endforeach; ?>
                                                    </select></div>
                                                <div class="col-md-3"><label class="form-label">Quantidade</label><input class="form-control" type="number" step="any" min="0.01" name="quantidade[]" required></div>
                                                <div class="col-md-1"><button class="btn btn-outline-danger remover-produto" type="button" aria-label="Remover produto">×</button></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php foreach (['origem' => 'Endereço de origem', 'destino' => 'Endereço de destino'] as $tipo => $titulo): ?>
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h2 class="h5 mb-3"><?= e($titulo) ?></h2>
                                        <div class="row g-3">
                                            <?php foreach (['rua' => 'Rua', 'numero' => 'Número', 'cep' => 'CEP', 'cidade' => 'Cidade', 'estado' => 'Estado'] as $campo => $rotulo): ?>
                                                <div class="<?= $campo === 'rua' ? 'col-md-5' : ($campo === 'cidade' ? 'col-md-3' : 'col-md-2') ?>">
                                                    <label class="form-label" for="<?= e($tipo . '_' . $campo) ?>"><?= e($rotulo) ?></label>
                                                    <input class="form-control" id="<?= e($tipo . '_' . $campo) ?>" name="<?= e($tipo . '_' . $campo) ?>" <?= $campo === 'estado' ? 'maxlength="2"' : '' ?> required value="<?= e($freteEdicao[$tipo][$campo] ?? '') ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button class="btn btn-success btn-lg" type="submit"><?= $freteEmEdicao ? 'Salvar alterações' : 'Emitir frete' ?></button>
                            <?php if ($freteEmEdicao): ?> <a class="btn btn-outline-secondary btn-lg" href="index.php?page=detalhe&id=<?= urlencode($freteEdicao['id']) ?>">Cancelar</a><?php endif; ?>
                        </form>
                        <template id="modelo-produto">
                            <div class="row g-2 align-items-end produto-linha mb-2">
                                <div class="col-md-8"><label class="form-label">Produto</label><select class="form-select" name="produto_id[]" required>
                                        <option value="">Selecione</option><?php foreach ($_SESSION['produtos'] as $produto): ?><option value="<?= e($produto['id']) ?>"><?= e($produto['nome']) ?> (<?= e($produto['unidade']) ?>)</option><?php endforeach; ?>
                                    </select></div>
                                <div class="col-md-3"><label class="form-label">Quantidade</label><input class="form-control" type="number" step="any" min="0.01" name="quantidade[]" required></div>
                                <div class="col-md-1"><button class="btn btn-outline-danger remover-produto" type="button" aria-label="Remover produto">×</button></div>
                            </div>
                        </template>
                    <?php endif; ?>

                <?php elseif ($pagina === 'detalhe' && $freteVisualizacao !== null): ?>
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="h3 mb-1">Frete emitido</h1>
                            <p class="text-secondary mb-0">Resumo das informações do transporte.</p>
                        </div>
                        <a class="btn btn-outline-primary" href="index.php?page=frete&id=<?= urlencode($freteVisualizacao['id']) ?>">Editar frete</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">Data</dt>
                                <dd class="col-sm-9"><?= e(date('d/m/Y', strtotime($freteVisualizacao['data']))) ?></dd>
                                <dt class="col-sm-3">Transportador</dt>
                                <dd class="col-sm-9"><?= e($freteVisualizacao['transportador']) ?></dd>
                                <dt class="col-sm-3">Produtos</dt>
                                <dd class="col-sm-9">
                                    <ul><?php foreach ($freteVisualizacao['produtos'] as $produto): ?><li><?= e($produto['nome']) ?> — <?= e($produto['quantidade']) ?> <?= e($produto['unidade']) ?></li><?php endforeach; ?></ul>
                                </dd>
                                <dt class="col-sm-3">Origem</dt>
                                <dd class="col-sm-9"><?= e(enderecoTexto($freteVisualizacao['origem'])) ?></dd>
                                <dt class="col-sm-3">Destino</dt>
                                <dd class="col-sm-9"><?= e(enderecoTexto($freteVisualizacao['destino'])) ?></dd>
                            </dl>
                        </div>
                    </div>
                    <a class="btn btn-primary mt-3" href="index.php?page=consulta">Consultar fretes</a>

                <?php elseif ($pagina === 'consulta'): ?>
                    <?php
                    $dataConsulta = $_GET['data'] ?? '';
                    $fretesEncontrados = [];
                    if ($dataConsulta !== '') {
                        foreach ($_SESSION['fretes'] as $frete) {
                            if ($frete['data'] === $dataConsulta) {
                                $fretesEncontrados[] = $frete;
                            }
                        }
                    }
                    ?>
                    <h1 class="h3 mb-1">Consultar Fretes</h1>
                    <p class="text-secondary mb-4">Selecione uma data para consultar os fretes emitidos nesse dia.</p>
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" class="row g-3 align-items-end">
                                <input type="hidden" name="page" value="consulta">
                                <div class="col-md-4"><label class="form-label" for="data-consulta">Data</label><input class="form-control" type="date" id="data-consulta" name="data" required value="<?= e($dataConsulta) ?>"></div>
                                <div class="col-md-2"><button class="btn btn-primary" type="submit">Consultar</button></div>
                            </form>
                        </div>
                    </div>
                    <?php if ($dataConsulta !== ''): ?>
                        <div class="card">
                            <div class="card-body">
                                <h2 class="h5 mb-3">Resultados para <?= e(date('d/m/Y', strtotime($dataConsulta))) ?></h2>
                                <?php if (count($fretesEncontrados) === 0): ?><p class="text-secondary mb-0">Nenhum frete encontrado para esta data.</p>
                                <?php else: ?><div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Transportador</th>
                                                    <th>Produtos</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($fretesEncontrados as $frete): ?><tr>
                                                        <td><?= e(date('d/m/Y', strtotime($frete['data']))) ?></td>
                                                        <td><?= e($frete['transportador']) ?></td>
                                                        <td><?= e(implode(', ', array_column($frete['produtos'], 'nome'))) ?></td>
                                                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="index.php?page=detalhe&id=<?= urlencode($frete['id']) ?>">Ver frete</a></td>
                                                    </tr><?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>