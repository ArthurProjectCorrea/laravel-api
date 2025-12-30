Excelente próximo passo.
Testes **bem pensados agora** vão te poupar **dor real** quando esse Auth virar infraestrutura crítica da empresa.

Abaixo está um **plano de testes de autenticação usando Pest**, **alinhado 1:1 com o plano de implementação**, focado **exclusivamente no fluxo de autenticação**, sem tocar em permissões, CRUD avançado ou regras de negócio futuras.

> 🎯 Objetivo dos testes
> Garantir que a **Auth API central**:

* É segura
* É previsível
* Não vaza informação
* Funciona igual para qualquer app integrado

---

# 🧪 Estratégia Geral de Testes

## Ferramentas

* **Pest PHP** (framework de testes)
* **Laravel Test Helpers** (nativos)
* **RefreshDatabase**
* **PostgreSQL em ambiente de teste**
* **Mail fake (`Mail::fake`)**
* **Notification fake (`Notification::fake`)**
* **Rate limiter real (não mockado)**

📌 Decisão importante:
**Não mockar autenticação interna do Laravel**, apenas efeitos externos (email).

---

## Organização dos testes

### Estrutura sugerida

```text
tests/
└── Feature/
    └── Auth/
        ├── LoginTest.php
        ├── LogoutTest.php
        ├── PasswordResetRequestTest.php
        ├── PasswordResetConfirmTest.php
        ├── TokenValidationTest.php
        └── RateLimitTest.php
```

Cada arquivo testa **um fluxo completo**, não endpoints isolados.

---

# 🔐 Testes de Autenticação — Plano Detalhado

## 1️⃣ Testes de pré-condição (base)

### Objetivo

Garantir que o sistema **não aceita estados inválidos**.

### Cenários a testar

* Usuário sem senha definida não consegue login
* Usuário inativo (se existir flag futura) não autentica
* Usuário com e-mail fora do domínio é bloqueado
* Endpoint de register não existe (404)

📌 Esses testes validam decisões arquiteturais.

---

## 2️⃣ Login — Fluxo principal

### Objetivo

Garantir login correto, seguro e padronizado.

### Casos de teste

#### ✅ Login bem-sucedido

* Usuário existente
* Email `@funac.mt.gov.br`
* Senha correta
* Retorna:

  * Token válido
  * Estrutura padrão de resposta

#### ❌ Senha incorreta

* Mensagem genérica
* Status apropriado
* Nenhum token gerado

#### ❌ Usuário inexistente

* Mesma resposta de senha inválida
* Não vaza informação

#### ❌ Email fora do domínio

* Bloqueio imediato
* Resposta genérica

#### ❌ Campos inválidos

* Email ausente
* Senha ausente
* Tipos inválidos

📌 Importante:
Todos os erros devem ter **mesma estrutura de resposta**.

---

## 3️⃣ Login — Tokens

### Objetivo

Validar comportamento dos tokens emitidos.

### Casos de teste

* Token é persistido em `personal_access_tokens`
* Token está vinculado ao usuário correto
* Token tem nome do app (quando informado)
* Tokens múltiplos podem coexistir
* Token permite acesso a rota protegida

📌 Isso garante integração multi-app segura.

---

## 4️⃣ Logout

### Objetivo

Garantir revogação correta do token atual.

### Casos de teste

* Logout invalida apenas o token usado
* Outros tokens continuam válidos
* Requisição sem token retorna não autenticado
* Logout sem token não quebra o sistema

---

## 5️⃣ Middleware de autenticação

### Objetivo

Validar proteção real da API.

### Casos de teste

* Rota protegida sem token → 401
* Rota protegida com token inválido → 401
* Rota protegida com token válido → 200
* Token revogado → 401

📌 Testa o guard `sanctum` de ponta a ponta.

---

## 6️⃣ Reset de senha — Solicitação

### Objetivo

Garantir fluxo seguro de “esqueci minha senha”.

### Casos de teste

#### ✅ Solicitação válida

* Email existente
* Domínio correto
* URL de frontend informada
* Notificação enviada

#### ❌ Email inexistente

* Resposta genérica
* Nenhum erro explícito

#### ❌ Email fora do domínio

* Bloqueado
* Nenhuma notificação enviada

#### ❌ URL ausente ou inválida

* Erro de validação
* Nenhum e-mail enviado

📌 Aqui o foco é **não vazar informação**.

---

## 7️⃣ Reset de senha — Confirmação

### Objetivo

Garantir integridade da troca de senha.

### Casos de teste

* Token válido redefine senha
* Token inválido falha
* Token expirado falha
* Token reutilizado falha
* Senha antiga não funciona mais
* Nova senha autentica com sucesso

📌 Testa segurança real, não só “happy path”.

---

## 8️⃣ Rate Limiting

### Objetivo

Garantir proteção contra abuso.

### Casos de teste

* Muitas tentativas de login bloqueiam temporariamente
* Muitas solicitações de reset bloqueiam
* Rate limit retorna status correto
* Após tempo de espera, acesso normaliza

📌 Não mockar tempo aqui, usar helpers do Laravel.

---

## 9️⃣ Padronização das respostas

### Objetivo

Garantir contrato fixo de API.

### Casos de teste

* Todas respostas possuem:

  * `success`
  * `message`
  * `data`
  * `errors`
* Nunca expõem:

  * Se usuário existe
  * Se email é válido
  * Se senha está errada

📌 Isso é **teste de contrato**, não funcional.

---

## 🔟 Eventos de autenticação

### Objetivo

Garantir que eventos disparem corretamente.

### Casos de teste

* Evento de login disparado
* Evento de logout disparado
* Evento de falha de login disparado
* Evento de reset de senha disparado

📌 Prepara auditoria futura sem refatoração.

---

## 1️⃣1️⃣ Cenários multi-app

### Objetivo

Validar comportamento com múltiplos aplicativos.

### Casos de teste

* Login com `app_name=erp-web`
* Login com `app_name=ponto-mobile`
* Tokens distintos por app
* Revogação isolada

📌 Essencial para o ecossistema descrito.

---

## 1️⃣2️⃣ Segurança defensiva

### Objetivo

Testar ataques comuns.

### Casos de teste

* SQL injection nos campos de login
* Payloads inesperados
* Campos extras ignorados
* Headers malformados

📌 ERP público → paranoia saudável.

---

# 🧪 Boas práticas específicas com Pest

### Organização

* Use `beforeEach` para criar usuário padrão
* Use `actingAs` apenas quando necessário
* Use factories sempre (nunca seeders)
* Use `assertDatabaseHas` e `assertDatabaseMissing`

---

### Nomenclatura dos testes

```text
it('allows login with valid credentials')
it('does not reveal if user exists')
it('blocks emails outside allowed domain')
```

📌 Testes devem ser **documentação viva**.

---

# ✅ Resultado esperado

Ao final desse plano, você terá:

* 🔐 Autenticação totalmente testada
* 🧪 Confiança para integrar qualquer app
* 🚀 Base sólida para MFA, SSO e AD
* 🛡️ Segurança compatível com ERP institucional

---
