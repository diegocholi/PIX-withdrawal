# Data

Camada concreta de persistencia do sistema.

Regras:

- pode depender de `Core` e `Providers`
- implementa persistencia MySQL, mapeamento e transacao concreta
- nao deve mover regra de negocio para SQL ou para o framework

Namespaces canonicos iniciais:

- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Bootstrap`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Config`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Mappers`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Migrations`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Queries`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Repositories`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Seeds`
- `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Transactions`
