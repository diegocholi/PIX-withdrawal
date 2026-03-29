# Exception

Excecoes de regra de negocio e invariantes do dominio.

Convencoes:

- especializar `CoreException` por conceito de negocio
- usar `errorCode` estavel e `context` util para diagnostico
