# HTTP Adapter

Convencao base da camada HTTP da aplicacao.

Responsabilidades:

- expor contratos HTTP sem carregar regra de negocio do saque
- traduzir request e response para contratos do core
- aplicar middleware, serializacao e tratamento de erros do transporte

Mapa inicial:

- `Controller`: endpoints e acoplamento minimo ao framework
- `Request`: validacao e normalizacao de payload de entrada
- `Response`: serializacao de sucesso e erro
- `Mapper`: traducao entre HTTP e core
- `Middleware`: preocupacoes transversais do transporte HTTP
- `Exception`: serializacao e handlers especificos do adapter HTTP
- `Health`: respostas utilitarias de saude e prontidao
- `Route`: suporte a organizacao de rotas; o registro Hyperf continua em `config/routes.php`
