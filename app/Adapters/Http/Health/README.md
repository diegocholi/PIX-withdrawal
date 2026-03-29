# HTTP Health

Componentes utilitarios de healthcheck e readiness da API.

Regras:

- endpoints operacionais podem usar payload flat, sem envelope `data` e `meta`
- `/health` e `/ready` devem compartilhar o mesmo shape basico de payload
- o payload operacional canonico atual usa `status`, `check` e `runtime`
