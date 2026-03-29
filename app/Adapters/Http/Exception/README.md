# HTTP Exceptions

Estrutura de tratamento de erro da camada HTTP.

Regras:

- handlers de excecao do adapter HTTP ficam neste modulo
- mapear falhas do core para contratos publicos sem vazar detalhes internos
- manter codigos HTTP e payloads de erro consistentes
- usar envelope `error` e `meta` nas responses de falha
- incluir `meta.correlation_id` quando o identificador estiver disponivel na borda HTTP
