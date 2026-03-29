# Providers Metrics

Implementacoes concretas de emissao de metricas.

Responsabilidades:

- enviar metricas operacionais dos providers
- oferecer fallback no-op quando o backend de metricas estiver desabilitado
- materializar um emitter concreto preparado para backend futuro
- encapsular backend de observabilidade
- manter nomes e pontos metricos consistentes
