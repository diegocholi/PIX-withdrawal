# CLI Diagnostics

Comandos e componentes de diagnostico operacional da linha de comando.

Regras:

- verificar dependencias externas sem expor detalhes sensiveis desnecessarios
- retornar status observavel e codigo de saida consistente
- reutilizar factories e providers concretos em vez de abrir conexoes ad hoc
- checks concretos devem retornar resultado tipado no proprio namespace `Diagnostic`
- checks agregadores, como `system:check:all`, devem apenas orquestrar checks canonicos existentes e consolidar resumo observavel sem duplicar a logica de conectividade
- envelopes agregados de diagnostico devem reutilizar `DiagnosticCheckResult` e `DiagnosticReport` para manter status, resumo e payload final consistentes entre comandos CLI
- validacoes de configuracao, como `app:config:validate`, devem depender de objetos tipados e bindings registrados no container, nao de leitura manual dispersa de variaveis de ambiente
