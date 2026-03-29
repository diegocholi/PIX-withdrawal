# HTTP Routes

Suporte a organizacao logica das rotas HTTP.

Regra:

- o bootstrap oficial das rotas Hyperf permanece em `config/routes.php`
- classes auxiliares futuras deste namespace nao substituem o arquivo de bootstrap do framework
- `config/routes.php` deve delegar o registro para um registrador explicito do adapter HTTP
