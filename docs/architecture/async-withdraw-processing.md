# Async Withdraw Processing

## Objetivo

Explicar a arquitetura assíncrona do saque PIX e o papel de API, Kafka,
scheduler e workers.

## Componentes principais

- `Adapters/Http`: aceita e consulta saques
- `Core/Application/UseCase/CreateWithdrawHandler`: registra o saque
- `Providers/Kafka`: publica e consome eventos de domínio
- `Adapters/Cli/Scheduler`: localiza saques agendados vencidos
- `Adapters/Cli/Worker`: executa o processamento financeiro e notificações
- `Plugins/Data`: persiste estado e projeções operacionais em MySQL

## Racional da arquitetura

- a API responde rapidamente sem bloquear o cliente em processamento financeiro
- o worker centraliza o débito e as transições finais de estado
- o scheduler apenas promove saques vencidos para a fila, evitando duplicar a
  regra de processamento
- Kafka desacopla aceite, processamento e notificação

## Fluxo de saque imediato

1. A API valida payload, rota e `correlation_id`.
2. O caso de uso cria o saque e o persiste em `queued`.
3. Um evento `withdraw.queued` é publicado no broker.
4. O worker financeiro consome a mensagem.
5. O saque passa para `processing`.
6. O débito atômico da conta é executado.
7. O saque termina em `done` ou `failed_*`.
8. O resultado pode gerar evento para notificação.

## Fluxo de saque agendado

1. A API aceita a solicitação futura.
2. O saque nasce em `scheduled`.
3. O scheduler procura saques vencidos em lote.
4. Cada saque promovido muda para `queued`.
5. O scheduler publica `withdraw.queued`.
6. O restante do fluxo reaproveita o mesmo worker financeiro.

## Fluxo de falha por saldo insuficiente

1. Saque imediato pode falhar ainda na borda HTTP com `409 Conflict`.
2. Saque agendado somente falha no processamento quando o worker tenta debitar a
   conta no vencimento.
3. O worker marca o saque como `FAILED_INSUFFICIENT_FUNDS`.
4. O estado final permanece rastreável por consulta de status.

## Papel restrito do cron ou scheduler

O runtime `app-scheduler` não substitui o worker financeiro:

- ele identifica saques elegíveis
- promove o estado para `queued`
- publica o evento correspondente

O scheduler não deve:

- debitar saldo
- decidir resultado final do saque
- duplicar a lógica de processamento do worker
