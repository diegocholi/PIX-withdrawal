# HTTP Surface Hardening Policy

## Objetivo

Definir limites e práticas mínimas de superfície para os endpoints HTTP públicos do módulo
sem acoplar a borda atual a uma implementação concreta de middleware, proxy ou plugin avançado.

## Escopo inicial

- a politica cobre principalmente `POST /account/{accountId}/balance/withdraw`
- `GET /account/{accountId}/balance/withdraw/{withdrawId}` segue a mesma diretriz de headers seguros, mas não compartilha o mesmo limite de payload por não receber corpo JSON
- health, readiness e bootstrap podem manter política operacional própria

## Content type aceito

- endpoints públicos com corpo devem aceitar apenas `Content-Type: application/json`
- requests sem `Content-Type` compatível devem ser rejeitadas antes do controller com `415 Unsupported Media Type`
- `Accept` esperado para endpoints públicos continua `application/json`

## Política de payload máximo

- o payload bruto do endpoint de criação deve permanecer pequeno e previsível
- a referência inicial do adapter HTTP é limitar o corpo de `POST /account/{accountId}/balance/withdraw` a `16 KiB`
- requests acima desse limite devem ser rejeitadas antes de acionar validação de negócio, idealmente com `413 Payload Too Large`
- o limite foi escolhido para acomodar o contrato JSON atual com margem operacional, sem permitir expansão arbitrária da superfície pública
- qualquer ampliação futura desse teto deve ser tratada como mudança contratual de borda e passar por revisão explícita

## Headers seguros mínimos

- endpoints públicos de negócio devem ficar preparados para retornar `Cache-Control: no-store`
- endpoints públicos de negócio devem ficar preparados para retornar `Pragma: no-cache`
- endpoints públicos JSON devem ficar preparados para retornar `X-Content-Type-Options: nosniff`
- esses headers reforçam não armazenamento indevido de payloads financeiros e reduzem interpretação incorreta do conteúdo
- a implementação concreta desses headers fica reservada ao backlog de hardening avançado

## Preparacao arquitetural da borda

- verificações de tamanho de corpo e content type devem ocorrer em middleware, server config ou guard equivalente, nunca em controller
- a borda HTTP atual deve manter contratos e bootstrap preparados para inserir essa protecao sem alterar o core
- a política detalhada de implementação concreta continua dependente das tarefas `ADV-8.1`, `ADV-8.1-T1`, `ADV-8.1-T2` e `ADV-8.1-T3`

## Limites desta etapa

- o adapter HTTP atual documenta a política, mas ainda não emite `413` ou `415` por mecanismo dedicado
- os headers seguros listados acima ainda não são obrigatórios em runtime enquanto o hardening avançado não for habilitado
