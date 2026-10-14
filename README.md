# ETRIP

Sistema de planejamento de viagem para carros elétricos no Espírito Santo.

Projeto desenvolvido para a feira de tecnologia da escola — Ensino Médio Técnico em Informática para Internet (1º ano). Marca/assinatura do grupo: **SGX**.

**Prazo de entrega:** 13/11

---

## O que é o ETRIP

O ETRIP ajuda donos de carro elétrico a planejar uma viagem, respondendo três perguntas principais:

1. A bateria do carro aguenta chegar até o destino?
2. Se não aguentar, onde parar para recarregar no caminho — sem nunca deixar o carro descarregar completamente antes de chegar a um posto?
3. Quanto tempo a viagem leva e quanto custa?

Diferente de carro a combustão, carro elétrico precisa de planejamento: a autonomia é menor e os pontos de recarga são mais espalhados. O ETRIP resolve isso de forma simples e local, sem depender de internet no dia da apresentação.

---

## Como funciona

**Tela 1 — `index.php`**

O usuário escolhe:
- Marca, modelo e versão do carro elétrico
- Cidade de origem e cidade de destino
- Percentual de bateria ao sair
- Percentual de bateria desejado ao chegar

**Cálculo — `app/models/CalculoEnergia.php` (chamado pelo controller `public/Resultado.php`)**

O sistema calcula a viagem considerando 3 situações possíveis, nunca assumindo que o carro pode chegar ao destino descarregado:

1. **Aguenta tranquilo** — chega com a reserva de bateria desejada
2. **Chega, mas abaixo da reserva** — o carro consegue chegar fisicamente ao destino, só que com menos bateria do que a pessoa queria; sugere recarregar lá
3. **Precisa parar antes** — a viagem gastaria mais energia do que a bateria tem inteira; o sistema busca a cidade mais distante possível, dentro do alcance real do carro, que tenha posto de recarga cadastrado. Se nem isso for possível, sugere recarregar mais antes de sair, na própria cidade de origem

**Resultado**

Mostra: imagem do carro, distância, tempo estimado, situação da bateria, posto sugerido (nome, endereço com botão de copiar, conector, potência, tempo de recarga) e custo estimado da viagem.

---

## Stack técnica

| Camada | Tecnologia |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP, organizado em MVC simplificado |
| Dados | Arrays PHP locais (sem API externa) |

### Por que sem API externa

O projeto inicialmente previa integração com APIs externas de rota (tipo OpenRouteService) e de postos de recarga (Open Charge Map). Essa abordagem foi trocada por dados locais pra:

- Eliminar dependência de internet no dia da apresentação
- Reduzir pontos de falha técnica
- Simplificar o desenvolvimento dentro do prazo

Os dados de distância e postos de recarga são reais, pesquisados manualmente (Google Maps e Open Charge Map) e cadastrados nos Models.

### Sobre o banco de dados

Hoje os dados ficam em arrays PHP (`app/models/`). A migração para **MySQL** (tabelas `carros` e `postos_recarga`) está em planejamento, já que o grupo tem tempo disponível no cronograma — mantendo as mesmas funções (`buscarCarroPorId()`, `listarMarcas()`, `buscarPostosPorCidade()`, etc.), só trocando de onde o dado vem, sem precisar alterar o restante do sistema.

---

## Estrutura de arquivos (MVC simplificado)

Sem framework e sem roteamento único — cada página continua acessível direto pela URL, mas o código é organizado por responsabilidade.

```
etrip/
│
├── public/                       Pasta pública (document root do servidor)
│   ├── index.php                 Tela 1 — formulário
│   ├── Resultado.php             Controller: recebe o POST, calcula, inclui a view
│   ├── historico.php             Controller: lista e permite excluir simulações
│   ├── 404.php                   Página de erro personalizada
│   ├── css/style.css             Estilo (inclui modo claro/escuro)
│   ├── js/
│   │   ├── selects.js            Cascata marca → modelo → versão
│   │   ├── sliders.js            Mostra o valor do slider em tempo real (%)
│   │   └── tema.js               Alterna e salva o modo escuro (localStorage)
│   ├── img/                      Logo do ETRIP
│   └── carrosimg/                Fotos dos carros, usadas no resultado e no histórico
│
└── app/                          Nada aqui é acessado direto pelo navegador
    ├── models/
    │   ├── CarroModel.php        Dados técnicos dos carros
    │   ├── CidadeModel.php       7 cidades do ES (rota BR-101) + distâncias
    │   ├── PostoModel.php        Postos de recarga reais do ES
    │   └── CalculoEnergia.php    Funções de cálculo (sem HTML, sem $_POST)
    │
    ├── views/
    │   ├── resultado.view.php    HTML da tela de resultado
    │   └── historico.view.php    HTML da tela de histórico
    │
    └── storage/
        └── historico.json        Criado automaticamente a cada simulação
```

**Classes opcionais** (`Carro.php`, `PostoRecarga.php`) ficariam em `app/models/` também, se o grupo decidir criá-las — não são obrigatórias pro funcionamento, já que todo o sistema funciona hoje com arrays simples.

---

## Equipe

| Responsável | Frente |
|---|---|
| **Miguel** | Backend PHP — lógica de cálculo, integração dos dados |
| **Lorenzo** | Estrutura de dados — organização dos Models e dos arrays |
| **Kaio** | Frontend — HTML, CSS, JavaScript (funcional e visual) |
| **Paulo** | Pesquisa de dados técnicos e testes de cenários |

---

## Dados cadastrados

### Carros elétricos (verificados com fontes reais — ver observações abaixo)

| Marca | Modelo | Versões |
|---|---|---|
| BYD | Dolphin Mini | GL, GS |
| BYD | Dolphin | GS |
| BYD | Yuan Pro | Padrão |
| Geely | EX2 | Pro, Max |
| Chevrolet | Spark EUV | Padrão |
| GAC | Aion UT | Premium, Elite |

### Cidades cobertas (Espírito Santo, seguindo a rota da BR-101)

Pinheiros → São Mateus → Linhares → Ibiraçu → Serra → Vitória → Vila Velha

Todas as 21 combinações possíveis de origem/destino entre essas cidades estão cadastradas com distância e tempo estimados.

### Postos de recarga

Postos reais cadastrados, pesquisados manualmente no Open Charge Map, cobrindo todas as 7 cidades do sistema — em expansão conforme o Paulo encontra mais opções.

---

## Recursos da interface

- **Imagem do carro** exibida na tela de resultado e nos cards de histórico
- **Botão de copiar endereço** do posto sugerido (usa `navigator.clipboard`)
- **Indicador de carregamento** no botão ao calcular
- **Histórico de simulações** (`historico.php`), sem necessidade de login — cada card mostra carro, rota, distância e data, com botão de excluir
- **Modo escuro**, com preferência salva no navegador (`localStorage`)
- **Página 404 personalizada**

---

## Escopo do MVP

**Incluído:**
- Seleção de carro (marca/modelo/versão)
- Cálculo de energia considerando % de bateria na saída e reserva desejada na chegada
- Verificação se a bateria aguenta a viagem, com lógica de 3 situações (ver seção "Como funciona")
- Sugestão de posto de recarga, inclusive em cidade intermediária quando necessário
- Estimativa de tempo de recarga e custo da viagem
- Histórico simples de simulações, sem login

**Fora do MVP (versões futuras):**
- Login de usuário
- Múltiplas paradas otimizadas numa mesma simulação
- Comparação entre carros na mesma tela
- Mapa visual interativo
- Ajuste do cálculo por elevação do terreno
- Integração com APIs externas em tempo real
- PWA (app instalável) — planejado como próximo passo, mencionável na apresentação

---

## Material de divulgação

Comercial em estilo "carro 0km" (dinâmico, com trilha forte), roteirizado em `roteiro-comercial-etrip.md`, incluindo cenas com os carros reais e cenas geradas por IA (prompts detalhados para cada uma). Perfil no Instagram planejado para documentar o desenvolvimento e divulgar o comercial finalizado.

---

## Observações para manutenção

- Os valores de distância em `CidadeModel.php` são estimativas; recomenda-se conferência periódica no Google Maps.
- Os dados de `CarroModel.php` (bateria, consumo, autonomia) foram verificados com fontes reais (fichas técnicas, Inmetro), mas vale reconferir se novas versões forem adicionadas.
- Todo o tratamento de erros (carro não encontrado, rota não cadastrada, percentuais inválidos) é feito via `try/catch`, com as mensagens centralizadas em `CalculoEnergia.php`.
- `public/` precisa ser configurado como document root do servidor, para que `app/` fique inacessível direto pelo navegador.
