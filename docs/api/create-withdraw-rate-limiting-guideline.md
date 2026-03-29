# Create Withdraw Rate Limiting Guideline

## Objetivo

Formalizar o comportamento esperado de rate limiting para `POST /account/{accountId}/balance/withdraw`
sem acoplar o adapter HTTP atual a uma implementação concreta de storage, middleware ou provider.

## Escopo

- o endpoint de criação de saque é o primeiro alvo obrigatório de rate limiting da API HTTP
- a implementação concreta fica para o backlog de advanced plugins
- a ausência temporária do plugin de rate limiting não altera o contrato nominal de sucesso atual do endpoint

## Comportamento esperado quando habilitado

- a proteção deve ser aplicada antes de o controller acionar o caso de uso do core
- requisições acima do limite devem ser rejeitadas com `429 Too Many Requests`
- a resposta de limite excedido deve usar o envelope canônico de erro da API, preservando `meta.correlation_id`
- `X-Correlation-Id` deve continuar presente na response mesmo quando a rejeição ocorrer na borda
- `Retry-After` pode ser retornado quando a estratégia concreta conseguir informar uma janela segura de retry
- headers adicionais de rate limiting ficam reservados para a etapa dedicada de plugins avançados

## Preparação arquitetural do adapter HTTP

- a integração deve acontecer por middleware, guard ou componente equivalente de borda, sem inserir regra de rate limiting nos controllers
- o endpoint de criação deve permanecer preparado para receber proteção dedicada no bootstrap HTTP ou no registrador de rotas
- o adapter HTTP deve continuar expondo contexto operacional suficiente para uma chave futura de limitação, como método, rota pública e `accountId` já saneado
- a decisão final sobre chave operacional, janela e quotas fica adiada para `ADV-4.1-T1` e `ADV-4.1-T2`

## Limites desta etapa

- nenhuma resposta `429` é emitida pelo adapter HTTP atual sem o plugin avançado correspondente
- nenhum header proprietário de rate limiting passa a ser obrigatório nesta fase
- endpoints de status, health e bootstrap seguem fora desta diretriz inicial e terão política própria em tarefas posteriores
