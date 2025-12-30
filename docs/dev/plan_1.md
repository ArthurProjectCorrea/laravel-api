Perfeito, o contexto ficou **muito bem definido** 👍
Com base nisso, vou montar um **plano de implementação focado 100% em autenticação**, **pensado como “Auth Central”** para um ecossistema de aplicativos (ERP core + apps satélites), **usando apenas recursos nativos do Laravel sempre que possível**, com decisões técnicas justificadas.

> ❗ Escopo deliberadamente restrito à **autenticação**
> ❌ Sem CRUD de usuários
> ❌ Sem permissões, papéis, RBAC (entra depois)
> ❌ Sem regras de negócio de domínio

---

# 🎯 Objetivo da Autenticação

Criar um **único ponto de autenticação centralizado**, capaz de:

* Autenticar usuários internos da empresa
* Ser consumido por:

  * ERP Web
  * Aplicativo de Ponto
  * Aplicativo de Fretes
  * Futuras integrações
* Evitar:

  * Múltiplas tabelas `users`
  * Divergência de credenciais
  * Lógicas duplicadas
* Manter:

  * Segurança de nível ERP
  * Padronização
  * Evolução sem retrabalho

---

# 🧠 Conceito-chave adotado

> **Auth Server Centralizado (Laravel API)**
> Todos os apps **confiam exclusivamente** nessa API para autenticação.

* Nenhum app gerencia usuários
* Nenhum app armazena senha
* Nenhum app implementa login próprio
* Todos usam **tokens emitidos pela API**

---

# 🧱 Tecnologias e recursos do Laravel escolhidos

## 🔐 Autenticação

* **Laravel Sanctum (nativo)**

  * Token-based auth
  * Suporte natural a:

    * Web
    * Mobile
    * Desktop (Electron)
  * Controle por dispositivo/aplicação

## 🗄️ Banco de dados

* **PostgreSQL**
* Tabela `users` única
* Tabela `personal_access_tokens` (Sanctum)

## ✉️ E-mail

* **Sistema de Mail nativo do Laravel**
* **Password Reset nativo**
* Customização do fluxo (URL controlada pelo frontend)

## 🔒 Segurança

* Hashing nativo (`Hash`)
* Rate limit nativo (`ThrottleRequests`)
* Proteções CSRF (quando aplicável)
* Eventos e logs nativos

---

# 📐 Plano de Implementação — Autenticação

## 1️⃣ Modelo mental da autenticação

### Fluxo geral

1. Usuário acessa qualquer app (web/mobile/desktop)
2. App solicita login (email + senha)
3. App chama a **API de autenticação central**
4. API valida credenciais
5. API retorna:

   * Token de acesso
   * Metadados mínimos do usuário
6. App usa o token em todas as requisições subsequentes

📌 **Nenhum app mantém sessão própria**
📌 **Nenhum app conhece regra de autenticação**

---

## 2️⃣ Estrutura base de autenticação (Laravel)

### Recursos nativos a utilizar

* `Auth` Facade
* `Guard` padrão `sanctum`
* `User` model único
* `Password` Broker nativo
* `Notifications` (para reset de senha)
* `Events` de autenticação

💡 **Decisão importante**
Usar **apenas 1 guard (`sanctum`)** evita complexidade futura.

---

## 3️⃣ Restrições de domínio de e-mail

### Requisito

> Apenas emails `@funac.mt.gov.br` podem autenticar

### Implementação conceitual

* Validação **antes da tentativa de login**
* Validação **antes do envio de reset de senha**
* Bloqueio definitivo no backend (não confiar no frontend)

### Comportamento esperado

* Qualquer tentativa fora do domínio:

  * Falha silenciosa (mensagem genérica)
  * Sem indicar se o usuário existe

📌 Evita:

* Enumeração de usuários
* Vazamento de informação interna

---

## 4️⃣ Criação de usuários (sem register)

### Decisão arquitetural

* ❌ Não existe endpoint `/register`
* ❌ Não existe signup público
* ✔ Usuários são criados **apenas internamente**:

  * Seeders
  * Scripts administrativos
  * Migração de dados
  * Integrações futuras

### Comportamento do sistema

* Usuário só consegue:

  * Fazer login
  * Redefinir senha (se já existir)

📌 Isso reforça o controle institucional do ERP.

---

## 5️⃣ Login (email + senha)

### Fluxo técnico

1. API recebe:

   * Email
   * Senha
   * Identificação do aplicativo (ex: `app_name`)
2. API valida:

   * Domínio do email
   * Existência do usuário
   * Senha
3. API gera:

   * Token Sanctum
   * Nome do token vinculado ao app

### Boas práticas aplicadas

* Tokens por aplicativo/dispositivo
* Possibilidade futura de:

  * Revogar tokens por app
  * Auditar acessos

---

## 6️⃣ Logout

### Comportamento esperado

* Logout remove **apenas o token atual**
* Não encerra outras sessões/apps

📌 Ideal para múltiplos apps conectados ao mesmo usuário.

---

## 7️⃣ “Esqueci minha senha” (Reset de senha)

### Requisitos-chave

* O **frontend define a URL de redefinição**
* A API **não controla a interface**
* Cada app pode ter sua própria tela

---

### Fluxo funcional

1. Usuário solicita reset
2. Frontend envia para a API:

   * Email
   * URL base de redefinição
3. API:

   * Valida domínio
   * Verifica existência do usuário
   * Gera token de reset
4. API envia e-mail contendo:

   * Token
   * Email
   * URL fornecida pelo frontend
5. Usuário clica no link
6. Frontend captura token
7. Frontend envia nova senha para a API
8. API valida token e altera senha

---

### Recursos nativos utilizados

* `Password::sendResetLink()`
* `password_resets` table
* `Notifications` customizada (apenas o link muda)

📌 Segurança mantida, UI desacoplada.

---

## 8️⃣ Segurança do fluxo de reset

Medidas obrigatórias:

* Token com expiração curta
* Token de uso único
* Mensagem genérica ao solicitar reset
* Rate limit por IP e email
* Invalidação automática de tokens antigos

Tudo isso é **nativo do Laravel**.

---

## 9️⃣ Rate Limiting (obrigatório)

Aplicar throttle em:

* Login
* Reset de senha
* Validação de token

Exemplo conceitual:

* Login: 5 tentativas / minuto
* Reset: 3 tentativas / minuto

📌 Protege contra brute force e abuso.

---

## 🔟 Padronização de resposta de autenticação

### Contrato fixo de API

Todas as respostas devem seguir padrão:

* Sucesso
* Mensagem genérica
* Dados mínimos
* Nunca revelar:

  * Se usuário existe
  * Se senha está errada
  * Se e-mail é válido

📌 Fundamental para segurança corporativa.

---

## 1️⃣1️⃣ Eventos e auditoria (preparação futura)

Mesmo sem implementar auditoria agora:

* Registrar eventos de:

  * Login
  * Logout
  * Reset de senha
* Usar eventos nativos (`Login`, `Logout`, `Failed`)

📌 Isso prepara o sistema para:

* Auditoria
* LGPD
* Relatórios de segurança

---

## 1️⃣2️⃣ Integração com aplicativos futuros

### Como os apps devem se comportar

* Nenhum app:

  * Guarda senha
  * Implementa login próprio
* Todos:

  * Redirecionam login para API
  * Armazenam token com segurança
  * Revalidam token a cada requisição

📌 Auth central vira **infraestrutura crítica**, não feature.

---

## 1️⃣3️⃣ Evolução futura (sem retrabalho)

Essa base permite futuramente:

* MFA
* Login por certificado
* Integração com LDAP/AD
* Single Sign-On interno
* Revogação global de acessos
* Monitoramento de segurança

Sem quebrar:

* Apps existentes
* Tokens emitidos
* Fluxos de login

---

## ✅ Conclusão

Esse plano cria:

* 🔐 Um **Auth central robusto**
* 🧠 Alinhado ao modelo institucional
* 🧩 Pronto para múltiplos aplicativos
* 🚀 Escalável sem refatorações traumáticas
* 🛡️ Seguro no nível exigido por um ERP público

---

## ✅ Implementação

Implementei um conjunto inicial de recursos seguindo este plano:

* Adicionados migrations de `personal_access_tokens` e `password_reset_tokens` ✅
* `Laravel Sanctum` usado para emissão de tokens (personal access tokens) ✅
* Endpoints de API: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me` ✅
* `POST /api/auth/password/forgot` e `POST /api/auth/password/reset` com notificações customizadas e fluxo desacoplado ✅
* Validação de domínio com a `Rule` `FunacEmail` aplicada em login e reset ✅
* Rate limiters configurados (`login: 5/min`, `password-reset: 3/min`) ✅
* Testes iniciais com Pest cobrindo os fluxos básicos ✅

---

> Observações: execute `php artisan migrate` em seu ambiente (aqui a execução falhou por falta de DB config local). Rode `vendor/bin/pint` antes de commitar e execute os testes relacionados com `php artisan test --filter=Auth`.

