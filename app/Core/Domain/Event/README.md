# Event

Eventos internos emitidos a partir de mudancas relevantes de estado.

Convencoes:

- eventos internos implementam `DomainEvent`
- eventos nomeados de negocio devem ter classe dedicada quando fizerem parte do contrato do core
- devem expor `eventName`, `aggregateId`, `occurredAt`, `correlationId` e `traceMetadata`
- serializacao deve ser previsivel via `toArray()`
