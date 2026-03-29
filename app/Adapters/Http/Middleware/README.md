# HTTP Middleware

Componentes transversais da borda HTTP.

Exemplos:

- correlation id
- logging estruturado
- hardening basico do transporte

Regras:

- middlewares globais HTTP devem ser registrados em `config/autoload/middlewares.php`
- `correlation_id` deve ser resolvido aqui e salvo no atributo `correlation_id` do request
- responses sem `X-Correlation-Id` explicito devem recebê-lo aqui antes de sair da borda
- logging de requests e responses deve usar `Observability` com `LogContext`, sem registrar o corpo bruto da request
- logging de response deve incluir `status_code`, `duration_ms`, `status_family` e indicador de erro
- quaisquer dados sensíveis presentes no contexto estruturado, inclusive `pix.key` em payloads aninhados, devem ser mascarados pelo serializador central antes de chegar ao logger
