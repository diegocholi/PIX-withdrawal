# Dto

Tipos de entrada e saida dos casos de uso do nucleo.

Convencoes:

- contratos de saida nomeados com sufixo `Output`
- DTOs concretos de saida nomeados com sufixo `Data`
- devem retornar somente dados necessarios ao consumidor interno
- devem ser `readonly` e serializaveis via `toArray()`
