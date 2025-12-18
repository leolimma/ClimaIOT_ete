# Estação Climática ETE (ClimaIOT) - V3

Aplicação PHP (Slim 4) para coletar, persistir e visualizar leituras da estação climática integrada ao Thinger.io, com painel administrativo, RBAC, relatórios em PDF e rotinas de sincronização automática.

## 🎯 Features Principais

- **Dashboard em Tempo Real**: Visualização de dados climáticos ao vivo
- **Sistema de Usuários**: Autenticação com roles (admin/user) e RBAC
- **Relatórios em PDF**: Exportação de dados com JsPDF e AutoTable
- **Sincronização com Thinger.io**: Integração automática de dados IoT
- **Painel Administrativo**: Gerenciamento de configurações, usuários e sincronização
- **Recuperação de Senha**: Fluxo seguro via e-mail
- **API REST**: Endpoints para acesso aos dados
- **Logging**: Sistema de logs estruturado

## 📋 Requisitos

- PHP 8.1+ com `pdo_mysql`, `mbstring`, `openssl`
- MySQL/MariaDB 5.7+
- Composer
- Servidor web apontando para `public/`

## 🚀 Instalação Rápida

```bash
# 1) Clonar e instalar dependências
git clone https://github.com/leolimma/ClimaIOT.git clima_ete
cd clima_ete
composer install --no-dev --optimize-autoloader

# 2) Criar arquivo .env (veja Configuração abaixo)
cp .env.example .env
# Editar .env com suas credenciais

# 3) Acessar setup (primeira vez)
php -S localhost:8000 -t public
# Acesse http://localhost:8000/setup
```

## ⚙️ Configuração (.env)

Crie `.env` na raiz com, no mínimo:

```env
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

# Cron (opcional)
CLIMA_CRON_KEY=uma_chave_segura
```

**Nota:** O sistema agora usa `.env` (arquivo `.env.example` está disponível). O arquivo `db_config.php` é descontinuado.

## 🔑 Login Padrão

Após setup:
- **Usuário**: admin
- **Senha**: admin (alterar na primeira entrada)

Para resetar admin via CLI:
```powershell
php -r "require 'bin/reset_admin.php';"
```

## 📂 Estrutura do Projeto

```
clima_ete_novo/
├── bin/
│   ├── reset_admin.php          # Reset de senha admin (CLI)
│   └── console                  # Console Symfony
├── docs/                        # Documentação
├── lib/                         # Bibliotecas PHP
│   ├── db.php                   # Conexão PDO centralizada
│   ├── schema.php               # Schema do banco e migrações
│   └── thinger.php              # API Thinger.io
├── migrations/                  # Migrações do banco
├── public/
│   ├── index.php               # Entry point Slim Framework
│   ├── php.ini                 # Configurações PHP (sessões, etc)
│   └── assets/                 # Imagens e recursos (Tailwind, Lucide)
├── src/
│   ├── Controller/             # Controladores Slim
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── PublicController.php
│   │   ├── RelatoriosController.php
│   │   ├── SetupController.php
│   │   └── CronController.php
│   ├── Middleware/             # Middlewares PSR-15
│   │   ├── SessionMiddleware.php
│   │   ├── AuthMiddleware.php
│   │   └── CsrfMiddleware.php
│   ├── Repository/             # Data access layer
│   │   ├── UserRepository.php
│   │   ├── ConfigRepository.php
│   │   ├── HistoricsRepository.php
│   │   └── PasswordResetRepository.php
│   ├── Service/                # Business logic
│   │   ├── AuthService.php
│   │   ├── ConfigService.php
│   │   ├── SyncService.php
│   │   ├── PublicViewService.php
│   │   ├── MetricService.php
│   │   ├── PasswordResetService.php
│   │   └── SetupService.php
│   └── Settings/               # Configurações DI
└── var/
    └── log/                    # Logs do sistema
```

## 🔐 Segurança

- **Autenticação**: Session-based com hash de senha (`password_hash`)
- **CSRF**: Token CSRF em POST administrativos (exceto login)
- **SQL Injection**: Prepared statements com PDO
- **XSS**: Sanitização com `htmlspecialchars()`
- **RBAC**: Controle de acesso por role (`admin`/`user`)
- **Throttle**: Limite de tentativas de login com bloqueio temporal
- **Sessões**: Configuração segura em `public/php.ini`

## 🗄️ Banco de Dados

Tabelas:

- `clima_historico`: Leituras históricas de sensores (id, data_registro, temp, hum, pres, uv, gas, chuva, chuva_status)
- `clima_config`: Configurações do sistema (chave, valor)
- `clima_users`: Usuários e autenticação (id, username, password, email, role)
- `clima_password_resets`: Tokens de reset de senha

Esquema criado/atualizado automaticamente via `ensureSchema()` em `lib/schema.php`.

## 🔄 Fluxo de Sincronização

### Manual
- Dashboard admin: `POST /admin/sync`

### Automático (Cron)
```bash
# Via web
curl "https://seu-site/cron/sync?key=SUA_CHAVE_CRON"

# Via CLI (Windows)
php sync_cron.php -k=SUA_CHAVE_CRON

# Ou Python/Node
node -e "require('http').get('http://localhost:8000/cron/sync?key=...')"
```

Integração: `fetchThingerData()` + `persistThingerPayload()` normalizam tipos e calculam `chuva_status`.

## 📊 Métricas Monitoradas

- **Temperatura** (°C) - Classificação: Congelante → Ótima → Quente
- **Umidade** (%) - Classificação: Muito Seco → Normal → Muito Úmido
- **Pressão** (hPa)
- **Radiação UV** (índice) - Classificação: Baixa → Alta → Extrema
- **Qualidade do Ar** (ppm)
- **Precipitação** (mm) - Status: Seco → Garoa → Chovendo

Formatação via `MetricService` com cores Tailwind.

## 📋 Relatórios

Acesso: `/admin/reports`

Formatos:
- **HTML**: Visualização no painel com tabela paginada
- **CSV**: Download direto
- **PDF**: Geração com JsPDF + AutoTable (botão no modal)

Filtros:
- Período (hoje, semana, mês, ano, customizado)
- Emitente (nome do usuário que gera)

## 👥 Gerenciamento de Usuários

Acesso: `/admin` (admin only)

Ações:
- Criar novo usuário (`/admin/users/create`)
- Alterar senha própria (`/admin/profile`)
- Deletar usuário (`/admin/users/delete/{id}`)
- Recuperar senha (`/admin/password/forgot` - público)

RBAC:
- **admin**: Acesso total, gerenciar usuários, relatórios, configurações
- **user**: Acesso limitado (ver dados, alterar própria senha, relatórios)

## 🛠️ Middleware Stack

Ordem de execução:

1. `SessionMiddleware` - Inicializa sessão PHP
2. `AuthMiddleware` - Valida autenticação (redirect para `/admin/login`)
3. `CsrfMiddleware` - Valida CSRF em POST (exceto `/admin/login`)

## 📡 Integração Thinger.io

Configurar via Dashboard Admin: `/admin/settings`

Campos:
- **Usuário**: Seu usuário Thinger
- **Device**: ID do device
- **Resource**: Caminho do resource (ex.: `clima/actual`)
- **Token**: Bearer token ou token simples

Validação automática ao salvar.

## 🖥️ Deploy

### Estrutura
- Docroot web deve apontar para `public/`
- Backend roda em `public/index.php`

### ⚠️ Instalação em Servidores Compartilhados (HostGator, etc)

Se o servidor **NÃO permite rodar `composer install`**:

1. **Gerar vendor localmente**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Compactar com vendor** (~15-20 MB):
   ```bash
   7z a clima_ete.7z .
   ```

3. **Fazer upload e descompactar** no servidor

4. **NÃO** tentar instalar composer no servidor

### Passos Normais
1. Instalar dependências: `composer install --no-dev --optimize-autoloader`
2. Criar `.env` com credenciais
3. Executar setup: `GET /setup` (primeira vez)
4. Configurar Thinger.io
5. Agendar cron para sincronização
6. Verificar logs em `var/log/`

### Sessões em Ambientes Compartilhados
Se `/tmp` não é adequado (cPanel, HostGator), crie diretório dedicado:

```bash
mkdir -p /home/usuario/tmp/clima_sessions
chmod 700 /home/usuario/tmp/clima_sessions
```

Configure em `public/php.ini`:
```ini
session.save_handler = files
session.save_path = "/home/usuario/tmp/clima_sessions"
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = "Lax"
```

## 📝 Changelog

### V3
- ✅ Sistema completo com usuários
- ✅ Relatórios em PDF com JsPDF
- ✅ Todas as features integradas
- ✅ Slim 4 com DI Container
- ✅ RBAC funcional
- ✅ Recuperação de senha
- ✅ README.md atualizado

### V2
- Atualização de arquitetura
- Slim Framework 4
- Dependency Injection
- Middleware PSR-15

### V1
- Versão inicial funcional
- Controllers e Services
- Integração Thinger.io

## 🤝 Contribuição

Para contribuir:

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -am 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## 🐛 Troubleshooting

**Conexão DB falhando**
- Verifique `.env` com credenciais corretas
- Erro `DatabaseConfigException`: `.env` ausente ou inválido
- Veja `var/log/` para detalhes

**Rotas quebradas**
- Confirme docroot apontando para `public/`
- Verifique `public/index.php` e routes

**Sessões não persistem**
- Verifique `session.save_path` em `public/php.ini`
- Teste permissões do diretório (755 ou 700)

**Thinger.io falhando**
- Valide token e resource em Dashboard
- Verifique logs em `var/log/`
- `fetchThingerData()` retorna status/mensagem detalhada

**E-mail de reset não chega**
- Função `mail()` requer configuração SMTP
- Substitua em `PasswordResetService` para usar provedor externo (SendGrid, etc)

## 📞 Suporte

- Abra uma issue no GitHub
- Verifique documentação em `docs/`
- Entre em contato com o administrador

## 📄 Licença

Propriedade da ETE Pedro Leão Leal. © 2025

---

**Desenvolvido com ❤️ por Leo Lima**
**Stack**: PHP 8.1+, Slim Framework 4, MySQL, Tailwind CSS, Lucide Icons
