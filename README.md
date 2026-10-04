# ETRIP

Sistema de planejamento de viagem para carros elétricos no Espírito Santo.

Projeto desenvolvido para a feira de tecnologia da escola — Ensino Médio Técnico em Informática para Internet (1º ano).

**Prazo de entrega:** 13/11

---

## O que é o ETRIP

O ETRIP ajuda donos de carro elétrico a planejar uma viagem, respondendo três perguntas principais:

1. A bateria do carro aguenta chegar até o destino?
2. Se não aguentar, onde parar para recarregar no caminho?
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

**Cálculo — `CalculoEnergia.php`**

O sistema:
1. Busca a distância entre origem e destino na base local de cidades
2. Calcula a energia necessária: `distância (km) × consumo médio do carro (kWh/km)`
3. Considera a reserva de bateria desejada pelo usuário na chegada
4. Compara com a energia disponível e decide se a bateria aguenta
5. Se não aguentar, busca um posto de recarga real cadastrado na base local

**Resultado**

Mostra: distância, tempo estimado, se precisa recarregar, posto sugerido (quando necessário), tempo de recarga e custo estimado da viagem.

---

## Stack técnica

| Camada | Tecnologia |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Dados | Arrays PHP locais (sem banco de dados / sem API externa) |

### Por que sem banco de dados e sem API externa

O projeto inicialmente previa MySQL e integração com APIs externas (rota e postos de recarga). Essa abordagem foi trocada por dados locais (arrays PHP) para:

- Eliminar dependência de internet no dia da apresentação
- Reduzir pontos de falha técnica
- Simplificar o desenvolvimento dentro do prazo de 8 semanas

Os dados de distância e postos de recarga são reais, pesquisados manualmente (Google Maps e Open Charge Map) e cadastrados nos arquivos de dados.

---

## Estrutura de arquivos

```
etrip/
│
├── index.php                     Tela 1 — formulário (marca, modelo, versão, origem,
│                                destino e % de bateria, tudo gerado via PHP)
├── CalculoEnergia.php            Cálculo de energia/bateria + HTML do resultado
│                               
│
├── carrosimg/                    Fotos dos carros (.jpg/.png), usadas no resultado
│
├── css/
│   └── style.css
│
├── js/
│   ├── selects.js                Cascata marca → modelo → versão
│   │                             
│   └── sliders.js                Mostra o valor do slider em tempo real (%)
│
├── backend/
│   ├── classes/                  
│   │   ├── Carro.php
│   │   └── PostoRecarga.php
│   │
│   └── data/
│       ├── dados_carros.php      Dados técnicos dos carros 
│       ├── dados_cidades.php     7 cidades do ES (rota BR-101) + distâncias
│       └── dados_postos.php      Postos de recarga reais do ES
│
└── README.md
```

---

## Equipe

| Responsável | Frente |
|---|---|
| **Miguel** | Backend PHP — lógica de cálculo, integração dos dados |
| **Lorenzo** | Estrutura de dados — classes PHP e organização dos arrays |
| **Kaio** | Frontend — HTML, CSS, JavaScript (funcional e animações) |
| **Paulo** | Pesquisa de dados técnicos e testes de cenários |

---

## Dados cadastrados

### Carros elétricos (5 mais vendidos no Brasil em 2026, segundo ABVE)

- BYD Dolphin Mini
- BYD Dolphin
- Geely EX2
- Chevrolet Spark EUV
- BYD Yuan Pro

### Cidades cobertas (Espírito Santo, seguindo a rota da BR-101)

Pinheiros → São Mateus → Linhares → Ibiraçu → Serra → Vitória → Vila Velha

Todas as 21 combinações possíveis de origem/destino entre essas cidades estão cadastradas com distância e tempo estimados.

### Postos de recarga

17 postos reais cadastrados, pesquisados manualmente no Open Charge Map, cobrindo todas as 7 cidades do sistema.

---

## Escopo do MVP

**Incluído:**
- Seleção de carro (marca/modelo/versão)
- Cálculo de energia considerando % de bateria na saída e reserva desejada na chegada
- Verificação se a bateria aguenta a viagem
- Sugestão de posto de recarga quando necessário
- Estimativa de tempo de recarga e custo da viagem

**Fora do MVP (versões futuras):**
- Login e histórico de simulações
- Múltiplas paradas otimizadas
- Comparação entre carros
- Mapa visual interativo
- Ajuste do cálculo por elevação do terreno
- Integração com APIs externas em tempo real

---

## Observações para manutenção

- Os valores de distância em `dados_cidades.php` são estimativas; recomenda-se conferência periódica no Google Maps.
- Os dados de `dados_carros.php` (bateria, consumo, autonomia) devem ser conferidos nas fichas técnicas oficiais das montadoras.
- Todo o tratamento de erros (carro não encontrado, rota não cadastrada, percentuais inválidos) é feito via `try/catch` em `CalculoEnergia.php`.
