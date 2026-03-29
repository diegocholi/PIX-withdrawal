# CLI Bootstrap

Classes que preparam o runtime de console, o container e o registro dos comandos.

Regras:

- centralizar bootstrap e wiring compartilhado do adapter CLI
- evitar logica de negocio e detalhes de transporte dentro dos comandos
- delegar configuracao transversal para providers e plugins quando existir contrato pronto
- expor um ponto canonico do adapter CLI para os entrypoints, hoje `CliRuntimeBootstrap`
- centralizar a lista de comandos registrados em `CliCommandRegistry`
