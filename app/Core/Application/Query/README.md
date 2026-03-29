# Query

Tipos de leitura e filtros internos do core.

Convencoes:

- contratos de leitura nomeados com sufixo `Query`
- DTOs concretos de leitura nomeados com sufixo `Input`
- representam intencao de consulta, nao detalhes de transporte
- devem ser `readonly` e implementar serializacao previsivel com `toArray()`
