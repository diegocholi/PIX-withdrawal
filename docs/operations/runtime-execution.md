# Runtime Execution

## Objetivo

Explicar como executar e validar manualmente os runtimes principais do projeto:
API HTTP, scheduler, worker financeiro e worker de notificação.

## API HTTP

No ambiente padrão a API sobe no serviço `app`.

Checks úteis:

```bash
curl -sS http://localhost:9501/health
curl -sS http://localhost:9501/ready
curl -sS http://localhost:9501/openapi.json
```

## Scheduler

O serviço `app-scheduler` roda continuamente no compose usando:

```bash
./bin/bootstrap-cli withdraw:scheduler:run --force --batch-size=<valor>
```

Para inspeção manual sem efeito colateral:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:scheduler:run --dry-run --batch-size=20
```

Para execução única real:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:scheduler:run --force --batch-size=20
```

## Worker financeiro

Consome mensagens do tópico de processamento e delega o saque ao caso de uso
`ProcessWithdrawHandler`.

Execução manual:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:worker:process --max-messages=10 --idle-timeout=5
```

## Worker de notificação

Consome eventos de notificação e envia email pelo SMTP configurado.

Execução manual:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:worker:notify --max-messages=10 --idle-timeout=5
```

## Fluxos principais para validação manual

### 1. Saque imediato aceito e processado

Criação:

```bash
curl -sS \
  -X POST http://localhost:9501/account/11111111-1111-4111-8111-111111111111/balance/withdraw \
  -H 'Content-Type: application/json' \
  -H 'x-correlation-id: manual-immediate-001' \
  -d '{
    "amount": "25.00",
    "method": "pix",
    "pix": {
      "key_type": "email",
      "key": "manual.immediate@example.com"
    }
  }'
```

Validação:

- a resposta HTTP deve ser `202 Accepted`
- o payload público deve voltar com `status: queued`
- o worker financeiro deve consumir a mensagem e concluir o saque em `done`

### 2. Saque agendado promovido pelo scheduler

Criação:

```bash
curl -sS \
  -X POST http://localhost:9501/account/22222222-2222-4222-8222-222222222222/balance/withdraw \
  -H 'Content-Type: application/json' \
  -H 'x-correlation-id: manual-scheduled-001' \
  -d '{
    "amount": "15.00",
    "method": "pix",
    "schedule": {
      "at": "2026-03-29T12:00:00+00:00"
    },
    "pix": {
      "key_type": "email",
      "key": "manual.scheduled@example.com"
    }
  }'
```

Validação:

- a resposta deve retornar `status: scheduled`
- após o horário de vencimento, o scheduler deve promover o saque para `queued`
- o worker financeiro deve finalizar o saque em `done`

### 3. Saldo insuficiente

Criação:

```bash
curl -sS \
  -X POST http://localhost:9501/account/44444444-4444-4444-8444-444444444444/balance/withdraw \
  -H 'Content-Type: application/json' \
  -H 'x-correlation-id: manual-insufficient-001' \
  -d '{
    "amount": "10.00",
    "method": "pix",
    "pix": {
      "key_type": "email",
      "key": "manual.insufficient@example.com"
    }
  }'
```

Validação:

- a API deve responder `409 Conflict`
- o código público esperado é `withdraw.insufficient_funds`

## Consulta de status

Para um saque existente do seed:

```bash
curl -sS \
  http://localhost:9501/account/11111111-1111-4111-8111-111111111111/balance/withdraw/aaaaaaa1-1111-4111-8111-111111111111
```

Casos úteis da massa canônica:

- `aaaaaaa1-1111-4111-8111-111111111111`: saque imediato concluído
- `bbbbbbb2-2222-4222-8222-222222222222`: saque agendado ainda pendente
- `ccccccc3-3333-4333-8333-333333333333`: saque finalizado com falha por saldo
  insuficiente
