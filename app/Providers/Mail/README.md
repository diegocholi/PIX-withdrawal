# Providers Mail

Integracoes concretas de notificacao por e-mail.

Responsabilidades:

- enviar mensagens SMTP
- montar notificacoes desacopladas
- isolar dependencias de transporte de e-mail

Componentes atuais:

- `SmtpWithdrawMailer` como provider SMTP concreto
- `SmtpMessage` como contrato imutavel da mensagem enviada
- `SmtpSendFailurePolicy` como politica minima para logar falhas e classificar erros de transporte retryable
- `WithdrawNotificationTemplate` como template textual canonico para notificacao de saque
- `WithdrawNotificationSubjectTemplate` como assunto canonico da notificacao de saque
- `WithdrawNotificationRenderer` como renderer do corpo da notificacao a partir do evento final de saque
- `WithdrawNotificationDispatch` e `WithdrawNotificationDispatcher` como contrato e orquestrador desacoplado do envio
- `WithdrawNotificationKafkaMessageMapper` e `WithdrawNotificationKafkaHandler` como adaptadores para acionamento assincrono via Kafka
- `NativeSmtpConnectionFactory` e `NativeSmtpConnection` para transporte socket com suporte a timeout
