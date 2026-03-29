# HTTP Adapter Namespace Map

## Objetivo

Padronizar a organização inicial do adapter HTTP para manter a evolução da API
previsível e alinhada ao bootstrap do Hyperf 3.

## Namespace Raiz

- `Tecnofit\\PixWithdrawal\\Adapters\\Http`

## Convenções

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Controller`

- controllers HTTP do Hyperf
- recebem a requisição, delegam ao core e retornam a response

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Dto\\Public`

- payloads públicos serializáveis da API HTTP
- estabilizam contratos externos sem expor formatos internos do core

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Request`

- validação e normalização de payloads de entrada

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Response`

- serialização de payloads públicos de sucesso e erro

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Mapper`

- tradução entre requests e responses HTTP e DTOs do core

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Middleware`

- preocupações transversais do transporte HTTP

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Exception`

- serialização de falhas e componentes de tratamento de erro da borda HTTP

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Exception\\Handler`

- handlers específicos registrados no bootstrap HTTP

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Health`

- componentes utilitários de healthcheck e readiness

`Tecnofit\\PixWithdrawal\\Adapters\\Http\\Route`

- organização lógica auxiliar das rotas quando necessário
- o registro oficial das rotas do Hyperf continua em `config/routes.php`
- `config/routes.php` delega para um registrador explícito do adapter HTTP

## Regras de Organização

- controllers não carregam regra de negócio financeira
- validação de transporte fica em `Request`
- serialização pública fica em `Response`
- tradução entre transporte e core fica em `Mapper`
- middleware e handlers centralizam preocupações transversais do adapter HTTP
- middlewares globais HTTP devem ser registrados em `config/autoload/middlewares.php`
- handlers de exceção HTTP devem ser registrados em `config/autoload/exceptions.php`
