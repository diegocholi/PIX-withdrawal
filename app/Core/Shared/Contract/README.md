# Contract

Contratos transversais reutilizaveis pelo nucleo.

Convencoes:

- `Traceable` padroniza `correlationId()` e `traceMetadata()` em comandos, queries, outputs e eventos
- `CorrelationIdGenerator` abstrai a geracao de `correlation_id` fora do transporte
- `StructuredLogger` define logging estruturado do core usando `Core\\Shared\\LogContext`
- `MetricEmitter` define emissao de metricas via `Core\\Shared\\MetricPoint`
- `SensitiveDataMasker`, `LogPayloadSerializer` e `EventPayloadSerializer` definem contratos abstratos de serializacao segura implementados em `Providers`
