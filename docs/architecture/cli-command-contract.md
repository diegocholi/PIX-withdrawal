# CLI Command Contract

## Objetivo

Padronizar argumentos, flags e códigos de saída dos comandos principais da CLI
para manter automação operacional previsível e evitar strings ou inteiros soltos.

## Regras de assinatura

- comandos da CLI devem preferir opções nomeadas a argumentos posicionais
- flags booleanas usam sempre `--kebab-case` e sem aliases curtos por padrão
- o runtime CLI deve aceitar `--correlation-id` como opção global compartilhada para sobrescrever o identificador de rastreio da execução
- valores numéricos operacionais usam nomes explícitos, como `--batch-size`, `--max-messages` e `--poll-timeout`
- comandos de scheduler devem reutilizar `--dry-run` e `--batch-size`
- comandos de worker devem reutilizar `--max-messages`, `--group-id`, `--topic`, `--poll-timeout` e `--idle-timeout`
- comandos sensíveis podem exigir `--force` para confirmar execução

## Catálogo canônico de opções

- `dry-run`: executa validação e simulação sem efeitos externos
- `correlation-id`: sobrescreve o identificador de rastreio da execução CLI; quando ausente, o runtime gera um valor automaticamente
- `batch-size`: limita a quantidade de itens processados por execução
- `max-messages`: limita a quantidade de mensagens consumidas em um ciclo de worker
- `group-id`: sobrescreve o consumer group de execução do worker
- `topic`: sobrescreve o tópico Kafka consumido pelo worker
- `poll-timeout`: define o tempo máximo de espera de cada poll do consumer Kafka em milissegundos
- `idle-timeout`: define o tempo máximo de espera ociosa antes do encerramento cooperativo
- `force`: confirma a execução de operação sensível

O catálogo compartilhado do adapter CLI vive em
`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Support\\CliOptionName`, referido no
adapter simplesmente como `CliOptionName`.

## Códigos de saída canônicos

- `0`: sucesso completo
- `2`: argumento ou combinação de flags inválida
- `3`: falha de dependência operacional, como MySQL, Kafka, SMTP ou configuração externa indisponível
- `4`: falha parcial quando o comando conclui com itens processados e itens rejeitados
- `5`: falha de execução não recuperável do comando
- `130`: interrupção cooperativa por sinal, como `SIGINT`

O catálogo compartilhado do adapter CLI vive em
`Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Support\\CliExitCode`, referido no
adapter simplesmente como `CliExitCode`.

## Regras de uso

- comandos devem retornar um código inteiro explícito
- não usar números mágicos diretamente nos comandos
- validação de argumentos deve falhar com `CliExitCode::INVALID_ARGUMENT`
- falhas de infraestrutura externa devem retornar `CliExitCode::DEPENDENCY_FAILURE`
- encerramento por sinal deve retornar `CliExitCode::INTERRUPTED`
- o mesmo `correlation_id` resolvido na entrada CLI deve ser reutilizado por todos os logs do ciclo de vida da mesma execução
- o runtime CLI deve materializar o `correlation_id` ativo em um contexto compartilhado da execução para reuso consistente por commands, scheduler e workers
- comandos principais da CLI devem registrar `cli.command.started` no início e `cli.command.finished` no término, com `result`, `exit_code` e `duration_ms` no contexto estruturado
- `system:check:mysql` deve validar a conexão MySQL configurada, executar consulta mínima de ping e expor `connection`, `database`, `ping` e `server_version` no payload final
- `system:check:kafka` deve validar os brokers configurados via metadata do cluster e expor `configured_brokers_count`, `discovered_brokers_count`, `discovered_topics_count` e `origin_broker_name` no payload final
- `system:check:mail` deve reutilizar o mailer SMTP configurado, enviar uma mensagem diagnóstica mínima e expor `transport`, `host`, `port`, `recipient` e `subject` no payload final
- `system:check:all` deve executar os checks canônicos de MySQL, Kafka e mail, consolidar `summary.total`, `summary.ok`, `summary.failed` e devolver `checks` indexado pelo nome canônico de cada dependência
- `app:config:validate` deve validar as configurações críticas de providers, runtimes, MySQL, Kafka e mail por meio dos objetos tipados e bindings registrados, com alias retrocompatível `config:validate`
- `app:schema:migrate` deve aplicar o schema canônico via `DataSchemaMigrationPlan` e ser seguro para repetição no bootstrap do container HTTP
- `app:seed:case` deve aplicar a massa canônica do case via `PixWithdrawalCaseSeeder`, registrar a execução em `seed_execution` e responder `skipped` quando o banco já estiver semeado
- comandos de diagnóstico e validação da CLI devem serializar o JSON final por um sanitizador dedicado do adapter, hoje `Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Support\\CliOutputSanitizer`
- o sanitizador de output da CLI deve reutilizar `SensitiveDataMasker` e mascarar pelo menos segredos conhecidos, usernames técnicos e endereços configurados de origem antes de escrever o payload no stdout
- `withdraw:scheduler:run` deve expor os ids promovidos, publicados e com `publication_failed` no payload final para tornar o resultado do lote operacionalmente explícito
- `withdraw:scheduler:run` deve exigir `--force` para execução com efeito real e aceitar `--dry-run` sem confirmação explícita para inspeção segura do lote
- `withdraw:scheduler:run` deve propagar o `correlation_id` da execução CLI no `trace_metadata` dos eventos publicados, preservando o `correlation_id` original do saque no payload de negócio
- no ambiente Docker local padrão, um runtime dedicado pode executar `withdraw:scheduler:run --force` em loop com intervalo configurável apenas para viabilizar testes ponta a ponta de saques agendados; esse bootstrap não substitui um scheduler real de produção
- `withdraw:worker:process` deve consumir mensagens do tópico `withdraw.process`, aceitar apenas `withdraw.queued` e expor `processed_messages` no payload final do comando
- `withdraw:worker:notify` deve consumir mensagens do tópico `notification.email.withdraw`, delegar ao `WithdrawNotificationKafkaHandler` e expor `processed_messages` no payload final do comando
- comandos de worker devem devolver `130` e status `interrupted` quando o loop encerrar por `SIGINT` ou `SIGTERM`
- workers da CLI devem propagar o `correlation_id` ativo da execução para os logs `kafka.consume.started` e `kafka.consume.stopped`, sem sobrescrever o `correlation_id` da mensagem em `kafka.consume.received`
- logs de publicação Kafka originados pela CLI devem preservar o `correlation_id` do evento publicado e carregar o `correlation_id` da execução CLI dentro de `trace_metadata` quando ele existir
