Perfeito.
Esse é o **terceiro pilar certo**: garantir que **localmente tudo que a autenticação precisa esteja rodando**, de forma **idêntica ao ambiente real**, mas **sem excesso**.

Abaixo está um **levantamento completo do que é essencial para a fase de autenticação** + um **plano de configuração local e Docker**, focado **somente no Auth**.

---

# 🎯 Objetivo deste plano

Garantir que, em ambiente local:

* A API de autenticação funcione **100%**
* Os fluxos testados com Pest reflitam a realidade
* O ambiente seja:

  * Reproduzível
  * Padronizado
  * Fácil de subir para qualquer dev
* Nenhuma dependência desnecessária seja adicionada

---

# 🧱 O que a API de Autenticação PRECISA localmente

## ✅ Essenciais (obrigatórios)

### 1️⃣ PostgreSQL

**Sim, obrigatório**

#### Por quê?

* É o banco oficial do projeto
* Auth depende diretamente de:

  * `users`
  * `password_resets`
  * `personal_access_tokens`
* Diferenças entre MySQL/Postgres **impactam produção**

#### Uso na autenticação

* Persistência de usuários
* Tokens Sanctum
* Reset de senha
* Futuro: auditoria e logs

📌 **Conclusão:**
Postgres **não é opcional**, nem em local.

---

### 2️⃣ Serviço de E-mail (Mailhog ou equivalente)

**Sim, obrigatório**

#### Por quê?

* Reset de senha depende de e-mail
* Fluxo precisa ser validado localmente
* Testes manuais e automatizados exigem inspeção do e-mail

#### Ferramenta recomendada

* **MailHog**

  * SMTP fake
  * Interface web
  * Não envia e-mails reais

📌 **Conclusão:**
Sem Mailhog, o fluxo de reset **fica incompleto**.

---

### 3️⃣ PHP + Laravel App

**Óbvio, mas com observações**

#### Requisitos

* PHP compatível com produção
* Extensões:

  * PDO
  * PDO_PGSQL
  * OpenSSL
  * Mbstring
  * Tokenizer

📌 Em Docker isso já vem controlado.

---

## ⚠️ Condicional (depende de decisão)

### 4️⃣ Redis

**Recomendado fortemente, mas tecnicamente opcional nesta fase**

#### Onde a autenticação usa Redis?

* Rate Limiting
* Cache de autenticação
* Sessões (se futuramente necessário)
* Filas (futuro reset async, auditoria)

#### Opções

* Usar Redis desde agora (recomendado)
* Usar driver de cache em arquivo (não ideal)

📌 **Decisão recomendada:**
➡ **Sim, subir Redis desde já**, mesmo que pouco usado agora.

Motivo:
Evita retrabalho quando:

* Rate limit ficar mais agressivo
* MFA for adicionado
* Login for desacoplado em filas

---

## ❌ Não necessários (nesta fase)

### ❌ Frontend

* Nenhuma interface é necessária
* Tudo é API

### ❌ Nginx separado

* Pode vir depois
* PHP built-in ou PHP-FPM no container é suficiente

### ❌ Queue worker separado

* Reset de senha pode ser síncrono por enquanto
* Worker pode ser adicionado depois sem impacto

---

# 🧩 Resumo rápido — Serviços necessários

| Serviço     | Necessário     | Motivo              |
| ----------- | -------------- | ------------------- |
| Laravel API | ✅              | Core                |
| PostgreSQL  | ✅              | Persistência        |
| Mailhog     | ✅              | Reset de senha      |
| Redis       | ⚠️ Recomendado | Rate limit / futuro |
| Nginx       | ❌              | Não essencial       |
| Worker      | ❌              | Pode vir depois     |

---

# 🐳 Plano de Configuração com Docker

## 1️⃣ Containers necessários

### Containers mínimos para Auth

* `app` (Laravel API)
* `postgres`
* `mailhog`
* `redis` (recomendado)

📌 Nenhum container extra além disso.

---

## 2️⃣ Comportamento esperado do ambiente Docker

### Laravel API

* Expõe porta da API
* Conecta-se aos serviços internos via network Docker
* Usa `.env` específico para ambiente local

---

### PostgreSQL

* Banco criado automaticamente
* Usuário e senha definidos
* Volume persistente (dados não somem ao reiniciar)

---

### Mailhog

* SMTP acessível internamente
* Interface web acessível externamente
* Usado exclusivamente em `local` e `testing`

---

### Redis

* Usado para:

  * Rate limit
  * Cache
* Mesmo que pouco utilizado agora, já operacional

---

## 3️⃣ Variáveis de ambiente essenciais (.env)

### Banco de dados

* Driver: `pgsql`
* Host: serviço Docker
* Porta padrão
* Banco exclusivo do projeto

---

### Mail

* Driver SMTP
* Host: mailhog
* Porta padrão
* Sem autenticação

---

### Cache / Rate limit

* Driver: redis
* Prefixo exclusivo para o projeto

---

### Sanctum

* Domínios stateful (se necessário)
* Configuração padrão é suficiente nesta fase

---

## 4️⃣ Ambiente de testes (Pest)

### Decisão importante

**Testes NÃO devem usar o mesmo banco do ambiente local.**

#### Opções

* Banco Postgres separado (`erp_auth_test`)
* Ou SQLite apenas para testes (menos fiel)

📌 Recomendação:
➡ **Postgres separado**, mesmo em Docker.

---

### Mail nos testes

* Sempre usar:

  * `Mail::fake`
  * `Notification::fake`

Nenhum e-mail real ou Mailhog é usado durante os testes automatizados.

---

## 5️⃣ Ordem de inicialização (startup)

1. PostgreSQL sobe
2. Redis sobe
3. Mailhog sobe
4. Laravel API sobe
5. Migrações rodam
6. API pronta para:

   * Login
   * Reset
   * Testes Pest

---

## 6️⃣ Checklist de pronto para autenticação

Antes de considerar a fase de Auth concluída:

* ✅ API sobe via Docker
* ✅ Postgres conecta
* ✅ Migrações rodam sem erro
* ✅ Login funciona
* ✅ Token é emitido
* ✅ Rota protegida valida token
* ✅ Reset de senha envia e-mail (Mailhog)
* ✅ Testes Pest passam
* ✅ Rate limit ativo

---

# 🔮 Preparação para o futuro (sem custo agora)

Essa estrutura já deixa preparado:

* Filas assíncronas
* Auditoria
* MFA
* Integração com AD/LDAP
* Observabilidade

Sem refatorar containers.

---

## ✅ Conclusão

Para a **fase de autenticação**, o ambiente local precisa ser:

* **Simples**
* **Completo**
* **Idêntico ao real**
* **Sem dependências inúteis**

Esse plano atende exatamente isso.

---

👉 **Próximo passo sugerido**
Podemos agora:

1️⃣ Definir o **docker-compose definitivo**
2️⃣ Criar um **checklist de segurança pré-produção do Auth**
3️⃣ Planejar **MFA desde já (sem implementar)**

Você decide como seguimos.
