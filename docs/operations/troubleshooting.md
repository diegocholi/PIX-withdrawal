# Troubleshooting

## API indisponivel em `localhost:9501`

Sintomas:

- `curl` falha com conexão recusada
- `docker compose ps` mostra `app` reiniciando

Checks:

```bash
docker compose logs --tail=100 app
docker compose run --rm app-cli ./bin/bootstrap-cli app:config:validate
```

Possiveis causas:

- falha no bootstrap do schema ou seed
- dependência externa ainda não saudável
- erro de configuração no `.env`

## Kafka indisponivel

Sintomas:

- `system:check:kafka` falha
- worker não consome mensagens
- scheduler relata falha de publicação

Checks:

```bash
docker compose logs --tail=100 kafka
docker compose logs --tail=100 kafka-init
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:kafka
```

Possiveis causas:

- broker ainda não concluiu o bootstrap
- tópicos não inicializados
- `KAFKA_BROKERS` divergente do ambiente em execução

## Worker parado ou sem consumo

Sintomas:

- saques ficam em `queued` sem avançar para `done` ou `failed`
- nenhuma mensagem nova aparece nos logs do worker

Checks:

```bash
docker compose ps app-worker-process
docker compose logs --tail=100 app-worker-process
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:worker:process --max-messages=1 --idle-timeout=3
```

Possiveis causas:

- Kafka indisponivel
- tópico incorreto
- worker encerrado por falha de configuração

## Scheduler não promove saques agendados

Sintomas:

- saque continua em `scheduled` após o horário de vencimento

Checks:

```bash
docker compose ps app-scheduler
docker compose logs --tail=100 app-scheduler
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:scheduler:run --dry-run --batch-size=20
```

Possiveis causas:

- horário do agendamento ainda não venceu
- clock do ambiente divergente
- falha de publicação do evento `withdraw.queued`

## Email não entregue

Sintomas:

- worker de notificação consome sem mensagem aparecer no Mailhog
- `system:check:mail` falha

Checks:

```bash
docker compose ps mailhog
docker compose logs --tail=100 mailhog
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:mail
```

Possiveis causas:

- `MAIL_HOST` ou `MAIL_PORT` incorretos
- worker de notificação não está consumindo
- mensagem nunca foi publicada no fluxo anterior

## Banco sem schema ou seed

Sintomas:

- erros SQL por tabela inexistente
- API sobe mas leituras basicas falham

Checks:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli app:schema:migrate
docker compose run --rm app-cli ./bin/bootstrap-cli app:seed:case
```

Observação:

- `app:seed:case` pode retornar `skipped` quando a massa já foi aplicada; isso é
  esperado
