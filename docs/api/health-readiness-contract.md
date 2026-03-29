# Health And Readiness Contract

## Objetivo

Padronizar os payloads operacionais dos endpoints utilitários da borda HTTP.

## Endpoints

- `GET /health`
- `GET /ready`

## Regras de contrato

- endpoints operacionais usam payload flat, sem envelope `data` e `meta`
- o shape canônico atual contém `status`, `check` e `runtime`
- `runtime` permanece `http` nos endpoints desta borda
- `check` identifica a natureza operacional do endpoint de forma estável para automação

## Payload de health

- status HTTP: `200 OK`

```json
{
  "status": "ok",
  "check": "health",
  "runtime": "http"
}
```

## Payload de readiness

- status HTTP: `200 OK` quando pronto
- status HTTP: `503 Service Unavailable` quando não pronto

Pronto:

```json
{
  "status": "ready",
  "check": "readiness",
  "runtime": "http"
}
```

Não pronto:

```json
{
  "status": "not_ready",
  "check": "readiness",
  "runtime": "http"
}
```
