# CLI Workers

Componentes responsaveis por inicializar e controlar loops de worker do runtime CLI.

Regras:

- encapsular ciclo de vida, sinais e shutdown cooperativo
- delegar processamento de mensagem para handlers e casos de uso dedicados
- manter parametros operacionais explicitos e testaveis
- materializar opcoes de runtime e resultado do loop em contratos dedicados, evitando arrays soltos
