# Naming Conventions

## Objetivo

Padronizar nomes de arquivos ligados a configuração, bootstrap, entrypoint e
scripts operacionais para facilitar navegação, automação e manutenção.

## Regras Gerais

- usar nomes curtos, explícitos e em `kebab-case`
- evitar prefixos vagos como `base`, `main`, `default` sem contexto
- manter o nome alinhado com a responsabilidade do arquivo
- separar arquivos por tipo de runtime ou domínio técnico

## Configuração

Arquivos de configuração devem ficar em `config/` e seguir:

- `<dominio>.php` para configuração principal
- `<dominio>.local.php` para sobreposição local quando necessário
- nomes esperados: `app.php`, `server.php`, `databases.php`, `cache.php`, `queue.php`

## Bootstrap

Arquivos de bootstrap executável devem ficar em `bin/` e seguir:

- `bootstrap-<runtime>` para inicialização compartilhada
- `bootstrap-http` para runtime web
- `bootstrap-cli` para runtime de console

## Entrypoint

Scripts de entrypoint de container devem ficar em `docker/entrypoint/` e seguir:

- `app.sh` para entrypoint comum da imagem
- `http.sh` para inicialização específica do runtime HTTP
- `cli.sh` para inicialização específica do runtime CLI

## Scripts Auxiliares

Scripts operacionais devem ter nome orientado a ação:

- `setup-local`
- `wait-for-mysql`
- `create-topics`

Quando shell script for necessário, usar extensão `.sh`.

## Adapter HTTP

Classes da camada HTTP devem seguir nomes orientados ao contrato público e ao
papel no transporte.

Controllers:

- usar sufixo `Controller`
- nomear pelo recurso ou capacidade exposta, como `WithdrawController`
- controllers utilitários de runtime podem manter nomes técnicos claros, como `HealthController`

Requests:

- usar sufixo `Request`
- nomear pela intenção de entrada, como `CreateWithdrawRequest`
- requests de consulta devem refletir a leitura exposta, como `FindWithdrawStatusRequest`

Responses:

- usar sufixo `Response`
- nomear pelo contrato público devolvido ao cliente, como `WithdrawAcceptedResponse`
- responses de erro compartilhadas devem manter nome explícito, como `ErrorResponse`

Mappers:

- usar sufixo `Mapper`
- nomear pela tradução realizada, como `CreateWithdrawRequestMapper` e `WithdrawStatusResponseMapper`

Middlewares:

- usar sufixo `Middleware`
- nomear pela preocupação transversal aplicada, como `CorrelationIdMiddleware`

Exception handlers:

- usar sufixo `Handler`
- nomear pela responsabilidade de serialização ou categoria tratada, como `UnexpectedThrowableHandler`

Registradores de rota:

- usar sufixo `Registrar`
- nomear pelo escopo de rotas registrado, como `HttpRouteRegistrar`

DTOs públicos:

- contratos públicos da API devem viver em `Adapters/Http/Dto/Public`
- o subnamespace público canônico do adapter HTTP é `Dto/Public`
- usar sufixos `RequestPayload` e `ResponsePayload` quando o tipo representar apenas forma serializável do contrato

## Adapter CLI

Classes e comandos da camada CLI devem seguir nomes orientados ao fluxo
operacional e ao agrupamento estável no runtime de console.

Namespaces:

- usar `Bootstrap` para preparo do runtime compartilhado da CLI
- usar `Command` para classes concretas registradas no console
- usar `Scheduler`, `Worker`, `Diagnostic` e `Support` apenas para responsabilidades do adapter CLI

Classes:

- comandos concretos usam sufixo `Command`, como `BootstrapSanityCheckCommand`
- componentes internos de scheduler usam nomes orientados ao fluxo, como `DueWithdrawScheduler` e `ScheduledWithdrawBatch`
- componentes internos de worker usam nomes orientados ao runtime ou consumo, como `WithdrawProcessingWorker` e `NotificationWorkerRuntime`
- componentes de diagnóstico usam nomes orientados ao recurso validado, como `MySqlConnectivityCheck` e `KafkaConnectivityCheck`

Assinaturas de comando:

- usar prefixo `app:` para bootstrap e validação operacional geral, como `app:sanity-check` e `app:config:validate`
- comandos de preparo de runtime da aplicação que precisam rodar antes do servidor HTTP devem manter o prefixo `app:`, como `app:schema:migrate`
- comandos de seed de bootstrap da aplicação devem manter o prefixo `app:` e explicitar o escopo semântico do seed, como `app:seed:case`
- usar prefixo `system:check:` para diagnósticos de dependências externas, como `system:check:mysql`, `system:check:kafka`, `system:check:mail` e `system:check:all`
- usar prefixo `withdraw:scheduler:` para comandos de scheduler, como `withdraw:scheduler:run`
- usar prefixo `withdraw:worker:` para comandos de worker, como `withdraw:worker:process` e `withdraw:worker:notify`
- manter três níveis de agrupamento quando o comando representar fluxo dedicado de saque
- evitar assinaturas vagas como `run`, `worker`, `process` ou `check` sem contexto do domínio

## Providers

Classes do módulo `Providers` devem seguir nomes orientados à responsabilidade
de integração concreta, sem duplicar componentes transversais que já existam em
`Plugins` ou no bootstrap do framework.

Bootstrap:

- usar sufixo `Bootstrap` para inicialização de providers por ambiente, como `ProviderRuntimeBootstrap`
- usar sufixo `Registry` quando o tipo apenas registrar ou agregar bindings, como `ProviderBindingRegistry`

Config:

- usar sufixo `Config` para objetos imutáveis de configuração por domínio, como `KafkaProducerConfig`
- usar sufixo `ConfigProvider` apenas para leitores centralizados de ambiente e defaults, como `MailConfigProvider`

Factories:

- usar sufixo `Factory` para construção segura de clients, gateways e adapters externos, como `KafkaProducerFactory`
- factories devem nomear explicitamente o artefato criado e evitar nomes genéricos como `DefaultFactory`

Kafka:

- usar sufixo `Producer` para publicadores, como `KafkaDomainEventProducer`
- usar sufixo `Consumer` para consumidores, como `KafkaWithdrawConsumer`
- usar sufixo `Mapper` para tradutores entre evento interno e payload de transporte, como `WithdrawEventMapper`
- usar sufixo `RetryPolicy` para estratégia operacional de retry, como `KafkaPublishRetryPolicy`
- usar sufixo `FailurePolicy` para tratamento operacional mínimo de falhas do broker, como `KafkaPublishFailurePolicy`
- erros operacionais explícitos do broker podem usar sufixo `Exception` no mesmo namespace técnico, como `KafkaPublishException` e `KafkaConsumeException`
- adaptadores que implementam `DomainEventDispatcher` com Kafka devem viver em `Providers\\Kafka` e usar sufixo `Dispatcher`, como `KafkaDomainEventDispatcher`
- contratos canônicos do transporte Kafka devem viver em `Providers\\Kafka\\Payload`
- usar sufixo `Payload` para o envelope e para o corpo publicado por tipo de evento, como `KafkaEventPayload` e `WithdrawProcessedPayload`

Mail:

- usar sufixo `Mailer` para integração concreta de envio SMTP, como `SmtpWithdrawMailer`
- usar sufixo `Template` para montagem de conteúdo, como `WithdrawNotificationTemplate`
- templates de assunto também devem usar sufixo `Template`, como `WithdrawNotificationSubjectTemplate`
- usar sufixo `Renderer` para materialização final do corpo a partir de um contrato seguro de evento ou resultado, como `WithdrawNotificationRenderer`
- usar sufixo `Dispatcher` para orquestração desacoplada de notificação, como `WithdrawNotificationDispatcher`
- quando o envio depender de dados externos ao evento final do core, como destinatário SMTP real, usar um contrato explícito de dispatch no mesmo namespace de mail em vez de inferir o valor do payload mascarado
- adaptadores de mensageria para notificação podem viver em `Providers\\Mail` quando o objetivo for apenas transformar a mensagem publicada no contrato do dispatcher, como `WithdrawNotificationKafkaHandler`

Logging:

- usar sufixo `Logger` apenas para implementações concretas de logger estruturado, como `HyperfStructuredLogger`
- usar sufixo `LogContextEnricher` para tipos que apenas acrescentam contexto operacional, como `KafkaLogContextEnricher`
- não criar logger exclusivo por provider quando a implementação transversal existente for suficiente

Metrics:

- usar sufixo `MetricEmitter` para implementações concretas de emissão, como `ProviderMetricEmitter`
- usar sufixo `MetricsCollector` apenas quando o tipo agregar contadores ou temporizadores operacionais

Runtime:

- usar sufixo `Resolver` para tipos que selecionam comportamento por ambiente, como `ProviderEnvironmentResolver`
- usar sufixo `Runtime` para componentes que representem estado ou dependência de execução compartilhada

Serialization:

- usar sufixo `Serializer` para serialização de payload, como `KafkaMessageSerializer`
- usar sufixo `Deserializer` para desserialização de payload externo
- usar sufixo `Normalizer` para transformações semânticas de dados antes do transporte

Support:

- usar sufixos orientados a intenção concreta, como `Validator`, `Parser`, `Formatter` ou `Builder`
- o namespace `Support` não deve receber contratos de domínio nem serviços transversais genéricos

## Data Plugin

Classes do módulo `Plugins\\Data` devem seguir nomes orientados à persistência
concreta, sem misturar regra de negócio do core com detalhes de SQL.

Bootstrap:

- usar sufixo `Registry` para agregadores de bindings do plugin, como `DataBindingRegistry`
- evitar wiring concreto espalhado fora de `config/autoload/dependencies.php` e do namespace `Plugins\\Data\\Bootstrap`

Config:

- usar sufixo `Config` para objetos imutáveis de conexão e runtime, como `MySqlConnectionConfig`
- usar sufixo `ConfigProvider` apenas para leitura centralizada de ambiente e defaults do plugin

Mappers:

- usar sufixo `Mapper` para tradução entre entidade e registro persistido, como `AccountRecordMapper`
- quando o mapper estiver focado em um agregado específico, refletir o recurso no nome, como `AccountWithdrawRecordMapper`
- o contrato canônico compartilhado do módulo é `RecordMapper`; implementações concretas devem manter o nome do agregado no tipo

Migrations:

- usar sufixo `Migration` para classes concretas que criam ou alteram tabelas, como `CreateAccountTableMigration`
- quando a migration representar a criação da tabela principal, usar o verbo `Create` seguido do nome canônico da tabela
- centralizar a ordem de execução do schema em um plano explícito, como `DataSchemaMigrationPlan`

Repositories:

- repositórios concretos MySQL usam prefixo `MySql` e sufixo `Repository`, como `MySqlAccountRepository`
- evitar nomes genéricos como `DefaultRepository` ou `BaseRepository`

Queries:

- consultas especializadas usam sufixo `Query`, como `DueScheduledWithdrawQuery`
- nomear a query pela leitura operacional materializada e não pela tecnologia

Seeds:

- seeds executáveis usam sufixo `Seeder`, como `PixWithdrawalCaseSeeder`
- seeds canônicas do case devem refletir o cenário no nome, como `PixWithdrawalCaseSeeder`
- rastreadores de execução única para bootstrap podem usar sufixo `Tracker`, como `SeedExecutionTracker`
- arquivos de apoio de massa podem manter nomes orientados ao cenário

Transactions:

- implementações concretas da abstração transacional podem usar sufixo `TransactionManager`, como `MySqlTransactionManager`
- operações atômicas e locks usam sufixos orientados à concorrência, como `AtomicAccountDebit` e `WithdrawProcessingLock`

## Advanced Plugin

Classes do módulo `Plugins\\Advanced` devem seguir nomes orientados à publicação
de contrato e hardening leve da borda HTTP, sem mover responsabilidade de rota
ou validação de negócio para dentro do plugin.

Bootstrap:

- usar sufixo `Registry` para agregadores de bindings do plugin, como `AdvancedPluginBindingRegistry`

Config:

- usar sufixo `Config` para objetos imutáveis de configuração, como `AdvancedPluginConfig`
- usar sufixo `ConfigProvider` para leitura centralizada da configuração carregada, como `AdvancedPluginConfigProvider`

Swagger:

- usar sufixo `Document` para contratos imutáveis da especificação, como `OpenApiDocument`
- usar sufixo `Factory` para geração da especificação publicada, como `OpenApiDocumentFactory`
- usar sufixo `Renderer` para materialização da página Swagger UI, como `SwaggerUiPageRenderer`

Security headers:

- usar sufixo `Policy` para o conjunto canônico de headers, como `SecurityHeadersPolicy`
- usar sufixo `Middleware` para aplicação da política na borda HTTP, como `SecurityHeadersMiddleware`
