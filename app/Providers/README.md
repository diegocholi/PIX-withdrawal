# Providers

Camada de integracoes concretas do negocio.

Regras:

- pode depender de `Core`
- implementa contratos e integracoes externas
- nao deve mover regra central para infraestrutura

Namespaces canonicos iniciais:

- `Tecnofit\\PixWithdrawal\\Providers\\Bootstrap`
- `Tecnofit\\PixWithdrawal\\Providers\\Config`
- `Tecnofit\\PixWithdrawal\\Providers\\Factories`
- `Tecnofit\\PixWithdrawal\\Providers\\Kafka`
- `Tecnofit\\PixWithdrawal\\Providers\\Logging`
- `Tecnofit\\PixWithdrawal\\Providers\\Mail`
- `Tecnofit\\PixWithdrawal\\Providers\\Metrics`
- `Tecnofit\\PixWithdrawal\\Providers\\Runtime`
- `Tecnofit\\PixWithdrawal\\Providers\\Serialization`
- `Tecnofit\\PixWithdrawal\\Providers\\Support`
