# ValueObject

Tipos imutaveis orientados a regra de negocio.

Convencoes:

- value objects devem ser `final readonly`
- devem validar invariantes na origem
- quando o valor circular em persistencia ou logs, expor serializacao previsivel
- quando dependerem de um conjunto fechado de tipos, devem colaborar com enum do dominio
