# Clean Environment Validation

## Objetivo

Validar que a entrega pode ser reproduzida a partir de um ambiente limpo, sem
dependência de estado local não documentado.

## Sequencia recomendada

1. Remover o ambiente anterior:

```bash
docker compose down -v
```

2. Confirmar a presença apenas dos arquivos versionados e do `.env`.

3. Subir tudo novamente:

```bash
docker compose up --build -d
```

4. Validar a configuração:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli app:config:validate
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:all
```

5. Conferir endpoints:

```bash
curl -sS http://localhost:9501/health
curl -sS http://localhost:9501/ready
curl -sS http://localhost:9501/openapi.json
```

6. Conferir seed:

```bash
curl -sS \
  http://localhost:9501/account/11111111-1111-4111-8111-111111111111/balance/withdraw/aaaaaaa1-1111-4111-8111-111111111111
```

## Critérios de aceite

- a API sobe sem etapa manual adicional
- schema e seed do case estão aplicados
- checks de MySQL, Kafka e mail concluem com sucesso
- endpoints públicos respondem conforme documentado
- o caso seeded pode ser consultado sem correções manuais no banco

## Evidências recomendadas

- saída de `docker compose ps`
- saída sanitizada de `system:check:all`
- resposta de `GET /health`
- resposta de consulta do saque seeded
