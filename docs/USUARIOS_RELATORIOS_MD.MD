# Usuários, Relatórios e Controle de Acesso (Resumo do Dia)

Data: 14/12/2025

Este documento resume todas as alterações e orientações implementadas hoje para o painel Admin, controle de usuários e acesso aos relatórios.

## Objetivos
- Separar permissões entre Administradores e Usuários comuns (RBAC).
- Melhorar a experiência do painel Admin (modais e seções condicionais).
- Corrigir fluxo de login e CSRF.
- Garantir compatibilidade com esquemas de senha antigos e novos.

## Mudanças Principais

### 1. RBAC (Role-Based Access Control)
- Adicionadas colunas na tabela `clima_users`:
  - `name` (VARCHAR 100)
  - `email` (VARCHAR 100)
  - `created_at` (DATETIME DEFAULT CURRENT_TIMESTAMP)
  - `role` (VARCHAR 20 DEFAULT 'user')
- Usuário `admin` garantido com `role='admin'` (ver `ensureSchema()` em `lib/schema.php`).

### 2. Fluxo de Login e Sessão
- `AuthService` atualizado para validar senha contra `password` (novo) com fallback para `password_hash` (antigo).
- `AuthMiddleware` ajustado: `LOGIN_PATH` definido como `/admin/login` para não confundir `/admin` com login.
- Ordem dos middlewares em `public/index.php`:
  - `SessionMiddleware` → `AuthMiddleware` → `CsrfMiddleware`
  - Motivo: garantir sessão ativa antes de validar autenticação e CSRF.

### 3. CSRF Middleware
- `CsrfMiddleware` não protege mais `POST /admin/login` (evita erro de token no login).
- Protege apenas ações sensíveis:
  - `/admin/logout`, `/admin/settings`, `/admin/sync`, `/admin/profile`, `/admin/users/create`
  - E qualquer `/admin/users/delete/{id}`

### 4. Painel Admin (UI)
- Seções condicionais via `isAdmin`:
  - Admin: vê "Sincronizar com Thinger", "Configurações Thinger", "Gerenciar Usuários", além dos Relatórios.
  - Usuário: vê Relatórios e modal "Alterar Minha Senha".
- Modais:
  - Alterar senha (todos os usuários) → `POST /admin/profile`
  - Gerenciar usuários (apenas admin) → lista via `GET /admin/users/list`, criação via `POST /admin/users/create`, exclusão via `POST /admin/users/delete/{id}`.
- Badge de role no header: "Administrador" (roxo) ou "Usuário" (cinza).

## Arquivos Alterados
- `lib/schema.php`: migração de colunas e garantia de role do admin.
- `src/Service/AuthService.php`: compatibilidade `password`/`password_hash`.
- `src/Middleware/AuthMiddleware.php`: `LOGIN_PATH` e lógica de admin area.
- `src/Middleware/CsrfMiddleware.php`: escopo de proteção reduzido para evitar bloqueio no login.
- `public/index.php`: ordem dos middlewares ajustada.
- `src/Repository/UserRepository.php`: seleção de `password`, `password_hash` e `role` para compatibilidade.
- `src/Controller/AdminController.php`: dashboard com renderização condicional de seções, modais e rotas auxiliares.
- `bin/reset_admin.php`: script para resetar senha do admin (novo).

## Rotas Envolvidas
- Login: `GET /admin/login`, `POST /admin/login`
- Logout: `GET /admin/logout`
- Dashboard: `GET /admin`
- Configurações: `POST /admin/settings` (admin)
- Sincronização: `POST /admin/sync` (admin)
- Perfil (alterar senha): `POST /admin/profile` (todos)
- Relatórios: `GET /admin/reports`
- Usuários (admin):
  - Lista: `GET /admin/users/list`
  - Criar: `POST /admin/users/create`
  - Excluir: `POST /admin/users/delete/{id}`

## Como Testar
1. Inicie o servidor local:
   ```powershell
   php -S localhost:8080 -t public
   ```
2. Acesse o login: `http://localhost:8080/admin/login`
3. Credenciais padrão (após reset):
   - Usuário: `admin`
   - Senha: `admin123`
4. Acesse o dashboard: `http://localhost:8080/admin`
   - Admin verá todas as seções.
   - Usuário verá apenas relatórios e alteração de senha.

## Reset do Usuário Admin
- Script: `bin/reset_admin.php`
- Executar:
  ```powershell
  php -r "require 'bin/reset_admin.php';"
  ```
- Efeito: Reseta a senha do `admin` para `admin123` e garante `role='admin'`, compatível com colunas `password` ou `password_hash`.

## Observações e Compatibilidade
- O esquema de usuários pode conter `password` (novo) ou `password_hash` (antigo). O sistema está compatível com ambos.
- CSRF foi mantido para ações sensíveis e removido do login para evitar bloqueios.
- Seções do dashboard foram refatoradas para mostrar apenas o que é permitido por role.

## Próximos Passos (Sugestões)
- Implementar reset de senha por email.
- Adicionar promoção/demissão de roles via UI (apenas admin).
- Documentar como promover usuário a admin via SQL:
  ```sql
  UPDATE clima_users SET role='admin' WHERE username='usuario';
  ```

---

Este documento consolida as mudanças para facilitar operação e manutenção do sistema de usuários e relatórios.
