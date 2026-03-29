# Providers Factories

Factories de construcao dos providers concretos.

Responsabilidades:

- encapsular instanciação externa
- encapsular tambem resolucoes simples para manter o registry declarativo e consistente
- falhar cedo em configuracao invalida
- evitar espalhar detalhes de bootstrap
- materializar objetos tipados de configuracao a partir de `ProviderConfigProvider`
