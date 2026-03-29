# PIX Withdrawal

## Visão geral

Este projeto implementa um case de saque PIX com processamento assíncrono, API HTTP em Hyperf 3, persistência em MySQL 8, mensageria com Kafka e notificação local via Mailhog.

A solução foi desenhada para separar com clareza:

* aceite da solicitação na borda HTTP
* persistência e rastreabilidade do saque
* processamento financeiro assíncrono em worker
* promoção de saques agendados por scheduler
* notificação desacoplada por eventos

O objetivo principal da arquitetura foi manter o fluxo consistente, escalável e com baixo acoplamento, sem transferir para a API HTTP responsabilidades de processamento financeiro que pertencem à camada assíncrona.

## Escopo funcional do case

O módulo cobre os fluxos principais pedidos para o case:

* criação de saque PIX imediato
* criação de saque PIX agendado
* consulta de status do saque
* processamento assíncrono centralizado em worker
* promoção automática de saques agendados vencidos para a fila
* tratamento explícito de saldo insuficiente
* notificação por email via Mailhog

## Stack principal

* PHP 8.3 com Hyperf 3
* MySQL 8
* Apache Kafka
* Mailhog
* Docker Compose

## Arquitetura em alto nível

O projeto segue organização modular com baixo acoplamento:

* `app/Core`: regras de negócio, contratos e casos de uso
* `app/Adapters`: bordas HTTP e CLI
* `app/Providers`: integrações concretas, como Kafka e mail
* `app/Plugins`: persistência e plugins complementares

### Fluxo resumido

1. A API valida a requisição e registra o saque.
2. Saques imediatos são persistidos e publicados para processamento assíncrono logo após o commit da transação.
3. Saques agendados são persistidos com estado `SCHEDULED`.
4. O scheduler localiza saques vencidos, promove com segurança para `QUEUED` e publica no Kafka.
5. O worker financeiro consome `withdraw.process`, executa o débito atômico e atualiza o estado final do saque.
6. Após a conclusão, o fluxo publica o evento necessário para o worker de notificação enviar o email correspondente.

### Princípios adotados

* a API aceita e registra a solicitação, mas não executa o processamento financeiro
* o scheduler não processa saque; ele apenas libera saques agendados para a fila
* o worker concentra a execução financeira para evitar duplicação de regra
* o banco é a fonte de verdade do estado do saque
* a mensageria desacopla aceite, processamento e notificação

Detalhes adicionais:

* [Arquitetura assíncrona](docs/architecture/async-withdraw-processing.md)
* [Modelo de dados e estados](docs/architecture/data-model-and-states.md)
* [Decisões técnicas](docs/architecture/technical-decisions.md)

## Estados do saque

A solução usa estados explícitos em vez de múltiplas flags booleanas, para evitar combinações inválidas e simplificar o controle operacional.

Estados principais:

* `QUEUED`: saque aceito e aguardando processamento assíncrono
* `SCHEDULED`: saque agendado aguardando vencimento
* `PROCESSING`: saque em processamento pelo worker
* `DONE`: saque concluído com sucesso
* `FAILED_INSUFFICIENT_FUNDS`: saque processado com falha por falta de saldo
* `FAILED`: falha técnica ou operacional não classificada como saldo insuficiente

Essa modelagem deixa as transições mais previsíveis, facilita idempotência e melhora a rastreabilidade do fluxo.

## Setup rápido

1. Copie o arquivo de ambiente:

```bash
cp .env.example .env
```

2. Suba o ambiente:

```bash
docker compose up --build -d
```

3. Valide o bootstrap:

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli app:config:validate
docker compose run --rm app-cli ./bin/bootstrap-cli system:check:all
```

4. Abra os endpoints principais:

* API: `http://localhost:9501`
* OpenAPI JSON: `http://localhost:9501/openapi.json`
* Swagger UI: `http://localhost:9501/docs`
* Mailhog UI: `http://localhost:8025`

O runtime HTTP executa `app:schema:migrate` e `app:seed:case` no bootstrap.
Se quiser preparar o banco manualmente, siga
[Operação via Docker Compose](docs/operations/docker-compose.md).

## Uso básico

### Criar saque imediato

```bash
curl -sS \
  -X POST http://localhost:9501/account/11111111-1111-4111-8111-111111111111/balance/withdraw \
  -H 'Content-Type: application/json' \
  -H 'x-correlation-id: readme-immediate-001' \
  -d '{
    "amount": "25.00",
    "method": "pix",
    "pix": {
      "key_type": "email",
      "key": "pix.immediate@example.com"
    }
  }'
```

### Criar saque agendado

```bash
curl -sS \
  -X POST http://localhost:9501/account/22222222-2222-4222-8222-222222222222/balance/withdraw \
  -H 'Content-Type: application/json' \
  -H 'x-correlation-id: readme-scheduled-001' \
  -d '{
    "amount": "35.00",
    "method": "pix",
    "schedule": {
      "at": "2026-04-01T12:00:00+00:00"
    },
    "pix": {
      "key_type": "email",
      "key": "pix.scheduled@example.com"
    }
  }'
```

### Consultar status

```bash
curl -sS \
  http://localhost:9501/account/11111111-1111-4111-8111-111111111111/balance/withdraw/aaaaaaa1-1111-4111-8111-111111111111
```

### Executar runtimes principais manualmente

```bash
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:scheduler:run --dry-run --batch-size=20
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:worker:process --max-messages=10 --idle-timeout=5
docker compose run --rm app-cli ./bin/bootstrap-cli withdraw:worker:notify --max-messages=10 --idle-timeout=5
```

Detalhes operacionais:

* [Docker Compose](docs/operations/docker-compose.md)
* [API, scheduler e workers](docs/operations/runtime-execution.md)
* [Troubleshooting](docs/operations/troubleshooting.md)

## Massa canônica do case

O seed `app:seed:case` cria um conjunto mínimo para validação manual:

* conta `11111111-1111-4111-8111-111111111111`
  com um saque imediato concluído
* conta `22222222-2222-4222-8222-222222222222`
  com um saque agendado pendente
* conta `33333333-3333-4333-8333-333333333333`
  com um saque que falhou por saldo insuficiente
* conta `44444444-4444-4444-8444-444444444444`
  com saldo zero para testes de rejeição

## Decisões técnicas principais

### Processamento assíncrono

O processamento financeiro não ocorre na thread HTTP. A API apenas valida, persiste e, no caso do saque imediato, publica o trabalho para processamento assíncrono.

Essa decisão foi tomada para:

* reduzir latência da borda HTTP
* desacoplar aceite da solicitação do tempo de processamento financeiro
* permitir escala horizontal dos workers
* concentrar o processamento financeiro em um único fluxo técnico

### Separação entre saque imediato e saque agendado

Saques imediatos são persistidos e publicados para processamento logo após o commit.
Saques agendados são persistidos em `SCHEDULED` e não entram em processamento na criação.

A publicação do saque agendado é responsabilidade exclusiva do scheduler quando `scheduled_for <= now`.

Essa separação evita que a API assuma a responsabilidade de iniciar processamento fora do momento correto.

### Cron como disparador, não como processador

O cron foi mantido apenas como mecanismo de ativação do scheduler.
Ele não executa débito, não envia email e não replica regra de negócio do worker.

Seu papel é apenas:

* localizar saques agendados vencidos
* promover com segurança para `QUEUED`
* publicar no Kafka para o worker financeiro

Isso evita duplicação de lógica e mantém o processamento centralizado.

### Débito atômico no banco

O débito da conta usa update atômico com guarda de saldo no MySQL.

Essa decisão foi adotada para:

* impedir saldo negativo
* suportar concorrência entre múltiplos workers
* evitar lógica de “ler saldo e depois decidir” em passos separados
* manter a consistência financeira no ponto mais confiável da arquitetura, que é o banco

### Timestamps persistidos em UTC

Os timestamps são persistidos em `UTC` no MySQL, mesmo quando a request chega com offset explícito, como `2026-03-29T20:15:00-03:00`.

Isso significa que o valor salvo na tabela pode aparecer como `2026-03-29 23:15:00`, que representa o mesmo instante absoluto em outro timezone.

Essa decisão foi adotada para:

* manter ordenação e comparação temporal consistentes entre API, scheduler, workers, banco e mensageria
* evitar ambiguidade ao persistir datas sem offset em colunas `DATETIME(6)`
* simplificar regras de agendamento, retry, timeout e auditoria técnica
* reduzir acoplamento com timezone de infraestrutura, ambiente local ou servidor
* deixar a conversão para `America/Sao_Paulo` e outros fusos como responsabilidade da camada de apresentação ou consulta

Ao inspecionar o banco manualmente, é esperado que o horário bruto não coincida visualmente com o horário original da request quando ela vier com offset diferente de `UTC`.

### Estados explícitos em vez de múltiplas flags

A modelagem do saque foi baseada em estados explícitos, e não em combinação de flags como `done`, `error` e equivalentes.

Isso melhora:

* clareza do fluxo
* previsibilidade das transições
* rastreabilidade operacional
* proteção contra combinações inválidas de estado

### Kafka como transporte assíncrono

Kafka não foi usado para “resolver tudo”, mas como transporte desacoplado entre aceite, processamento e notificação.

A escolha foi feita porque:

* reduz acoplamento entre API, scheduler e workers
* permite evolução futura com mais consumidores
* melhora resiliência operacional em cenários de pico ou falha transitória
* mantém o worker como dono do processamento, e não a fila

### Mailhog como provider local

Mailhog foi mantido como provider de email em ambiente local por aderência direta ao case e por simplificar a validação da notificação sem dependências externas.

## Trade-offs adotados no case

A solução foi desenhada para ser arquiteturalmente sólida sem expandir escopo além do necessário.

Trade-offs adotados:

* Kafka foi mantido por valor arquitetural no fluxo assíncrono, mas sem adicionar DLQ, retry avançado ou observabilidade enterprise.
* O projeto não implementa autenticação completa, rate limiting ou tracing distribuído, porque esses pontos não foram exigidos no case e deslocariam o foco da entrega principal.
* O scheduler foi mantido propositalmente simples: ele localiza vencidos, promove para `QUEUED` e publica o processamento.
* A validação principal da entrega foi priorizada em ambiente Docker reproduzível e documentação clara, em vez de CI/CD completa, já que pipeline não foi exigido no enunciado.

## Extensibilidade

Hoje o case aceita apenas `PIX` com chave do tipo `EMAIL`, mas a estrutura foi organizada para que essa restrição fique localizada e a expansão futura não exija refatoração ampla do fluxo assíncrono.

A evolução esperada ocorre por especialização de:

* método de saque
* payload aceito
* validação de entrada
* persistência específica do método

O fluxo principal permanece o mesmo:

* aceite
* persistência
* agendamento quando aplicável
* publicação
* processamento assíncrono
* atualização de estado
* consulta posterior

Assim, novos métodos de saque podem ser adicionados sem duplicar o ciclo principal de processamento.

## Documentação complementar

* [Índice da documentação](docs/README.md)
* [Operação completa via Docker Compose](docs/operations/docker-compose.md)
* [Execução manual da API, scheduler e workers](docs/operations/runtime-execution.md)
* [Checks rápidos do ambiente](docs/operations/quick-checks.md)
* [Troubleshooting básico](docs/operations/troubleshooting.md)
* [Checklist final de entrega](docs/checklists/final-delivery-checklist.md)
* [Validação em ambiente limpo](docs/delivery/clean-environment-validation.md)
* [Pacote final de entrega](docs/delivery/submission-package.md)
