# Integrações e fronteiras

## Estratégia

O SPIV é a camada de jornada, autoria e acompanhamento. Cada capacidade externa
deve operar em um dos três modos abaixo:

1. **Integração direta:** API com envio de correlação e retorno de estados.
2. **Transição autenticada:** redirecionamento preservando contexto quando
   suportado.
3. **Fluxo assistido:** roteiro e registro manual dos marcos quando não houver
   integração.

A indisponibilidade de integração não deve impedir o piloto, mas o modo usado
deve ficar visível para auditoria e métricas.

## Matriz inicial

| Capacidade | Sistema esperado | Dado mínimo | Retorno esperado | Contingência |
|---|---|---|---|---|
| Identidade do empregado | LDAP/AD corporativo | identificador funcional | situação e perfis | nenhuma autenticação pública |
| Situação funcional | fonte corporativa a validar | identificador | ativo/afastado/desligado | sincronização programada |
| Cadastro CNPJ | base local da Receita Federal | CNPJ | dados públicos e fonte | consulta posterior |
| Carteira existente | SPIV/CRM a validar | CNPJ | responsável e estado | fila de análise |
| Cadastro do cliente | Meu Correios | CNPJ e representante | cadastro/pendência | link assistido |
| Contratação | Correios Empresas | dados, produto, correlação | número e estado | transição + reconciliação |
| Assinatura | mecanismo oficial | documento e signatário | assinado/rejeitado | orientação e retomada |
| Ativação | sistema contratual | número do contrato | data e estado | consulta ou validação manual |
| Primeiro uso | sistema operacional a validar | contrato/cartão/CNPJ | evento e data | confirmação administrativa |
| E-mail | serviço corporativo | destinatário e template | envio/entrega | nova tentativa controlada |
| SMS | provedor corporativo | telefone e template | envio/entrega | e-mail ou QR |
| WhatsApp | futura conta oficial | destinatário e template | estado | não habilitar no piloto |

## Contrato de correlação

Toda chamada que permitir metadados deve receber:

- `correlation_id` da oportunidade;
- `campaign_id`;
- CNPJ normalizado;
- produto e versão;
- instante de origem;
- sistema e versão emissora.

Retornos devem incluir identificador externo, estado, instante, código de erro e
correlação recebida. Operações de criação precisam de chave de idempotência.

## Portal externo

O portal público deve usar um serviço de borda separado do ambiente interno. O
token deve ser opaco, armazenado apenas como hash e limitado a:

- uma oportunidade;
- ações explícitas;
- prazo curto;
- número de tentativas;
- validação adicional quando o risco exigir.

O portal não consulta diretamente bases internas. Ele envia comandos validados a
uma camada de serviço com listas permitidas de campos e ações.

## Offline e sincronização

O pacote offline pode conter somente:

- catálogo e questionário publicados;
- identidade mínima do empregado;
- campanha e permissões com validade;
- rascunhos criados no dispositivo.

Na sincronização, cada operação envia identificador local imutável. O servidor
responde com estado definitivo, conflitos e `correlation_id`, sem criar registros
duplicados em reenvios.

## Reconciliação de contratos

Ordem de evidência:

1. correlação devolvida pelo sistema oficial;
2. identificador externo capturado durante a transição;
3. combinação CNPJ + produto + representante + janela de tempo;
4. validação humana.

Correspondências ambíguas nunca são promovidas automaticamente a confirmadas.

## Segurança mínima

- Login interno bloqueado fora das redes autorizadas, salvo mecanismo remoto
  oficial.
- Autorização no servidor; esconder botões não é controle de acesso.
- Segregação de dados pessoais e documentos.
- Templates de comunicação versionados.
- Segredos e tokens nunca incluídos em URLs persistentes ou logs.
- Auditoria de leitura e download de documentos.
- Limites de requisição e detecção de abuso no portal externo.

## Questões para descoberta técnica

- Quais APIs existem para Meu Correios e Correios Empresas?
- Há suporte a SSO, deep link ou passagem de contexto?
- Qual campo pode carregar o `correlation_id`?
- Como consultar ativação e primeiro uso?
- Qual serviço corporativo envia SMS?
- Quais redes, VPNs e dispositivos são considerados autorizados?
- Qual padrão corporativo rege retenção e armazenamento de documentos?
