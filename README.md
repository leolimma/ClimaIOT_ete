# Estação Climática ETE (ClimaIOT)

Aplicação PHP (Slim 4) para coletar, persistir e visualizar leituras da estação climática integrada ao Thinger.io, com painel administrativo, RBAC e rotinas de sincronização.

## Visão Geral
- Backend: Slim 4 + PHP 8.x
- Banco: MySQL/MariaDB
- UI: Tailwind + Lucide
- Configuração: arquivo `.env` (sem `db_config.php`)
- Segurança: sessões, CSRF (admin), RBAC (`admin`/`user`), throttle de login

## Requisitos
- PHP 8.1+ com `pdo_mysql`, `mbstring`, `openssl`
- MySQL/MariaDB acessível
- Servidor web apontando a [public/](public/)
- Composer para instalação de dependências

## Estrutura
- Público/rotas: [public/index.php](public/index.php)
- Controladores: [src/Controller](src/Controller)
- Middlewares: [src/Middleware](src/Middleware)
- Serviços/Repos: [src/Service](src/Service), [src/Repository](src/Repository)
- Dados/Thinger: [lib/db.php](lib/db.php), [lib/schema.php](lib/schema.php), [lib/thinger.php](lib/thinger.php)
- Config padrão: [src/Settings/settings.php](src/Settings/settings.php)
- Configuração PHP: [public/php.ini](public/php.ini)

## Instalação Rápida
```bash
# 1) Clonar e instalar dependências
git clone https://github.com/leolimma/ClimaIOT.git clima_ete
cd clima_ete
composer install --no-dev --optimize-autoloader

# 2) Preparar .env (veja abaixo) e apontar docroot para public/

# 3) Acessar setup
# Ex.: https://seu-dominio/setup
```

## Configuração (.env)
Crie `.env` na raiz com, no mínimo:
```
DB_HOST=localhost
DB_NAME=clima_ete
DB_USER=usuario
DB_PASS=senha
DB_CHARSET=utf8mb4

# Thinger.io
THINGER_USER=seu_usuario
THINGER_DEVICE=seu_dispositivo
THINGER_RESOURCE=seu/recurso
THINGER_TOKEN=Bearer SEU_TOKEN

# Cron
CLIMA_CRON_KEY=uma_chave_segura
```

## Provisionamento Inicial (Setup)
- Acesse [setup](public/index.php) via `GET /setup` para executar:
	- Criar/atualizar esquema (`clima_historico`, `clima_config`, `clima_users`, `clima_password_resets`)
	- Gravar `.env` se informado
	- Criar usuário admin inicial (opcional)
- Rotas:
	- `GET /setup` (form)
	- `POST /setup` ou `POST /setup/run` (executa)

## Autenticação e RBAC
- Login: `GET/POST /admin/login`
- Logout: `GET /admin/logout`
- Dashboard: `GET /admin`
- RBAC: `clima_users.role` com `admin` e `user`
- CSRF: exigido em `POST` do admin (exceto `POST /admin/login`)

## Sincronização com Thinger.io
- Painel: `POST /admin/sync` (admin)
- Cron público com chave: `GET /cron/sync?key=CLIMA_CRON_KEY`
	- Define `CLIMA_CRON_KEY` no `.env` ou em `clima_config`
- Persistência: [lib/thinger.php](lib/thinger.php) → `persistThingerPayload()` normaliza tipos e calcula `chuva_status`

## Armazenamento de Sessões (fora do /tmp)
Em ambientes onde `/tmp` não é compartilhado ou não é adequado, configure um diretório dedicado e ajuste o `php.ini` dentro de [public/php.ini](public/php.ini):

1) Criar diretório de sessões (exemplos):
```bash
# Linux (ajuste usuário/grupo do webserver)
mkdir -p /var/www/clima_ete/var/sessions
chown www-data:www-data /var/www/clima_ete/var/sessions
chmod 700 /var/www/clima_ete/var/sessions

# cPanel/HostGator (home do usuário)
mkdir -p /home/SEU_USUARIO/tmp/clima_sessions
chmod 700 /home/SEU_USUARIO/tmp/clima_sessions
```

2) Configurar diretivas de sessão em [public/php.ini](public/php.ini):
```
session.save_handler = files
session.save_path = "/home/SEU_USUARIO/tmp/clima_sessions"
session.gc_probability = 1
session.gc_divisor = 1000
session.gc_maxlifetime = 86400
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_samesite = "Lax"
; Habilite apenas com HTTPS
session.cookie_secure = 1
```

3) (Opcional) Em Apache, garanta que o `php.ini` em `public/` é aplicado. Em Nginx/FPM, edite o `php.ini` do pool ou use ini-values no vhost/site.

## Deploy
- Docroot deve apontar para [public/](public/)
- Instale dependências com `composer install --no-dev`
- Configure `.env`
- Verifique permissões de `var/` (logs/sessões, se usado)
- Git: `.gitignore` mantém apenas arquivos de produção (ex.: ignora testes e binários legados)

## E-mail de Reset de Senha
- Fluxo: `GET /admin/password/forgot` → e-mail com token → `GET /admin/password/reset?token=...`
- Envio padrão via `mail()`; para SMTP, substitua implementação em `PasswordResetService` conforme seu provedor.

## Troubleshooting
- Conexão DB: veja [lib/db.php](lib/db.php); erros `DatabaseConfigException/ConnectionException` indicam `.env` ausente ou inválido
- Rotas quebradas: confirme docroot e `public/index.php`
- Sessões não persistem: verifique `session.save_path` e permissões do diretório
- Thinger.io falhando: `fetchThingerData()` retorna status/mensagem; valide token e recurso

## Rotas Principais
- Público: `GET /` (home), `GET /live` (HTML/JSON)
- Admin: `GET/POST /admin/login`, `GET /admin`, `POST /admin/settings`, `POST /admin/sync`, `POST /admin/profile`, `GET /admin/reports`
- Usuários (admin): `GET /admin/users/list`, `POST /admin/users/create`, `POST /admin/users/delete/{id}`
- Setup: `GET /setup`, `POST /setup`/`/setup/run`
- Cron: `GET /cron/sync?key=...`

## Comandos Úteis
```bash
# Instalação
composer install --no-dev --optimize-autoloader

# Rodar localmente (PHP built-in)
php -S localhost:8080 -t public

# Reset admin (CLI)
php -r "require 'bin/reset_admin.php';"
```

## Licença
Consulte o arquivo de licença no repositório.
