# HTTP Responses

Serializacao de respostas publicas da API.

Regras:

- manter payloads previsiveis e estaveis
- concentrar factories ou builders de sucesso e erro aqui
- evitar montar respostas diretamente nos controllers quando houver formato reutilizavel
- usar envelope `data` e `meta` nos endpoints publicos de negocio; endpoints operacionais podem permanecer flat
