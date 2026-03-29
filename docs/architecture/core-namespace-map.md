# Core Namespace Map

## Objetivo

Padronizar a localização das classes do núcleo para manter previsibilidade,
baixo acoplamento e automação segura nas próximas tarefas.

## Namespace Raiz

- `Tecnofit\\PixWithdrawal\\Core`

## Convenções por Camada

`Tecnofit\\PixWithdrawal\\Core\\Application\\UseCase`

- implementações de casos de uso do núcleo
- classes nomeadas por ação de negócio, como `CreateWithdraw` e `ProcessWithdraw`

`Tecnofit\\PixWithdrawal\\Core\\Application\\Command`

- DTOs de intenção de escrita recebidos pelos casos de uso
- classes nomeadas com sufixo `Command`

`Tecnofit\\PixWithdrawal\\Core\\Application\\Query`

- DTOs de leitura e filtros de consulta internos
- classes nomeadas com sufixo `Query`

`Tecnofit\\PixWithdrawal\\Core\\Application\\Dto`

- dados de entrada e saída dos casos de uso
- classes nomeadas com sufixo `Input` ou `Output`

`Tecnofit\\PixWithdrawal\\Core\\Application\\Exception`

- erros de orquestração e validação aplicacional
- classes nomeadas com sufixo `Exception`

`Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity`

- entidades e agregados com identidade e ciclo de vida

`Tecnofit\\PixWithdrawal\\Core\\Domain\\ValueObject`

- tipos imutáveis orientados a regra de negócio

`Tecnofit\\PixWithdrawal\\Core\\Domain\\Enum`

- enums do domínio e estados fechados

`Tecnofit\\PixWithdrawal\\Core\\Domain\\Service`

- serviços de domínio sem estado persistente

`Tecnofit\\PixWithdrawal\\Core\\Domain\\Event`

- eventos internos disparados por mudanças relevantes de estado
- classes nomeadas no passado, como `WithdrawCreated`

`Tecnofit\\PixWithdrawal\\Core\\Domain\\Exception`

- erros de regra de negócio e invariantes

`Tecnofit\\PixWithdrawal\\Core\\Domain\\Contract`

- contratos do domínio dependentes de abstrações externas ao modelo
- preferir nomes orientados a capacidade, como `Clock` e `UuidGenerator`

`Tecnofit\\PixWithdrawal\\Core\\Infrastructure\\Repository`

- contratos de persistência consumidos pelos casos de uso
- interfaces nomeadas com sufixo `Repository`

`Tecnofit\\PixWithdrawal\\Core\\Infrastructure\\Contract`

- contratos técnicos do núcleo para mensageria, notificação e integrações abstratas

`Tecnofit\\PixWithdrawal\\Core\\Shared\\Contract`

- contratos transversais reutilizáveis por Application, Domain e Infrastructure

`Tecnofit\\PixWithdrawal\\Core\\Shared\\Exception`

- exceções base compartilhadas por todo o núcleo

`Tecnofit\\PixWithdrawal\\Core\\Shared`

- tipos transversais concretos, como `Result`

## Regras de Organização

- `Application` orquestra, mas não concentra regra de negócio rica
- `Domain` não conhece adapters, providers, plugins nem framework
- `Infrastructure` dentro do `Core` define apenas abstrações; implementações concretas ficam fora do `Core`
- `Shared` existe para reduzir duplicação sem virar depósito genérico
- novas classes devem entrar primeiro no namespace mais específico disponível
