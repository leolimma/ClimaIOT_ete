# 📁 Estrutura Completa do Projeto

Visão detalhada de todos os arquivos e diretórios do sistema.

---

## 📊 Árvore de Diretórios

```
clima_ete_novo/
│
├── 📄 README.md                              # Documentação principal
├── 📄 CHANGELOG.md                           # Histórico de versões
├── 📄 composer.json                          # Dependências PHP
├── 📄 composer.lock                          # Lock file Composer
├── 📄 .env.example                           # Exemplo de variáveis
├── 📄 .gitignore                             # Arquivos ignorados Git
│
├── 📁 public/                                # Raiz web (document root)
│   ├── 📄 index.php                          # Entry point Slim Framework
│   ├── 📄 .htaccess                          # Rewrite rules Apache
│   └── 📁 assets/                            # Arquivos estáticos
│       └── 📁 img/                           # Imagens
│           ├── agradece.jpg                  # Logo rodapé
│           ├── agradece.png                  # Logo em PNG
│           ├── logo_1.png                    # Logo principal
│           ├── tecnoambiente_logo.png        # Logo técnica
│           └── favico.png                    # Favicon
│
├── 📁 src/                                   # Código-fonte PHP
│   ├── 📁 Controller/                        # Controllers (MVC)
│   │   ├── PublicController.php              # Rotas públicas (/live, /csv, /pdf)
│   │   ├── AdminController.php               # Painel admin (/admin)
│   │   ├── AuthController.php                # Login/Logout
│   │   ├── CronController.php                # Cron jobs
│   │   ├── SetupController.php               # Setup (/setup)
│   │   └── RelatoriosController.php          # Relatórios (admin)
│   │
│   ├── 📁 Service/                           # Serviços (Lógica de Negócio)
│   │   ├── AuthService.php                   # Autenticação
│   │   ├── ConfigService.php                 # Configurações
│   │   ├── MetricService.php                 # Cálculos de métricas
│   │   ├── PasswordResetService.php          # Reset de senha
│   │   ├── PublicViewService.php             # Dados públicos
│   │   ├── SetupService.php                  # Setup do sistema
│   │   └── SyncService.php                   # Sincronização Thinger
│   │
│   ├── 📁 Repository/                        # Data Access Layer
│   │   ├── ConfigRepository.php              # CRUD clima_config
│   │   ├── HistoricsRepository.php           # CRUD clima_historico
│   │   ├── PasswordResetRepository.php       # CRUD reset tokens
│   │   └── UserRepository.php                # CRUD clima_users
│   │
│   ├── 📁 Middleware/                        # Middlewares Slim
│   │   ├── SessionMiddleware.php             # Gerencia sessões
│   │   ├── AuthMiddleware.php                # Valida autenticação
│   │   └── CsrfMiddleware.php                # Proteção CSRF
│   │
│   └── 📁 Settings/                          # Configurações Slim
│       └── settings.php                      # DI Container
│
├── 📁 lib/                                   # Bibliotecas reutilizáveis
│   ├── db.php                                # Conexão PDO MySQL
│   ├── schema.php                            # Criação/Validação tabelas
│   └── thinger.php                           # Integração Thinger.io
│
├── 📁 bin/                                   # Scripts executáveis
│   ├── console                               # CLI console
│   └── reset_admin.php                       # Reset senha admin
│
├── 📁 migrations/                            # Migrações BD
│   └── V1__init_tables.php                   # Schema inicial
│
├── 📁 var/                                   # Dados dinâmicos
│   ├── 📁 log/                               # Logs do sistema
│   │   └── clima_users_columns.json          # Cache schema
│   └── 📁 pdf/                               # PDFs gerados
│
├── 📁 vendor/                                # Dependências Composer
│   ├── slim/                                 # Slim Framework
│   ├── psr/                                  # PSR standards
│   ├── symfony/                              # Symfony console
│   └── ... (outros packages)                # Diversos
│
└── 📁 docs/                                  # Documentação
    ├── DEPLOY_HOSTGATOR_COMPLETO.md          # Guia deploy completo ✅
    ├── SCRIPTS_DEPLOY.md                     # Scripts prontos ✅
    ├── SUMARIO_EXECUTIVO.md                  # Este documento ✅
    ├── DEPLOY_CHECKLIST.md                   # Checklist deploy
    ├── MANUTENCAO_RELATORIOS.md              # Manutenção de relatórios
    ├── RELATORIOS_ARCHITECTURE.md            # Arquitetura relatórios
    └── terr6836_clima_ete.sql                # Backup referência
```

---

## 🔑 Arquivos Críticos

### Controllers

| Arquivo | Rota | Funcionalidade |
|---------|------|----------------|
| **PublicController.php** | `/` | Home, live, CSV, PDF export |
| **AdminController.php** | `/admin` | Dashboard, config, sync |
| **AuthController.php** | `/login`, `/logout` | Autenticação |
| **CronController.php** | `/cron/sync` | Sincronização Thinger |
| **SetupController.php** | `/setup` | Configuração inicial |

### Services

| Arquivo | Responsabilidade |
|---------|------------------|
| **AuthService.php** | Validação login, hash senha |
| **SyncService.php** | Fetch dados Thinger, persistência |
| **ConfigService.php** | Get/Set configurações BD |
| **PublicViewService.php** | Dados para painel público |
| **MetricService.php** | Classificação métricas (temp, UV, etc) |

### Repositories

| Arquivo | Tabela | Operações |
|---------|--------|-----------|
| **UserRepository.php** | clima_users | CREATE, READ, UPDATE, DELETE |
| **HistoricsRepository.php** | clima_historico | INSERT, SELECT, AGGREGATE |
| **ConfigRepository.php** | clima_config | GET, SET |

### Bibliotecas

| Arquivo | Função |
|---------|--------|
| **db.php** | Gerencia conexão PDO, getConfigValue(), setConfigValue() |
| **schema.php** | ensureSchema(), cria/altera tabelas |
| **thinger.php** | fetchThingerData(), getThingerSettings() |

---

## 📊 Banco de Dados

### Tabela: `clima_users`

```sql
CREATE TABLE clima_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),    -- Novo (hash bcrypt)
    password VARCHAR(255),          -- Legado (suportado)
    role ENUM('admin', 'user'),    -- RBAC
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    locked_until TIMESTAMP NULL,   -- Lock por tentativas
    login_attempts INT DEFAULT 0
);
```

### Tabela: `clima_historico`

```sql
CREATE TABLE clima_historico (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data_registro DATETIME,
    temp DECIMAL(5,2),             -- Temperatura
    hum INT,                        -- Umidade
    pres DECIMAL(7,2),             -- Pressão
    uv DECIMAL(5,2),               -- Radiação UV
    gas DECIMAL(8,2),              -- Qualidade do Ar
    chuva DECIMAL(5,2),            -- Precipitação
    chuva_status VARCHAR(20),      -- "Seco" / "Chuva" / "Tempestade"
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (data_registro),
    INDEX (created_at)
);
```

### Tabela: `clima_config`

```sql
CREATE TABLE clima_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(50) UNIQUE,       -- Chave configuração
    valor LONGTEXT,                 -- Valor (JSON, string, etc)
    tipo VARCHAR(20),               -- "string", "int", "json"
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Exemplos de chaves:
-- thinger_user, thinger_device, thinger_resource, thinger_token
-- cron_key, setup_done, last_sync, error_count
```

---

## 🚀 Fluxos Principais

### 1️⃣ Home Page (`/`)

```
GET /
  ↓
PublicController::home()
  ↓
PublicViewService::getLandingData()
  ↓
QueryDB: clima_historico (último registro)
  ↓
Calcular status (online/atenção/offline)
  ↓
Renderizar template HTML
```

### 2️⃣ Painel Ao Vivo (`/live`)

```
GET /live
  ↓
PublicController::live()
  ↓
PublicViewService::getLiveData()
  ↓
QueryDB: clima_historico (últimos 48h)
  ↓
Processar para gráfico Chart.js
  ↓
Renderizar painel HTML
```

### 3️⃣ Export CSV (`/live?format=csv&period=24`)

```
GET /live?format=csv&period=24
  ↓
PublicController::liveCsv('24')
  ↓
PublicViewService::getHistoryForExport('24')
  ↓
QueryDB: clima_historico (últimas 24h)
  ↓
Formatar CSV com BOM UTF-8
  ↓
Download arquivo
```

### 4️⃣ Export PDF (`/live?format=pdf&period=24`)

```
GET /live?format=pdf&period=24
  ↓
PublicController::livePdf('24')
  ↓
Gerar HTML com dados
  ↓
Usuário clica botão "Imprimir"
  ↓
window.print() (print dialog)
  ↓
Usuário escolhe "Salvar como PDF"
```

### 5️⃣ Sincronização Cron (`/cron/sync`)

```
GET /cron/sync?key=CHAVE_SECRETA (ou via cron job)
  ↓
CronController::sync()
  ↓
Validar chave
  ↓
SyncService::syncWithThinger()
  ↓
Fetch dados Thinger.io (HTTP GET)
  ↓
Normalizar tipos
  ↓
Calcular chuva_status
  ↓
Insert em clima_historico
  ↓
Update clima_config (last_sync)
  ↓
Log resultado
```

### 6️⃣ Login Admin (`/admin/login`)

```
GET/POST /admin/login
  ↓
AuthController::login()
  ↓
Se POST:
  - Validar CSRF (exceção: não valida na rota de login)
  - Buscar usuário em clima_users
  - Validar senha (hash bcrypt ou legado)
  - Check lock (tentativas excedidas)
  - Criar sessão
  - Redirect /admin
```

### 7️⃣ Dashboard Admin (`/admin`)

```
GET /admin
  ↓
SessionMiddleware (validar sessão)
  ↓
AuthMiddleware (validar autenticação)
  ↓
AdminController::dashboard()
  ↓
Carregar dados:
  - ConfigRepository::getAll()
  - UserRepository::getAll()
  - HistoricsRepository::getLatest()
  ↓
Renderizar dashboard
```

---

## 🔐 Segurança

### Validação
- ✅ CSRF token em POST (exceto login)
- ✅ Input sanitization com `cleanInput()`
- ✅ Output escaping com `htmlspecialchars()`
- ✅ Prepared statements em todas queries

### Autenticação
- ✅ Senha com `password_hash()` (bcrypt)
- ✅ Lock automático após 5 tentativas
- ✅ Token session com PHP nativo
- ✅ RBAC: admin vs user

### Autorização
- ✅ Middleware valida role
- ✅ UI condicional por role
- ✅ Admin-only endpoints protegidos

---

## 📦 Dependências Composer

```json
{
  "slim/slim": "^4.15.1",              // Framework web
  "slim/csrf": "^0.8.0",                // Proteção CSRF
  "psr/http-server-middleware": "^1.0", // PSR-15
  "symfony/console": "^7.0",            // CLI commands
  "php-di/php-di": "^7.0"              // Dependency Injection
}
```

**Sem dependências externas para:**
- ✅ PDF (usa window.print())
- ✅ Backup (usa mysqldump)
- ✅ Cron (usa agendador SO)

---

## 📝 Variáveis de Ambiente (.env)

```bash
# Banco de dados
DB_HOST=localhost
DB_NAME=clima_ete
DB_USER=clima_user
DB_PASS=senha_segura

# Thinger.io
THINGER_USER=seu_usuario
THINGER_DEVICE=seu_device
THINGER_RESOURCE=data
THINGER_TOKEN=seu_token

# Segurança
CLIMA_CRON_KEY=chave_secreta_aqui

# Charset
DB_CHARSET=utf8mb4
```

---

## 🧪 Como Executar Localmente

```bash
# 1. Clonar/entrar no projeto
cd c:\PROJETOS\clima_ete_novo

# 2. Instalar dependências
composer install

# 3. Copiar .env
copy .env.example .env

# 4. Editar credenciais
# Abrir .env e preencher DB_HOST, DB_NAME, etc

# 5. Criar banco
mysql -u root -p < docs/terr6836_clima_ete.sql

# 6. Executar setup
php setup.php

# 7. Iniciar servidor
php -S localhost:8000 -t public

# 8. Acessar
# http://localhost:8000
# http://localhost:8000/admin
```

---

## 📞 Estrutura de Pastas no HostGator

Após deploy, estrutura será:

```
/home/seu_usuario/
├── public_html/              # Document root
│   ├── index.php
│   ├── public/
│   ├── src/
│   ├── lib/
│   ├── vendor/
│   ├── var/
│   ├── .env                  # ⚠️ Proteger: chmod 600
│   └── .htaccess
│
├── backups/                  # Backup automático
│   ├── clima_ete_*.sql.gz
│   └── codigo_*.tar.gz
│
└── bin/                      # Scripts auxiliares
    └── setup.php
```

---

## ✨ Melhorias Futuras Propostas

| Prioridade | Funcionalidade | Estimativa |
|-----------|----------------|-----------|
| 🔴 Alta | Alertas por email | 2h |
| 🔴 Alta | Dashboard em tempo real (WebSocket) | 4h |
| 🟡 Média | Gráficos mensais/anuais | 2h |
| 🟡 Média | API REST com autenticação | 3h |
| 🟢 Baixa | APP mobile (React Native) | 20h |
| 🟢 Baixa | Integração Slack | 1h |

---

## 📞 Contatos e Links

| Item | Link/Contato |
|------|--------------|
| **Git Repository** | https://github.com/seu-repo/clima_ete |
| **HostGator Support** | suporte@hostgator.com.br |
| **Thinger.io Docs** | https://docs.thinger.io |
| **Slim Framework** | https://www.slimframework.com |
| **PHP Docs** | https://www.php.net |

---

## 📋 Licença e Créditos

- **Sistema**: Estação Climática ETE
- **Desenvolvedor**: Seu Nome
- **Instituição**: ETE Pedro Leão Leal
- **Data**: 2025
- **Status**: ✅ Produção

---

**Documento atualizado em**: 16 de dezembro de 2025
