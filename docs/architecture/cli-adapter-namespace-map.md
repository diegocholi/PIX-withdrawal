# CLI Adapter Namespace Map

## Objetivo

Padronizar a organização inicial do adapter CLI para manter a evolução do
runtime de console previsível e alinhada ao bootstrap do Hyperf 3.

## Namespace Raiz

- `Tecnofit\\PixWithdrawal\\Adapters\\Cli`

## Convenções

`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Bootstrap`

- bootstrap compartilhado do runtime CLI
- preparação do container e registro central dos comandos

`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Command`

- comandos concretos registrados no console
- borda de argumentos, opções e códigos de saída

`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Scheduler`

- orquestração de rotinas agendadas e batches operacionais
- reutiliza queries, repositories e publishers sem carregar regra de negócio no comando

`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Worker`

- controle de ciclo de vida de consumers e loops cooperativos
- integração com sinais e shutdown gracioso quando disponível

`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Diagnostic`

- verificação de conectividade, bootstrap e configuração operacional

`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Support`

- utilitários internos restritos ao adapter CLI
- não deve receber regra de negócio nem contratos compartilhados do core

## Regras de Organização

- comandos do adapter CLI não concentram regra de negócio financeira
- bootstrap e runtime compartilhado ficam fora dos comandos concretos
- scheduler, worker e diagnóstico mantêm responsabilidades separadas
- utilitários específicos da CLI ficam em `Support` e não em namespaces compartilhados
