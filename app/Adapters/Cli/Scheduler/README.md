# CLI Scheduler

Componentes responsaveis por localizar e promover tarefas agendadas do dominio.

Regras:

- manter batch e dry-run fora da regra de negocio central
- separar localizacao de elegiveis, promocao e publicacao
- preservar execucao segura e idempotente em cenarios repetidos
- a orquestracao canonica do scheduler de saque vive em `ScheduledWithdrawScheduler`
