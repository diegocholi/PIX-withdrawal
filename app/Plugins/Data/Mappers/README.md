# Data Mappers

Mapeadores entre entidades do dominio e registros persistidos.

Responsabilidades:

- converter agregados do core para payloads persistiveis
- reconstruir entidades a partir de linhas do banco
- manter o shape SQL fora dos casos de uso
- usar `RecordMapper` como contrato canonico de traducao entre dominio e persistencia
