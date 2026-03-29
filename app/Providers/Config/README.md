# Providers Config

Configuracao centralizada dos providers concretos.

Responsabilidades:

- ler ambiente e defaults
- validar parametros obrigatorios
- expor objetos de configuracao por dominio
- centralizar leitura do modulo via `ProviderConfigProvider`
- definir objetos tipados de configuracao como `KafkaConfig`
- executar validacao explicita por dominio com validators como `KafkaConfigValidator`
