# Data Queries

Consultas concretas de leitura e suporte operacional.

Responsabilidades:

- materializar queries orientadas a uso do scheduler, worker e API
- manter leitura especializada fora dos repositories quando o contrato pedir consulta dedicada
- evitar SQL duplicado espalhado por varios componentes
