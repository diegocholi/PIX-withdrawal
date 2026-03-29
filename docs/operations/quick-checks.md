# Quick Checks

## Objetivo

Concentrar verificações curtas para confirmar se MySQL, Kafka, Mailhog e a
aplicação estão operacionais.

## Checks canônicos da CLI

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:mysql
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:kafka
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:mail
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:all
```

## HTTP

```bash
curl -sS http://localhost:9501/health
curl -sS http://localhost:9501/ready
curl -sS http://localhost:9501/openapi.json
```

## Docker

```bash
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 app-worker-process
docker compose logs --tail=100 app-scheduler
```

## MySQL

```bash
docker compose exec mysql mysql -upix_withdrawal -ppix_withdrawal -D pix_withdrawal -e "show tables;"
```

Esperado:

- tabelas `account`, `account_withdraw`, `account_withdraw_pix`,
  `account_transaction` e `seed_execution`

## Kafka

```bash
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh --bootstrap-server kafka:19092 --list
```

Esperado:

- tópicos de processamento e notificação disponíveis

## Mailhog

Abra `http://localhost:8025` para inspecionar mensagens entregues pelo worker
de notificação.
