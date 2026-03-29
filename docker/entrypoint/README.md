# Entrypoint

Convencao inicial para scripts de container:

- `app.sh` para fluxo compartilhado
- `http.sh` para runtime HTTP
- `cli.sh` para runtime CLI

Usar shell script com responsabilidade unica e nomes em `kebab-case` ou nome curto
orientado ao runtime quando o contexto da pasta ja for suficiente.
