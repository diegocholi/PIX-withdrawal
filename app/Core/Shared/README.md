# Shared

Componentes transversais reutilizaveis pelo nucleo.

Responsabilidades:

- tipos compartilhados
- contratos utilitarios
- servicos de suporte sem acoplamento a adapters ou plugins
- envelope de resultado para fluxos explicitos de sucesso e falha
- excecoes base reutilizaveis pelas camadas do core
- catalogo canonico de `error_code` estavel em `Core\\Shared\\ErrorCode`
- contrato e normalizacao de rastreio em `Core\\Shared\\TraceContext`
- payload estruturado de logging em `Core\\Shared\\LogContext`
- catalogo e payload de metricas em `Core\\Shared\\MetricName` e `Core\\Shared\\MetricPoint`
- contratos de serializacao segura e mascaramento em `Core\\Shared\\Contract\\EventPayloadSerializer`, `Core\\Shared\\Contract\\LogPayloadSerializer` e `Core\\Shared\\Contract\\SensitiveDataMasker`
