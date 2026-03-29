# Data Mapping Strategy

## Objetivo

Definir o padrão de tradução entre entidades do domínio e registros
persistidos pelo plugin `Plugins\\Data`.

## Contrato base

- Mappers concretos do plugin implementam `Tecnofit\\PixWithdrawal\\Plugins\\Data\\Mappers\\RecordMapper`.
- O contrato expõe `toRecord(object $entity): array` para persistência e `toDomain(array $record): object` para reconstituição.
- Cada mapper concreto continua orientado a um agregado específico, como `AccountRecordMapper` ou `AccountWithdrawRecordMapper`.

## Regras de traducao

- IDs e chaves de correlação permanecem como `string` sem transformações semânticas.
- Enums de domínio devem ser persistidos pelo `value` do enum backed.
- `Money` deve ser persistido como string decimal com duas casas, usando `Money::toDecimal()` e `Money::fromDecimal()`.
- `DateTimeImmutable` deve ser persistido em UTC, em colunas `DATETIME(6)`, e reconstituído como imutável.
- `ScheduleAt` usa a mesma representação persistida de data e hora, mas a reconstituição deve depender de um `Clock` explícito do domínio.
- `PixKey` deve ser persistida em campos separados para tipo e valor, preservando o valor já normalizado pelo value object.
- Campos opcionais do domínio devem ser mapeados para `null` de forma explícita, sem strings vazias artificiais.
- Conversões repetidas de `Money` e `DateTimeImmutable` devem ser reutilizadas por helpers dedicados no mesmo namespace de mappers.

## Limites de responsabilidade

- Mappers não executam IO, query, transação nem regra de concorrência.
- Validação de shape persistido pode acontecer no mapper quando necessária para evitar reconstituição inconsistente.
- Repositories coordenam consulta e persistência; mappers apenas traduzem o shape entre domínio e banco.
