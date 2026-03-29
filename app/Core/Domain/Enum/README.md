# Enum

Enums que representam estados e conjuntos fechados do dominio.

Convencoes:

- usar `backed enum` quando o valor precisar atravessar persistencia, logs ou contratos
- enums devem centralizar helpers sem espalhar strings magicas
- expansoes futuras devem acontecer no enum existente, preservando o contrato publico
