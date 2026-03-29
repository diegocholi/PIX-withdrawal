# Providers Kafka Payload

Contratos canonicos de payload publicados pelo modulo em topicos Kafka.

Responsabilidades:

- formalizar o envelope estavel das mensagens Kafka em `KafkaEventPayload`
- explicitar o shape do bloco `payload` por evento com classes dedicadas
- evitar que o mapper publique estruturas implicitas ou divergentes por evento
