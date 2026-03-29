# CLI Commands

Comandos concretos registrados no runtime CLI.

Regras:

- manter cada comando pequeno e orientado a um fluxo operacional claro
- traduzir argumentos e opcoes para contratos internos tipados
- delegar execucao para casos de uso, scheduler ou worker dedicados
- comandos de bootstrap operacional da aplicacao podem delegar para planos explicitos do plugin quando o fluxo precisar preparar schema ou runtime antes do servidor subir
- comandos de seed de bootstrap devem ser idempotentes e delegar para seeders rastreados no plugin de dados
- reutilizar `CliOptionName` e `CliExitCode` antes de introduzir strings e inteiros soltos
- comandos do scheduler devem delegar a orquestracao para tipos em `Adapters/Cli/Scheduler`
