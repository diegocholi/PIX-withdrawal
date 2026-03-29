# HTTP Header Conventions

## Objetivo

Padronizar os cabeçalhos relevantes da API HTTP de saque PIX.

## Cabeçalhos de request

- `Accept: application/json`
  Indica que o cliente espera payload JSON como contrato público da API.

- `Content-Type: application/json`
  Obrigatório em requests com corpo JSON, especialmente no endpoint de criação.

- `X-Correlation-Id`
  Cabeçalho opcional de rastreabilidade. Quando informado pelo cliente, deve ser propagado pela borda HTTP. Quando ausente, a borda pode gerar um valor e devolvê-lo na response.

## Cabeçalhos de response

- `Content-Type: application/json; charset=utf-8`
  Cabeçalho padrão para responses JSON da API.

- `X-Correlation-Id`
  Deve refletir o identificador efetivamente usado no processamento daquela request HTTP.

- `Location`
  Opcional para responses `202 Accepted` de criação. Quando presente, deve apontar para `GET /account/{accountId}/balance/withdraw/{withdrawId}`.

## Regras

- nomes de cabeçalho devem seguir a grafia canônica definida neste documento
- correlação HTTP usa sempre `X-Correlation-Id`; não criar aliases paralelos nesta API
- payloads e headers devem concordar sobre o mesmo `correlation_id` observado pela borda
- a política de payload máximo, content type aceito e headers mínimos de hardening da superfície pública está em `docs/api/http-surface-hardening-policy.md`
