# Providers Kafka

Integracoes concretas com Kafka.

Responsabilidades:

- publicar e consumir mensagens
- traduzir eventos internos para transporte
- formalizar o contrato canonico do envelope Kafka em `Kafka/Payload/KafkaEventPayload`
- explicitar o shape do bloco `payload` por evento com tipos dedicados em `Kafka/Payload`
- conectar o contrato `DomainEventDispatcher` do core ao producer Kafka por `KafkaDomainEventDispatcher`
- concentrar retry e falhas operacionais do broker
- encapsular registros publicados por `KafkaProducerRecord` e a implementacao concreta inicial em `KafkaMessageProducer`
- tratar falhas de publicacao por uma politica dedicada, hoje `KafkaPublishFailurePolicy`, com erro explicito via `KafkaPublishException`
- tratar falhas de consumo por uma politica dedicada, hoje `KafkaConsumeFailurePolicy`, incluindo payload invalido e erro explicito via `KafkaConsumeException`
