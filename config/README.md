# Config

Convencao inicial para arquivos de configuracao:

- `config.php` para bootstrap global do Hyperf
- `<dominio>.php`
- `<dominio>.local.php`

Exemplos esperados:

- `app.php`
- `server.php`
- `databases.php`

Estrutura base atual:

- `config/config.php`
- `config/routes.php`
- `config/autoload/annotations.php`
- `config/autoload/app.php`
- `config/autoload/commands.php`
- `config/autoload/databases.php`
- `config/autoload/dependencies.php`
- `config/autoload/exceptions.php`
- `config/autoload/kafka.php`
- `config/autoload/logger.php`
- `config/autoload/mail.php`
- `config/autoload/middlewares.php`
- `config/autoload/providers.php`
- `config/autoload/server.php`

Regra para HTTP:

- o bootstrap de rotas do Hyperf deve permanecer centralizado em `config/routes.php`
- middlewares globais HTTP devem ser declarados em `config/autoload/middlewares.php`
- handlers de excecao HTTP devem ser declarados em `config/autoload/exceptions.php`

Regra para Providers:

- a composicao de ambiente do modulo deve ficar em `config/autoload/providers.php`
- a leitura centralizada dessa configuracao deve acontecer via `Providers\\Config\\ProviderConfigProvider`
- `config/autoload/kafka.php` e `config/autoload/mail.php` devem derivar defaults por ambiente a partir do ambiente normalizado de providers
- configuracoes de dominio devem manter shape estavel para futura materializacao em objetos tipados, como `Providers\\Config\\KafkaConfig`
- validacoes criticas de configuracao devem acontecer em validators dedicados antes do uso de providers externos, como `Providers\\Config\\KafkaConfigValidator`
