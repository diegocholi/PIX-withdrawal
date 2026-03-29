# Data Plugin Namespace Map

## Objetivo

Documentar a organização base do plugin de dados para manter nomes, limites de
responsabilidade e pontos de extensão previsíveis.

## Namespace raiz

- `Tecnofit\\PixWithdrawal\\Plugins\\Data`

## Namespaces canônicos

- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Bootstrap`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Config`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Mappers`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Migrations`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Queries`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Repositories`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Seeds`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Transactions`

## Regras iniciais

- `Bootstrap` concentra o wiring explícito do plugin no container da aplicação.
- `Config` concentra objetos tipados e leitura validada de configuração do MySQL.
- `Mappers` traduzem entidades do domínio para linhas persistíveis e vice-versa.
- `Migrations` agrupa evolução de schema executada em MySQL 8.
- `Queries` concentra leituras especializadas do scheduler, worker e API.
- `Repositories` implementa contratos de `Core\\Infrastructure\\Repository`.
- `Seeds` armazena massa de desenvolvimento e teste executavel em ambiente Docker.
- `Transactions` materializa `TransactionManager`, débito atômico e controle de concorrência.
