# Command

DTOs de intencao de escrita consumidos pelos casos de uso.

Convencoes:

- contratos de escrita nomeados com sufixo `Command`
- DTOs concretos de escrita nomeados com sufixo `Input`
- devem carregar apenas os dados necessarios ao caso de uso
- devem implementar o contrato transversal de rastreio com `correlationId()` e `traceMetadata()`
- devem ser `readonly` e implementar serializacao previsivel com `toArray()`
