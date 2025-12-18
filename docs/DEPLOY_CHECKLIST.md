# Checklist de Deploy (Slim 4 + RBAC)

Data: 14/12/2025

## Pré-requisitos
- PHP 8.1+ com extensões `pdo_mysql`, `openssl`, `mbstring`.
- Banco MySQL acessível e credenciais válidas.
- `db_config.php` preenchido com `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.

## Passos

### 0. Ambiente (.env)
- Copie `.env.example` para `.env` na raiz do projeto
- Preencha `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`
- `db_config.php` não é mais usado (arquivo ignorado no git)

### 1. Preparar banco
- Executar provisionamento:
  ```powershell
  php setup.php
  ```
- Validar timezone do MySQL:
  ```powershell
  php check_mysql_tz.php
  ```

### 2. Migrar schema e garantir admin
- O schema adiciona colunas `name`, `email`, `created_at`, `role`, e migra `password_hash` → `password` quando necessário (ver `lib/schema.php`).
- Resetar admin (compatível com `password` e `password_hash`):
  ```powershell
  php -r "require 'bin/reset_admin.php';"
  ```

### 3. Variáveis e configurações
- Preencher Thinger no dashboard (admin): `user`, `device`, `resource`, `token` (aceita `Bearer ...` ou valor puro).
- Definir `cron_key` para proteger `sync_cron.php`.

### 4. Testes de funcionalidade
- Subir servidor local:
  ```powershell
  php -S localhost:8080 -t public
  ```
- Login:
  - `http://localhost:8080/admin/login`
  - Usuário: `admin` | Senha: `admin123`
- Dashboard:
  - `http://localhost:8080/admin`
  - Admin vê Sync/Config/Usuários/Relatórios
  - Usuário vê Relatórios + Alterar Senha
- Relatórios:
  - `GET /admin/reports` (HTML)
  - `GET /admin/reports?period=7&format=csv` (CSV)

### 5. Segurança
- Ordem dos middlewares em `public/index.php`:
  - Session → Auth → CSRF
- CSRF:
  - Não exigir em `POST /admin/login`
  - Exigir em `POST /admin/settings`, `POST /admin/sync`, `POST /admin/profile`, `POST /admin/users/create`, `POST /admin/users/delete/{id}`

### 6. Operação
- Sincronização manual: `POST /admin/sync` (admin)
- Cron via web: `https://seu-site/sync_cron.php?key=SUA_CHAVE`
- Cron via CLI:
  ```powershell
  php sync_cron.php -k=SUA_CHAVE
  ```

## Problemas comuns
- Login falhando: confirmar coluna de senha vigente (`password` vs `password_hash`) e rodar `bin/reset_admin.php`.
- CSRF inválido: confirmar ordem de middlewares e exceção para `POST /admin/login`.
- Sessão não persiste: verificar cookies com `Secure`/`SameSite=Lax` e ambiente (localhost vs produção).

## Pós-deploy
- Remover `setup.php` e considerar restringir `lib/schema.php` em produção.
- Validar que somente admins têm acesso às rotas de gerenciamento.
