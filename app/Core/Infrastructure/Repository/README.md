# Repository

Contratos de persistencia consumidos pelos casos de uso do core.

Convencoes:

- interfaces orientadas ao agregado e ao caso de uso
- sem detalhes de SQL, ORM, tabela ou driver
- operacoes de concorrencia explicitas via metodos como `lockById`
