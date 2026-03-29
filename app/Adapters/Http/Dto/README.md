# HTTP DTO

Tipos publicos serializaveis do adapter HTTP.

Regras:

- contratos publicos reutilizaveis da API vivem neste modulo
- o subnamespace `Public` concentra payloads expostos externamente
- DTOs do adapter HTTP representam formato de transporte, nao contratos internos do core
