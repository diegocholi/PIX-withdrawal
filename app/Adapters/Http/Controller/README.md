# HTTP Controllers

Classes que recebem a requisicao HTTP, delegam ao core e retornam a response.

Regras:

- manter metodos pequenos e orientados ao endpoint
- nao concentrar regra de negocio financeira
- depender de mappers, requests e casos de uso em vez de arrays soltos
