# Runbook de implantação — demonstração interna do Vendedor Eventual

## Objetivo e limites

Este runbook publica a entrega demonstrativa interna da feature
`003-vendedor-eventual`. Ele não autoriza uso de identidades ou dados reais e
não substitui as validações corporativas pendentes de LDAP, rede, privacidade e
retenção.

Na demonstração externa à rede corporativa, use exclusivamente as identidades
fictícias cadastradas com origem `demo`. Em produção ou homologação corporativa,
o modo demo deve permanecer desligado.

## Diretórios canônicos

- Desenvolvimento local: `C:\xampp\htdocs\spiv`.
- DocumentRoot local: `C:\xampp\htdocs\spiv\public`.
- URL local: `https://spiv.test`.
- VPS: resolver e registrar o diretório real da aplicação como `<APP_ROOT>`
  antes de executar qualquer comando. Nunca executar comandos a partir de
  `public`.

## Portões de entrada

Todos os itens abaixo precisam estar atendidos:

- árvore Git limpa e commit de release presente no GitHub;
- suíte automatizada integral aprovada;
- migrations revisadas e sem pendências no ambiente local;
- backup verificável do banco de dados do ambiente de destino;
- PHP 8.2 ou superior, extensões e acesso ao PostgreSQL validados no VPS;
- `.env` do VPS preservado fora do Git e revisado;
- responsável pela demonstração e janela de implantação definidos;
- plano de retorno comunicado antes da ativação.

## Configuração segura da demonstração

O arquivo versionado `env` contém padrões seguros. Os segredos pertencem apenas
ao `.env` do ambiente e nunca devem ser commitados.

Para demonstração com dados fictícios:

```dotenv
CI_ENVIRONMENT = production
SPIV_IDENTITY_PROVIDER = demo
SPIV_DEMO_LOGIN_ENABLED = true
SPIV_DEMO_PASSWORD = "<SEGREDO_FORA_DO_REPOSITORIO>"
VENDOR_EVENTUAL_ENABLED = false
```

Mantenha `VENDOR_EVENTUAL_ENABLED=false` durante a publicação. A trava global
só deve ser ligada após os smoke tests administrativos. O cadastro da aplicação
também deve permanecer desabilitado até esse momento.

## Preparação local

Executar a partir de `C:\xampp\htdocs\spiv`:

```powershell
git status --short --branch
git fetch origin
git merge-base --is-ancestor origin/master HEAD
php spark migrate:status
composer test
```

Confirmar que o Apache publica a mesma árvore e validar:

- login e logout de uma identidade fictícia;
- acesso negado para identidade sem concessão;
- administração de aplicação e campanha;
- capacitação e aceite;
- criação e reenvio idempotente de oportunidade;
- consulta de CNPJ, diagnóstico, alertas de duplicidade e solicitação de
  carteira;
- ausência de dados reais na demonstração.

## Publicação pelo GitHub

1. Atualizar o branch da feature com `origin/master` e repetir os testes.
2. Revisar o diff e integrar o branch aprovado ao `master`.
3. Fazer push do `master` para o GitHub.
4. Registrar o hash exato do commit aprovado e, se adotado pelo projeto, criar
   uma tag de release.

Não copiar arquivos manualmente para o VPS. O repositório remoto é a fonte da
implantação.

## Implantação no VPS

Antes da execução, substituir `<APP_ROOT>` pelo caminho canônico confirmado no
servidor e `<RELEASE_COMMIT>` pelo hash aprovado.

```bash
cd <APP_ROOT>
git status --short --branch
git fetch origin
git checkout master
git pull --ff-only origin master
git rev-parse HEAD
composer install --no-dev --prefer-dist --optimize-autoloader
php spark migrate --all
php spark migrate:status
```

O resultado de `git rev-parse HEAD` deve ser igual a `<RELEASE_COMMIT>`. Se a
árvore do VPS não estiver limpa, interromper a implantação e investigar; não
descartar alterações automaticamente.

## Smoke tests antes da ativação

Com a trava global ainda desligada:

1. verificar página inicial, login e área administrativa;
2. confirmar que a administração mostra a trava global desligada;
3. confirmar acesso negado ao portal do Vendedor Eventual;
4. verificar logs da aplicação e do servidor web;
5. confirmar que as tabelas e dados demonstrativos esperados estão presentes.

Depois, ligar `VENDOR_EVENTUAL_ENABLED=true`, reiniciar somente os serviços que
o ambiente exigir e:

1. habilitar a aplicação no painel administrativo;
2. conceder acesso somente às personas fictícias escolhidas;
3. executar a jornada completa com uma campanha demonstrativa;
4. verificar auditoria, idempotência e ausência de erros nos logs.

## Monitoramento

Durante a janela da demonstração, acompanhar:

- falhas de autenticação e autorização;
- respostas HTTP 4xx e 5xx inesperadas;
- erros de banco e migrations;
- falhas de sincronização offline;
- criação duplicada de oportunidades;
- crescimento anormal da fila local ou dos logs.

Não registrar senhas, tokens, documentos ou dados pessoais nos logs de
implantação.

## Retorno seguro

Na primeira anomalia material:

1. definir `VENDOR_EVENTUAL_ENABLED=false`;
2. desabilitar a aplicação no painel, se o painel estiver disponível;
3. preservar logs e evidências para diagnóstico;
4. retornar o código ao commit anterior apenas por procedimento Git aprovado;
5. restaurar banco somente se houver dano confirmado e com autorização do
   responsável pelo ambiente.

Migrations já aplicadas não devem ser revertidas automaticamente. Prefira uma
correção compatível para frente, especialmente quando houver dados gravados.

## Registro de conclusão

Ao final, registrar:

- data, janela e responsável;
- commit implantado;
- resultado do backup;
- resultado das migrations e testes;
- configuração efetiva das travas, sem incluir segredos;
- personas usadas;
- incidentes ou limitações observadas;
- decisão de manter ativo ou executar retorno.
