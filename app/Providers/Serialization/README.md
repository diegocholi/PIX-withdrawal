# Providers Serialization

Serializacao concreta ligada aos providers.

Responsabilidades:

- transformar payloads de transporte
- implementar a serializacao segura e o mascaramento definidos pelo core
- normalizar recursivamente datas, enums e DTOs serializaveis antes de publicar ou logar payloads
- manter contratos de serializacao previsiveis
- evitar duplicacao de formatos externos
