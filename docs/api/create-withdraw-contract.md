# Create Withdraw Contract

## Endpoint

- `POST /account/{accountId}/balance/withdraw`

## Objetivo

Solicitar a criação de um saque PIX para processamento assíncrono.

## Path Params

- `accountId`: UUID da conta origem do saque
- a borda HTTP rejeita valores ausentes ou fora do formato UUID antes de acionar o core

## Headers relevantes

- `Content-Type: application/json`
- `Accept: application/json`
- `X-Correlation-Id`: opcional nesta etapa; quando ausente poderá ser gerado pela borda HTTP
- a política de payload máximo e headers seguros desta superfície está em `docs/api/http-surface-hardening-policy.md`

## Diretriz de rate limiting

- o endpoint de criação é o primeiro candidato obrigatório a rate limiting quando a proteção de borda avançada for habilitada
- nesta etapa o adapter HTTP apenas formaliza a integração; a resposta nominal do endpoint continua `202 Accepted` enquanto não existir plugin concreto de limitação
- a diretriz detalhada de comportamento e preparo da borda está em `docs/api/create-withdraw-rate-limiting-guideline.md`

## Request Body

```json
{
  "amount": "150.25",
  "method": "PIX",
  "pix": {
    "key_type": "EMAIL",
    "key": "user@example.com"
  },
  "schedule": null
}
```

## Campos do request

- `amount`: obrigatório, string decimal com duas casas
- `method`: obrigatório, nesta etapa aceita apenas `PIX`
- `pix`: obrigatório, objeto com os dados da chave PIX
- `pix.key_type`: obrigatório, nesta etapa aceita apenas `EMAIL`
- `pix.key`: obrigatório, valor textual da chave PIX
- `schedule`: opcional, pode ser omitido ou enviado como `null` para saque imediato
- `schedule.at`: obrigatório quando `schedule` existir; deve usar data RFC 3339 futura

## Comportamento do campo schedule

- sem `schedule` ou com `schedule: null`, o saque é aceito para processamento assíncrono imediato
- com `schedule.at`, o saque é aceito como agendado e deve entrar inicialmente no estado público `scheduled`
- o campo `schedule` não altera o endpoint nem o contrato de sucesso; altera apenas o status inicial devolvido

## Response de sucesso

- status HTTP: `202 Accepted`
- envelope: `data` para o recurso e `meta` para metadados compartilhados da resposta

```json
{
  "data": {
    "withdraw_id": "wd_123",
    "account_id": "acc_123",
    "status": "queued",
    "scheduled": false,
    "scheduled_for": null,
    "status_url": "/account/acc_123/balance/withdraw/wd_123"
  },
  "meta": {
    "correlation_id": "corr_123"
  }
}
```

## Regras da response assíncrona

- `data.withdraw_id` identifica a solicitação criada
- `data.account_id` replica o contexto público do recurso
- `data.status` usa `queued` para saque imediato aceito e `scheduled` para saque agendado aceito
- `data.scheduled` indica de forma booleana se a solicitação nasceu agendada
- `data.scheduled_for` replica o agendamento em RFC 3339 quando existir
- `data.status_url` aponta para o endpoint público de consulta de status
- `meta.correlation_id` devolve o identificador de rastreio observado pela borda HTTP

## Regra de saldo insuficiente

- para saque imediato, a API valida saldo antes de persistir e antes de publicar processamento assíncrono
- quando a conta não possuir saldo suficiente no momento da criação, a API devolve `409 Conflict`
- nesse caso nenhum registro de saque é criado e nenhuma mensagem de processamento é publicada
- para saque agendado, a API não antecipa essa validação porque o saldo pode mudar até a data de execução

## Proteção temporal contra retry idêntico

- o backend permite dois saques com mesmo valor para a mesma conta
- para reduzir replay acidental, a API bloqueia payloads equivalentes aceitos nos últimos `60` segundos
- equivalencia considera `accountId`, `method`, `amount`, `pix.key_type`, `pix.key` e `schedule.at`
- quando o bloqueio ocorrer, a API devolve `409 Conflict` com mensagem clara e `retry_after_seconds`

## Exemplos

Saque imediato aceito:

```json
{
  "data": {
    "withdraw_id": "wd_123",
    "account_id": "acc_123",
    "status": "queued",
    "scheduled": false,
    "scheduled_for": null,
    "status_url": "/account/acc_123/balance/withdraw/wd_123"
  },
  "meta": {
    "correlation_id": "corr_123"
  }
}
```

Saque agendado aceito:

```json
{
  "data": {
    "withdraw_id": "wd_456",
    "account_id": "acc_123",
    "status": "scheduled",
    "scheduled": true,
    "scheduled_for": "2026-04-01T12:00:00+00:00",
    "status_url": "/account/acc_123/balance/withdraw/wd_456"
  },
  "meta": {
    "correlation_id": "corr_456"
  }
}
```

Retry idêntico bloqueado temporariamente:

```json
{
  "error": {
    "code": "withdraw.duplicate_request_blocked",
    "message": "An equivalent withdraw request was already accepted recently.",
    "details": {
      "withdraw_id": "wd_789",
      "retry_after_seconds": 42
    }
  },
  "meta": {
    "correlation_id": "corr_789"
  }
}
```

Saldo insuficiente para saque imediato:

```json
{
  "error": {
    "code": "withdraw.insufficient_funds",
    "message": "Account balance is insufficient for immediate withdraw creation.",
    "details": {
      "account_id": "acc_123",
      "requested_amount": "150.25"
    }
  },
  "meta": {
    "correlation_id": "corr_790"
  }
}
```
