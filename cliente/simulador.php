<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-carrinho { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1">🛒 Mercado Inteligente</span>
    </div>
</nav>

<div class="container">
    <div class="card card-carrinho p-3 mb-4 bg-white">
        <h5>Montar Carrinho</h5>
        <div class="row g-2">
            <div class="col-12">
                <input type="text" id="produto" class="form-control" placeholder="Nome do produto (ex: Omo)">
            </div>
            <div class="col-6">
                <input type="number" id="qtd" class="form-control" placeholder="Qtd" value="1">
            </div>
            <div class="col-6">
                <button onclick="addItem()" class="btn btn-success w-100">Adicionar</button>
            </div>
        </div>
    </div>

    <div class="card card-carrinho p-3 bg-white">
        <h5>Meu Carrinho</h5>
        <ul id="lista" class="list-group list-group-flush mb-3">
            <!-- Itens entram aqui -->
        </ul>
        <div class="d-flex justify-content-between align-items-center">
            <strong>Total Estimado:</strong>
            <h3 class="text-primary">R$ <span id="total">0.00</span></h3>
        </div>
        <button class="btn btn-primary w-100 mt-3" onclick="compararPrecos()">Comparar Melhores Mercados</button>
    </div>
    
    <div id="resultadoComparacao" class="mt-4"></div>
</div>

<script>
let itensCarrinho = [];
let totalGeral = 0;

function addItem() {
    const nome = document.getElementById('produto').value;
    const qtd = parseInt(document.getElementById('qtd').value);

    if(nome === "") return alert("Digite o produto");

    // No MVP, como não temos o preço exato antes de comparar, 
    // simulamos uma lista para busca posterior
    itensCarrinho.push({nome, qtd});
    
    const lista = document.getElementById('lista');
    const li = document.createElement('li');
    li.className = "list-group-item d-flex justify-content-between align-items-center";
    li.innerHTML = `${nome} <span class="badge bg-secondary rounded-pill">${qtd}x</span>`;
    lista.appendChild(li);

    document.getElementById('produto').value = "";
}

function compararPrecos() {
    const resDiv = document.getElementById('resultadoComparacao');
    resDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div><p>Analisando mercados...</p></div>';

    // Aqui faríamos o fetch para api/comparar.php enviando itensCarrinho
    // Exemplo de retorno visual:
    setTimeout(() => {
        resDiv.innerHTML = `
            <div class="alert alert-success">
                <h6>🏆 Melhor Opção: Mercado Central</h6>
                <p class="small mb-0">Economia estimada de R$ 12,40</p>
            </div>
        `;
    }, 1500);
}
</script>
</body>
</html>