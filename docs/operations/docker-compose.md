# Docker Compose Operation

## Objetivo

Documentar como subir, derrubar e validar a solução completa localmente via
Docker Compose, incluindo preparação inicial do banco.

## Pré-requisitos

- Docker Engine com Compose habilitado
- portas `9501`, `3306`, `9092`, `1025` e `8025` livres no host
- arquivo `.env` criado a partir de `.env.example`

## Bootstrap do ambiente

1. Criar o arquivo de ambiente:

```bash
cp .env.example .env
```

2. Construir e subir os containers:

```bash
docker compose up --build -d
```

3. Conferir os containers:

```bash
docker compose ps
```

4. Validar a configuração e dependências:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli app:config:validate
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:all
```

## Serviços e portas

| Serviço | Container | Porta host | Papel |
| --- | --- | --- | --- |
| app | `pix-withdrawal-app` | `9501` | API HTTP, OpenAPI e Swagger UI |
| app-cli | `pix-withdrawal-cli` | não exposta | Runtime manual para comandos de suporte |
| app-worker-process | `pix-withdrawal-worker-process` | não exposta | Worker financeiro que consome `withdraw.queued` |
| app-scheduler | `pix-withdrawal-scheduler` | não exposta | Loop que executa `withdraw:scheduler:run --force` |
| mysql | `pix-withdrawal-mysql` | `3306` | Persistência da aplicação |
| kafka | `pix-withdrawal-kafka` | `9092` | Broker Kafka local |
| kafka-init | `pix-withdrawal-kafka-init` | não exposta | Inicialização de tópicos do broker |
| mailhog | `pix-withdrawal-mailhog` | `1025`, `8025` | SMTP local e UI de inspeção |

## Dependências entre serviços

- `app`, `app-cli`, `app-worker-process` e `app-scheduler` dependem de:
  `mysql`, `kafka`, `kafka-init` e `mailhog`
- `kafka-init` depende do broker Kafka saudável
- o runtime HTTP aplica schema e seed antes de expor a API

## Preparação inicial do banco

O container `app` já executa no bootstrap:

- `./bin/bootstrap-cli app:schema:migrate`
- `./bin/bootstrap-cli app:seed:case`

Para preparar manualmente por CLI:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli app:schema:migrate
docker compose run --rm app-cli ./bin/bootstrap-cli app:seed:case
```

O seed é idempotente. Se a massa canônica já existir, o comando responde com
`skipped`.

## Ciclo de vida do ambiente

Subir:

```bash
docker compose up -d
```

Reconstruir imagem e subir:

```bash
docker compose up --build -d
```

Parar mantendo volumes:

```bash
docker compose down
```

Parar removendo volumes de dados locais:

```bash
docker compose down -v
```

## Endpoints para validação inicial

- API bootstrap: `http://localhost:9501/`
- health: `http://localhost:9501/health`
- readiness: `http://localhost:9501/ready`
- OpenAPI: `http://localhost:9501/openapi.json`
- Swagger UI: `http://localhost:9501/docs`
- Mailhog UI: `http://localhost:8025`

## Validação mínima após o bootstrap

- `docker compose ps` deve mostrar `app`, `app-worker-process`, `app-scheduler`,
  `mysql`, `kafka` e `mailhog` como ativos
- `curl http://localhost:9501/health` deve responder `200`
- `docker compose run --rm app-cli ./bin/bootstrap-cli system:check:all`
  deve retornar resumo sem falhas
