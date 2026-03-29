# Documentation Index

## Objetivo

Centralizar a navegação da documentação de entrega do case e registrar a
convenção mínima dos arquivos deste módulo.

## Estrutura

- `docs/api`: contratos HTTP públicos e políticas da borda
- `docs/architecture`: organização modular, fluxos, modelo de dados e decisões
- `docs/operations`: setup, execução, checks e troubleshooting operacional
- `docs/checklists`: listas finais de validação
- `docs/delivery`: artefatos para submissão e reprodução em ambiente limpo

## Convenção adotada

- idioma padrão: português técnico
- nomes de arquivo: `kebab-case`
- um arquivo por assunto principal
- documentos de operação devem privilegiar comandos reproduzíveis em Docker
- documentos de arquitetura devem referenciar classes, pastas e estados reais do
  código
- checklists devem ser objetivos e orientados à verificação manual

## Navegação rápida

- [README principal](../README.md)
- [Operação via Docker Compose](operations/docker-compose.md)
- [Execução dos runtimes](operations/runtime-execution.md)
- [Checks rápidos](operations/quick-checks.md)
- [Troubleshooting](operations/troubleshooting.md)
- [Arquitetura assíncrona](architecture/async-withdraw-processing.md)
- [Modelo de dados e estados](architecture/data-model-and-states.md)
- [Decisões técnicas](architecture/technical-decisions.md)
- [Checklist final](checklists/final-delivery-checklist.md)
- [Validação em ambiente limpo](delivery/clean-environment-validation.md)
- [Pacote final](delivery/submission-package.md)
