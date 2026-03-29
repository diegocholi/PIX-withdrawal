# Find Withdraw Status Contract

## Endpoint

- `GET /account/{accountId}/balance/withdraw/{withdrawId}`

## Objetivo

Consultar o estado operacional público de uma solicitação de saque PIX.

## Path Params

- `accountId`: UUID da conta origem
- `withdrawId`: UUID da solicitação de saque
- a borda HTTP rejeita valores ausentes ou fora do formato UUID antes de consultar o core

## Headers relevantes

- `Accept: application/json`
- `X-Correlation-Id`: opcional nesta etapa; quando ausente poderá ser gerado pela borda HTTP
- a política de headers seguros desta superfície está em `docs/api/http-surface-hardening-policy.md`

## Response de sucesso

- status HTTP: `200 OK`
- envelope: `data` para o recurso e `meta` para metadados compartilhados da resposta

```json
{
  "data": {
    "withdraw_id": "wd_123",
    "account_id": "acc_123",
    "status": "processing",
    "amount": "150.25",
    "method": "PIX",
    "pix": {
      "key_type": "EMAIL",
      "key_masked": "u***@example.com"
    },
    "scheduled": false,
    "scheduled_for": null,
    "processed_at": null,
    "error_reason": null,
    "failure_category": null
  },
  "meta": {
    "correlation_id": "corr_123"
  }
}
```

## Mapeamento público de status

- `PENDING` do core -> `pending`
- `SCHEDULED` do core -> `scheduled`
- `QUEUED` do core -> `queued`
- `PROCESSING` do core -> `processing`
- `DONE` do core -> `completed`
- `FAILED_INSUFFICIENT_FUNDS` do core -> `failed` com `failure_category: insufficient_funds`
- `FAILED_VALIDATION` do core -> `failed` com `failure_category: validation`
- `FAILED_INTERNAL` do core -> `failed` com `failure_category: internal`

## Regras do payload

- `data.withdraw_id` identifica a solicitação consultada
- `data.account_id` replica o escopo público do recurso
- `data.status` expõe o estado público estável do fluxo assíncrono
- `data.amount` preserva o valor decimal em string
- `data.method` expõe o método aceito pelo contrato público
- `data.pix` expõe apenas dados PIX mascarados quando houver payload PIX associado ao saque
- `data.pix.key_type` identifica o tipo público da chave PIX
- `data.pix.key_masked` expõe somente a chave mascarada; a chave completa nunca deve sair pela API pública
- `data.scheduled` indica se a solicitação nasceu agendada
- `data.scheduled_for` usa RFC 3339 quando houver agendamento
- `data.processed_at` usa RFC 3339 quando o processamento terminar
- `data.error_reason` pode trazer motivo legivel quando o estado final for falha
- `data.failure_category` estabiliza a categoria publica de falha
- `meta.correlation_id` devolve o identificador de rastreio observado na consulta

## Exemplos

Saque concluído:

```json
{
  "data": {
    "withdraw_id": "wd_123",
    "account_id": "acc_123",
    "status": "completed",
    "amount": "150.25",
    "method": "PIX",
    "pix": {
      "key_type": "EMAIL",
      "key_masked": "u***@example.com"
    },
    "scheduled": false,
    "scheduled_for": null,
    "processed_at": "2026-04-01T12:05:00+00:00",
    "error_reason": null,
    "failure_category": null
  },
  "meta": {
    "correlation_id": "corr_123"
  }
}
```

Saque com falha:

```json
{
  "data": {
    "withdraw_id": "wd_456",
    "account_id": "acc_123",
    "status": "failed",
    "amount": "150.25",
    "method": "PIX",
    "pix": {
      "key_type": "EMAIL",
      "key_masked": "u***@example.com"
    },
    "scheduled": false,
    "scheduled_for": null,
    "processed_at": "2026-04-01T12:05:00+00:00",
    "error_reason": "Insufficient funds.",
    "failure_category": "insufficient_funds"
  },
  "meta": {
    "correlation_id": "corr_456"
  }
}
```
