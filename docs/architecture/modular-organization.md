# Organização Modular

## Objetivo

Definir uma convenção inicial para manter o bootstrap da aplicação simples,
previsível e com baixo acoplamento entre os módulos.

## Regra Central

O `Core` representa a regra de negócio e não deve depender de:

- `Adapters`
- `Providers`
- `Plugins`
- bibliotecas de infraestrutura concreta do framework

O fluxo permitido deve privilegiar a direção de dependência para dentro:

- `Adapters` podem depender de `Core`
- `Providers` podem depender de `Core`
- `Plugins` podem depender de `Core`
- `Core` não pode depender das camadas acima

## Responsabilidades

`app/Core`

- entidades, objetos de valor, contratos e serviços de domínio
- regras de negócio e casos de uso
- abstrações necessárias para integrar persistência, mensageria e notificação

`app/Adapters`

- entrada e saída da aplicação
- HTTP, CLI, serialização, parsing e tradução entre mundo externo e contratos internos

`app/Providers`

- integrações concretas com serviços externos do negócio
- gateways de pagamento, notificação e outros provedores especializados

`app/Plugins`

- recursos complementares e detalhes técnicos reutilizáveis
- implementações de suporte para dados, observabilidade ou capacidades avançadas
- plugins complementares de documentação contratual e hardening leve podem viver aqui, como `Plugins\\Advanced`

## Regras de Nomeação

- nomes devem explicitar intenção e contexto de negócio
- classes concretas devem ficar fora do `Core` quando representarem infraestrutura
- contratos devem nascer próximos do uso no `Core` sempre que a regra de negócio depender deles
- adaptadores devem traduzir dados; não devem carregar regra de negócio

## Aplicação Inicial

Os namespaces raiz refletem essa convenção:

- `Tecnofit\\PixWithdrawal\\Core`
- `Tecnofit\\PixWithdrawal\\Adapters`
- `Tecnofit\\PixWithdrawal\\Providers`
- `Tecnofit\\PixWithdrawal\\Plugins`

Essa estrutura é a base para os próximos passos de bootstrap HTTP e CLI.
