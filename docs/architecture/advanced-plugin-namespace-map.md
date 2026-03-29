# Advanced Plugin Namespace Map

## Objetivo

Documentar a organização mínima do plugin `Advanced` para manter a publicação do
contrato OpenAPI e o hardening leve da borda HTTP em pontos previsíveis.

## Namespace raiz

- `Tecnofit\\PixWithdrawal\\Plugins\\Advanced`

## Namespaces canônicos

- `Tecnofit\\PixWithdrawal\\Plugins\\Advanced\\Bootstrap`
- `Tecnofit\\PixWithdrawal\\Plugins\\Advanced\\Config`
- `Tecnofit\\PixWithdrawal\\Plugins\\Advanced\\SecurityHeaders`
- `Tecnofit\\PixWithdrawal\\Plugins\\Advanced\\Swagger`

## Regras iniciais

- `Bootstrap` concentra o wiring explícito do plugin via `AdvancedPluginBindingRegistry`.
- `Config` concentra objetos tipados e leitura centralizada de configuração do OpenAPI e Swagger UI.
- `SecurityHeaders` concentra a política canônica de headers simples e o middleware de aplicação.
- `Swagger` concentra a geração da especificação OpenAPI e a renderização da página Swagger UI.
- o bootstrap HTTP continua delegado por `config/routes.php` e `Adapters\\Http\\Route\\HttpRouteRegistrar`
  mesmo quando o contrato publicado depende do plugin `Advanced`.
