# Providers Namespace Map

## Objetivo

Definir a estrutura inicial do módulo `Providers` para manter a organização das
integrações concretas previsível desde o bootstrap até os adaptadores externos.

## Namespace raiz

- `Tecnofit\\PixWithdrawal\\Providers`

## Namespaces canônicos iniciais

- `Tecnofit\\PixWithdrawal\\Providers\\Bootstrap`
- `Tecnofit\\PixWithdrawal\\Providers\\Config`
- `Tecnofit\\PixWithdrawal\\Providers\\Factories`
- `Tecnofit\\PixWithdrawal\\Providers\\Kafka`
- `Tecnofit\\PixWithdrawal\\Providers\\Logging`
- `Tecnofit\\PixWithdrawal\\Providers\\Mail`
- `Tecnofit\\PixWithdrawal\\Providers\\Metrics`
- `Tecnofit\\PixWithdrawal\\Providers\\Runtime`
- `Tecnofit\\PixWithdrawal\\Providers\\Serialization`
- `Tecnofit\\PixWithdrawal\\Providers\\Support`

## Responsabilidade por grupo

`Bootstrap`

- inicialização por ambiente
- wiring explícito das integrações concretas
- registro centralizado dos bindings do core via `ProviderBindingRegistry`
- seleção de perfil por ambiente via `ProviderRuntimeBootstrap`
- declaração dos bindings obrigatórios por runtime em `providers.runtime.required_bindings`

`Config`

- leitura e validação de parâmetros de ambiente
- objetos de configuração por domínio técnico
- normalização do ambiente de providers via `ProviderEnvironment`
- leitura centralizada da configuração carregada via `ProviderConfigProvider`

`Factories`

- criação segura de clients, dispatchers e gateways externos
- materialização de config tipada por factories como `KafkaConfigFactory`

`Kafka`

- producer, consumer e tradução de mensagens de broker
- contratos de payload publicados no broker via `Providers\\Kafka\\Payload`

`Mail`

- SMTP, templates e dispatch de notificação

`Logging`

- logger estruturado e adaptadores operacionais de log

`Metrics`

- emissão concreta de métricas operacionais
- fallback no-op para ambientes sem backend de métricas habilitado

`Runtime`

- componentes compartilhados de execução e ambiente

`Serialization`

- serialização segura e mascaramento concretos usados por logs e eventos
- payloads e codecs específicos de transporte externo

`Support`

- utilitários internos restritos ao módulo `Providers`
- adapters de contratos do core para serviços técnicos do framework, como `HyperfDomainEventDispatcher`
