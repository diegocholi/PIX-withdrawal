# CLI Adapter

Convencao base da camada CLI da aplicacao.

Responsabilidades:

- expor comandos operacionais sem carregar regra de negocio financeira
- reutilizar casos de uso, providers e plugins ja registrados no container
- manter contratos previsiveis de entrada, saida e codigo de retorno

Mapa inicial:

- `Bootstrap`: preparo do runtime de console e resolucao compartilhada
- `Command`: comandos concretos registrados no runtime CLI
- `Scheduler`: orquestracao de rotinas agendadas e batches operacionais
- `Worker`: execucao de consumers e loops cooperativos de processamento
- `Diagnostic`: comandos de validacao operacional e conectividade
- `Support`: utilitarios internos exclusivos do adapter CLI
