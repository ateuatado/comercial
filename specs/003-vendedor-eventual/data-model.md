# Modelo conceitual de dados

## Princípios

- Autoria, condução, carteira, contrato e reconhecimento são dimensões distintas.
- Eventos de auditoria são acrescentados; não são sobrescritos.
- Conteúdo publicado é versionado.
- Dados públicos de CNPJ mantêm fonte e instante da consulta.
- Dados sensíveis têm retenção e acesso próprios.

## Entidades

### Employee

Identidade funcional fornecida pelo diretório corporativo.

Campos essenciais: `employee_id`, nome, lotação, situação funcional, perfis de
acesso e instante da última sincronização.

No piloto externo, a origem pode ser `demo` e os dados devem ser exclusivamente
fictícios. O vínculo com o usuário de autenticação não transforma o empregado em
vendedor da carteira existente.

### Application

Capacidade funcional disponibilizável no SPIV, identificada por código estável,
como `vendedor_eventual`. Possui estado próprio e não equivale a grupo de perfil.

### EmployeeEntitlement

Concessão de acesso de um empregado a uma aplicação ou capacidade.

Campos essenciais: empregado, aplicação, capacidade, origem (`administrador`,
`campanha` ou `sistema`), campanha opcional, início, término, estado, concedente,
justificativa, revogação e timestamps.

Concessões originadas por campanha exigem campanha e término. A efetividade é
calculada no servidor e depende também da situação funcional, feature flag,
estado e vigência da campanha e ausência de suspensão administrativa.

### Campaign

Define uma edição do piloto ou programa.

Campos: identificador, nome, modalidade (`demonstrativa` ou futura modalidade
autorizada), início, fim, estado, catálogo publicado, treinamento, termos,
políticas de sessão e critérios de reconhecimento.

Estados: `rascunho`, `publicada`, `ativa`, `suspensa`, `encerrada`, `arquivada`.

### Enrollment

Adesão voluntária do empregado a uma campanha.

Campos: campanha, empregado, versão dos termos, capacitação, resultado, validade,
data de adesão e motivo de suspensão.

Estados: `iniciada`, `em_capacitacao`, `habilitada`, `pausada`, `expirada`,
`suspensa`, `encerrada`.

### ProductVersion

Versão publicável de uma solução comercial.

Campos: nome simples, nome oficial, problema resolvido, público, benefícios,
restrições, requisitos, documentos, roteiro, perguntas frequentes, materiais,
regras de recomendação e vigência.

### QuestionnaireVersion

Conjunto imutável de perguntas, alternativas, ramificações e regras de
recomendação. Uma oportunidade referencia exatamente a versão utilizada.

### CustomerOrganization

Empresa identificada por CNPJ, incluindo MEI.

Campos: CNPJ normalizado, razão social, nome fantasia, situação, natureza
jurídica, CNAE, porte, endereço oficial e metadados da fonte.

### CustomerRepresentative

Pessoa autorizada a fornecer dados e celebrar a contratação.

Dados pessoais ficam segregados e associados às finalidades de consentimento.

### Opportunity

Unidade central da jornada comercial.

Campos: `correlation_id`, CNPJ, campanha, originador, condutor atual, canal de
origem, instante do contato, estado, versão do questionário e modo de criação.

Estados principais:

`rascunho_offline` → `registrada` → `diagnostico` → `interesse_confirmado` →
`contratacao_iniciada` → `aguardando_cliente` → `aguardando_assinatura` →
`contrato_registrado` → `ativada` → `primeiro_uso` → `pos_venda` → `concluida`

Saídas alternativas: `sem_interesse`, `inadequada`, `expirada`, `cancelada`,
`duplicada`, `falha_tecnica`.

### OpportunityParticipant

Relaciona pessoas à oportunidade com papel e período: `originador`, `condutor`,
`colaborador`, `especialista`, `responsavel_carteira`, `pos_venda`, `decisor`.

O papel `originador` não é substituído por transferência operacional.

### InteractionEvent

Linha do tempo imutável.

Campos: identificador, oportunidade, tipo, autor, instante real, instante de
recebimento, canal, dispositivo, origem online/offline, versão do conteúdo e
dados mínimos do evento.

### ConsentRecord

Campos: oportunidade, representante, finalidade, texto e versão, canal, instante,
prova, validade e eventual revogação.

Finalidades iniciais: `registro_interesse`, `contato_comercial`,
`tratamento_contratual`.

### PortfolioRequest

Solicitação de inclusão do cliente em carteira, independente do contrato.

Estados: `provisoria`, `em_analise`, `mais_informacoes`, `aprovada`, `rejeitada`,
`conflito`, `cancelada`.

### PortfolioReservation

Reserva temporária originada pela primeira solicitação válida. Não bloqueia a
venda e não confirma autoria.

### Recommendation

Registra produto sugerido, regras acionadas, explicação, se foi apresentado e a
resposta do cliente.

### DocumentRequirement

Regra versionada que relaciona produto, natureza jurídica, etapa, documento e
obrigatoriedade.

### CustomerDocument

Metadados e referência protegida do arquivo. Registra emissor, capturador,
finalidade, validade, verificação, retenção e destino. O arquivo não deve ser
armazenado em galeria pessoal.

### ContractLink

Relaciona oportunidade ao contrato oficial.

Campos: sistema de origem, identificador oficial, CNPJ, produto, estado, método
de vínculo, confiança, evidências e instante de confirmação.

Métodos: `correlation_id`, `integracao_direta`, `reconciliacao`, `validacao_manual`.

Estados: `nao_localizado`, `provavel`, `confirmado`, `conflitante`, `rejeitado`.

### CustomerPortalSession

Token opaco e temporário para uma oportunidade e conjunto mínimo de ações.
Armazena hash do token, expiração, uso, revogação e mecanismo adicional de
validação. Nunca concede acesso ao ambiente interno.

### SupportRequest

Solicitação de orientação, colaboração ou transferência. Registra tipo,
especialidade, responsável, SLA, estado e resultado.

### PostSaleTask

Tarefa de ativação, primeiro uso, suporte, satisfação ou nova oportunidade.

### RecognitionAssessment

Resultado técnico e demonstrativo de regras versionadas.

Estados: `nao_aplicavel`, `em_observacao`, `criterio_atingido`,
`validacao_pendente`, `validado`, `nao_elegivel`.

Não contém valor monetário nem ordem de pagamento.

### AuditEvent

Registro transversal para publicação, acesso documental, mudança de permissão,
decisão de carteira, reconciliação e exportação.

## Relacionamentos essenciais

- Uma campanha possui muitas versões de produto e uma versão ativa de
  treinamento e termos.
- Um empregado possui muitas adesões; uma adesão pertence a uma campanha.
- Uma empresa pode possuir muitas oportunidades, contratos e solicitações de
  carteira.
- Uma oportunidade possui um originador e pode possuir muitos participantes.
- Uma oportunidade pode ter várias solicitações, mas só uma decisão vigente de
  carteira por escopo definido.
- Uma oportunidade pode se relacionar a zero, um ou mais contratos; vínculos
  ambíguos exigem análise.
- Consentimentos e documentos pertencem a finalidades específicas.

## Invariantes

1. `correlation_id` é único e imutável.
2. Todo evento possui autor ou origem de sistema identificada.
3. Transferência não altera o originador.
4. Contrato confirmado não implica inclusão automática em carteira.
5. Inclusão aprovada não implica contrato celebrado.
6. Reconhecimento demonstrativo nunca contém promessa ou valor financeiro.
7. Uma recomendação referencia versões publicadas de produto e questionário.
8. Sincronização offline é idempotente por identificador local de operação.
9. Documento e consentimento não podem ser reutilizados para finalidade distinta
   sem base válida.
10. Autenticação bem-sucedida não concede aplicação sem autorização efetiva.
11. Encerramento, suspensão ou fim da vigência da campanha cessa imediatamente
    suas concessões, preservando registros históricos.
12. Identidades `demo` não podem autenticar quando o modo demonstrativo estiver
    desabilitado.
