# Technical Decisions

## Objetivo

Registrar as decisões arquiteturais principais que sustentam a entrega do case.

## DEC-001 Processamento assíncrono como caminho canônico

Decisão:

- saques imediatos e agendados são processados de forma assíncrona
- a API apenas aceita a solicitação e registra o trabalho

Motivação:

- reduzir latência na borda HTTP
- manter o mesmo caminho de processamento para cenários imediatos e agendados
- facilitar reprocessamento controlado e observabilidade operacional

## DEC-002 Status explícitos e transições protegidas

Decisão:

- o saque possui estados explícitos no domínio e no banco
- o worker reavalia o estado antes de executar efeitos finais

Motivação:

- evitar ambiguidade entre aceite e conclusão
- dar rastreabilidade clara para API, CLI e troubleshooting
- simplificar idempotência operacional em cenários de retry

## DEC-003 Débito atômico no banco

Decisão:

- o débito financeiro usa update atômico com guarda de saldo

Motivação:

- impedir saldo negativo sob concorrência
- reduzir janelas entre leitura e escrita do saldo
- materializar falha de saldo insuficiente de forma consistente

## DEC-004 Scheduler como disparador e não processador

Decisão:

- o scheduler local apenas localiza saques vencidos, promove para `queued` e
  publica o evento correspondente

Motivação:

- concentrar a regra financeira no worker
- evitar lógica duplicada em mais de um runtime
- manter o cron simples, previsível e barato de operar

## DEC-005 Documentação HTTP isolada em plugin avançado

Decisão:

- OpenAPI, Swagger UI e hardening leve da borda ficam em `Plugins/Advanced`

Motivação:

- preservar o `Core` sem dependências de documentação
- manter a documentação contratual como detalhe plugável da superfície HTTP
- permitir evolução da borda sem contaminar o domínio
