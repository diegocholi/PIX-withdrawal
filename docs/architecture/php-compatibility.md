# PHP Compatibility

## Alvo de Plataforma

- PHP alvo do projeto: `8.3`
- imagem base do runtime: `php:8.3-cli-bookworm`
- linha do framework: `Hyperf 3.1`

## Extensões Mínimas

O bootstrap atual considera obrigatórias:

- `json`
- `openssl`
- `pcntl`
- `pdo`
- `pdo_mysql`
- `redis`
- `sockets`
- `swoole`

## Decisões de Compatibilidade

- o `composer.json` fixa `config.platform.php` em `8.3.0` para evitar resolução
  de dependências baseada no PHP do container de build
- as extensões obrigatórias foram declaradas no manifesto para falhar cedo quando
  o ambiente alvo estiver incompleto
- o `Dockerfile` instala as extensões necessárias para HTTP, CLI, banco, redis e
  runtime coroutine do Hyperf

## Observações

- instalações via imagem oficial `composer` podem exigir `--ignore-platform-req`
  para extensões nativas ausentes no container de build
- a validação real de runtime deve acontecer preferencialmente na imagem da aplicação
  ou via `docker compose`
