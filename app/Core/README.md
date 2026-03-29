# Core

Camada central da aplicacao.

Regras:

- nao depende de `Adapters`
- nao depende de `Providers`
- nao depende de `Plugins`
- concentra contratos e regra de negocio

Estrutura base:

- `Application`: casos de uso, comandos, queries e orquestracao interna
- `Domain`: entidades, value objects, enums e contratos orientados ao negocio
- `Infrastructure`: contratos tecnicos internos do core que sustentam persistencia e integracoes abstratas
- `Shared`: tipos e servicos transversais reutilizaveis pelo nucleo

Mapa inicial de namespaces:

- `Tecnofit\\PixWithdrawal\\Core\\Application\\UseCase`
- `Tecnofit\\PixWithdrawal\\Core\\Application\\Command`
- `Tecnofit\\PixWithdrawal\\Core\\Application\\Query`
- `Tecnofit\\PixWithdrawal\\Core\\Application\\Dto`
- `Tecnofit\\PixWithdrawal\\Core\\Application\\Exception`
- `Tecnofit\\PixWithdrawal\\Core\\Domain\\Entity`
- `Tecnofit\\PixWithdrawal\\Core\\Domain\\ValueObject`
- `Tecnofit\\PixWithdrawal\\Core\\Domain\\Enum`
- `Tecnofit\\PixWithdrawal\\Core\\Domain\\Service`
- `Tecnofit\\PixWithdrawal\\Core\\Domain\\Event`
- `Tecnofit\\PixWithdrawal\\Core\\Domain\\Exception`
- `Tecnofit\\PixWithdrawal\\Core\\Domain\\Contract`
- `Tecnofit\\PixWithdrawal\\Core\\Infrastructure\\Repository`
- `Tecnofit\\PixWithdrawal\\Core\\Infrastructure\\Contract`
- `Tecnofit\\PixWithdrawal\\Core\\Shared\\Contract`
- `Tecnofit\\PixWithdrawal\\Core\\Shared\\Exception`
