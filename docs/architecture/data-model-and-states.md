# Data Model And States

## Objetivo

Descrever o modelo de dados principal, os estados do saque e a estratégia de
consistência adotada no projeto.

## Tabelas principais

### `account`

Responsabilidade:

- saldo atual e identificação da carteira

Colunas de interesse:

- `id`
- `name`
- `balance`
- `created_at`
- `updated_at`

### `account_withdraw`

Responsabilidade:

- agregado principal do saque e sua máquina de estados

Colunas de interesse:

- `id`
- `account_id`
- `method`
- `amount`
- `status`
- `correlation_id`
- `idempotency_key`
- `retry_count`
- `scheduled`
- `scheduled_for`
- `queued_at`
- `processing_started_at`
- `processed_at`
- `error_reason`
- `last_retry_at`
- `created_at`
- `updated_at`

### `account_withdraw_pix`

Responsabilidade:

- payload especifico do metodo PIX

Colunas de interesse:

- `account_withdraw_id`
- `key_type`
- `key`
- `created_at`

### `account_transaction`

Responsabilidade:

- rastrear o débito financeiro efetivamente aplicado

Colunas de interesse:

- `account_id`
- `reference_type`
- `reference_id`
- `direction`
- `amount`
- `balance_before`
- `balance_after`
- `created_at`

### `seed_execution`

Responsabilidade:

- impedir repetição do seed canônico entre reinícios

## Estados do saque

Estados implementados em `Core/Domain/Enum/WithdrawStatus.php`:

- `PENDING`
- `SCHEDULED`
- `QUEUED`
- `PROCESSING`
- `DONE`
- `FAILED_INSUFFICIENT_FUNDS`
- `FAILED_VALIDATION`
- `FAILED_INTERNAL`

## Transições relevantes

- `PENDING` -> `QUEUED`: aceite imediato apto para publicação
- `SCHEDULED` -> `QUEUED`: promoção pelo scheduler após vencimento
- `QUEUED` -> `PROCESSING`: worker assumiu o saque
- `PROCESSING` -> `DONE`: débito e persistências concluídas
- `PROCESSING` -> `FAILED_INSUFFICIENT_FUNDS`: débito atômico rejeitado por saldo
- `PROCESSING` -> `FAILED_INTERNAL`: falha operacional classificada como interna

## Estratégia de consistência e idempotência

- o estado do saque fica explícito na tabela principal
- a borda trabalha com `correlation_id`, enquanto `idempotency_key` é gerado internamente pelo backend
- o scheduler só publica após promover o estado para `QUEUED`
- o worker verifica estados finais antes de repetir processamento
- o seed usa `seed_execution` para não duplicar massa de dados

## Estratégia de débito atômico e concorrência

O débito financeiro concreto vive em
`Plugins/Data/Transactions/MySqlAtomicAccountDebit`.

A operação usa `UPDATE ... WHERE balance >= amount` para garantir:

- ausência de saldo negativo sob concorrência
- falha determinística quando o saldo não cobre o valor do saque
- reaproveitamento da mesma regra tanto para carga normal quanto para mensagens
  repetidas

O processamento roda dentro de transação e persiste:

- mudança de estado do saque
- saldo final da conta
- lançamento em `account_transaction`

Isso reduz inconsistências entre saldo e histórico financeiro.
