# Contract

Contratos tecnicos internos do core para integracoes abstratas.

Convencoes:

- contratos desta camada descrevem capacidades tecnicas necessarias ao core
- implementacoes concretas ficam fora do `Core`
- dispatch de evento interno deve depender de `DomainEvent`, nunca de Kafka ou fila concreta
