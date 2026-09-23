# Proteção CSRF do SPIV

## Estado e decisão

Desde 2026-09-08, o filtro CSRF do CodeIgniter 4 é global para todas as
requisições mutáveis da aplicação. Não há exceções por rota no estado atual.

A proteção usa sessão (`csrfProtection = 'session'`) e mantém o mesmo token
durante a sessão (`regenerate = false`). A não regeneração a cada envio é
intencional: várias telas executam sucessivas operações AJAX sem recarregar a
página. O token continua imprevisível, associado à sessão e validado em cada
POST; a sessão autenticada é renovada pelo fluxo de login do Shield.

## Convenções obrigatórias

- Todo formulário HTML com `method="post"` deve conter `<?= csrf_field() ?>`.
- `fetch` e `XMLHttpRequest` mutáveis e de mesma origem recebem automaticamente
  o cabeçalho configurado pelo CodeIgniter através de `public/assets/js/csrf.js`.
- Requisições GET, HEAD e OPTIONS não recebem o cabeçalho.
- Requisições para outra origem nunca recebem o token.
- Novos layouts devem incluir `csrf_meta('spiv-csrf')` e `assets/js/csrf.js`, ou
  implementar proteção equivalente explicitamente.
- Uma integração externa que não possa fornecer CSRF não deve ganhar exceção
  genérica. Deve usar autenticação própria, rota dedicada e exceção mínima,
  documentada e testada.

## Pontos alterados

- `app/Config/Filters.php`: habilitação global do filtro.
- `app/Config/Security.php`: token estável durante a sessão.
- `app/Views/layouts/main.php`: publicação segura do token em meta tag.
- `public/assets/js/csrf.js`: cabeçalho automático para AJAX mutável.
- Views com formulários POST: campo oculto explícito.
- `tests/unit/CsrfProtectionTest.php`: contrato automatizado da proteção.

## Verificação de regressão

Antes de concluir uma alteração relacionada a formulários ou APIs internas:

1. executar `php spark routes` e confirmar o filtro global `csrf`;
2. executar `vendor/bin/phpunit tests/unit/CsrfProtectionTest.php`;
3. verificar login, um formulário administrativo, um formulário de vendedor e
   uma ação AJAX;
4. confirmar que POST sem token ou com token inválido é rejeitado.

O erro preexistente da suíte completa na migration de scoring com SQLite não é
uma exceção de CSRF e deve ser tratado separadamente.

## Registro de validação — 2026-09-08

- Testes focados de CSRF, autorização administrativa e acesso a aplicações:
  **14 testes e 175 assertions, todos aprovados**.
- O teste de integração comprova rejeição sem token, rejeição com token inválido
  e aceitação com token válido.
- A auditoria automatizada encontrou tokens em todos os formulários POST.
- Sintaxe PHP validada nos arquivos alterados.
- A suíte completa executou 39 testes e chegou a 216 assertions, mas terminou
  com quatro erros não relacionados ao CSRF: incompatibilidade `NOW()`/SQLite
  na migration de scoring, dois estados de fixture da jornada eventual e uma
  tabela de carteira duplicada no teste de reserva. Esses débitos não foram
  alterados nesta entrega para preservar o escopo.
