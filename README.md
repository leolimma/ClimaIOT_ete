# Sistema de Monitoramento Climático - ETE Pedro Leão Leal

## Versão 3 (V3)

Sistema completo de monitoramento ambiental integrado com Thinger.io, desenvolvido em PHP com Slim Framework 4.

### 🎯 Features Principais

- **Dashboard em Tempo Real**: Visualização de dados climáticos ao vivo
- **Sistema de Usuários**: Autenticação com roles (admin/user) e RBAC
- **Relatórios em PDF**: Exportação de dados com JsPDF e AutoTable
- **Sincronização com Thinger.io**: Integração automática de dados IoT
- **Painel Administrativo**: Gerenciamento de configurações, usuários e sincronização
- **API REST**: Endpoints para acesso aos dados

### 📋 Requisitos

- PHP 8.2+
- MySQL 5.7+
- Composer
- Node.js (opcional, para build assets)

### 🚀 Instalação

#### 1. Clonar o repositório

```bash
git clone https://github.com/leolimma/ClimaIOT.git
cd ClimaIOT
```

#### 2. Configurar variáveis de ambiente

Criar arquivo `.env` na raiz:

```env
DB_HOST=localhost
DB_NAME=clima_ete
DB_USER=root
DB_PASS=sua_senha
DB_CHARSET=utf8mb4

THINGER_USER=seu_usuario
THINGER_DEVICE=seu_device
THINGER_RESOURCE=seu_resource
THINGER_TOKEN=seu_token
```

#### 3. Instalar dependências

```bash
composer install
```

#### 4. Provisionar banco de dados

Via web:
```
http://localhost:8000/setup
```

Ou CLI:
```powershell
php setup.php
```

#### 5. Iniciar servidor de desenvolvimento

```bash
php -S localhost:8000 -t public
```

Acesse: `http://localhost:8000`

### 🔑 Login Padrão

Após setup:
- **Usuário**: admin
- **Senha**: admin (alterar na primeira entrada)

### 📂 Estrutura do Projeto

```
clima_ete_novo/
├── bin/                          # Scripts CLI
│   └── reset_admin.php          # Reset de senha admin
├── docs/                        # Documentação
├── lib/                         # Bibliotecas PHP
│   ├── db.php                   # Conexão PDO
│   ├── schema.php               # Schema do banco
│   └── thinger.php              # API Thinger.io
├── migrations/                  # Migrações do banco
├── public/                      # Raiz web
│   ├── index.php               # Entry point Slim
│   └── assets/                 # Imagens e recursos
├── src/
│   ├── Controller/             # Controladores
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── RelatoriosController.php
│   │   └── PublicController.php
│   ├── Middleware/             # Middlewares
│   │   ├── SessionMiddleware.php
│   │   ├── AuthMiddleware.php
│   │   └── CsrfMiddleware.php
│   ├── Repository/             # Data access
│   ├── Service/                # Business logic
│   └── Settings/               # Configurações
└── var/                        # Logs e cache
```

### 🔐 Segurança

- **Autenticação**: Session-based com hash de senha
- **CSRF**: Token CSRF em todos os POST
- **SQL Injection**: Prepared statements com PDO
- **XSS**: Sanitização com `htmlspecialchars()`
- **RBAC**: Controle de acesso por role (admin/user)

### 🗄️ Banco de Dados

Tabelas principais:

- `clima_historico`: Leituras históricas de sensores
- `clima_config`: Configurações do sistema
- `clima_users`: Usuários e autenticação
- `clima_password_resets`: Token de reset de senha

### 🔄 Fluxo de Sincronização

1. **Manual**: Via dashboard admin → "Sincronizar Agora"
2. **Automático**: Via cron job
   ```bash
   curl "https://seu-site/cron/sync?key=SUA_CHAVE_CRON"
   ```
   Ou CLI:
   ```powershell
   php sync_cron.php -k=SUA_CHAVE_CRON
   ```

### 📊 Métricas Monitoradas

- **Temperatura** (°C)
- **Umidade** (%)
- **Pressão** (hPa)
- **Radiação UV** (índice)
- **Qualidade do Ar** (ppm)
- **Precipitação** (mm)

### 📋 Relatórios

Exportação disponível em:
- **CSV**: Download direto
- **PDF**: Com formatação profissional

Acesso: `/admin/reports`

### 👥 Gerenciamento de Usuários

- Criar novo usuário (admin)
- Alterar senha própria (todos)
- Deletar usuário (admin)
- Recuperação de senha (público)

### 🛠️ Middlewares

Ordem de execução:
1. `SessionMiddleware` - Inicializa sessão
2. `AuthMiddleware` - Valida autenticação
3. `CsrfMiddleware` - Valida CSRF (exceto login)

### 📡 Integração Thinger.io

Configurar em `/admin/settings`:
- **Usuário**: Seu usuário Thinger
- **Device**: ID do device
- **Resource**: Caminho do resource
- **Token**: Bearer token ou token simples

### 🐛 Debug

Logs disponíveis em: `var/log/`

```php
error_log('Mensagem de debug');
```

### 📝 Changelog

#### V3
- Sistema completo com usuários
- Relatórios em PDF
- Todas as features integradas
- Correção de conflitos de merge

#### V2
- Atualização de arquitetura
- Slim Framework 4
- Dependency Injection

#### V1
- Versão inicial funcional

### 🤝 Contribuição

Para contribuir:

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -am 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

### 📄 Licença

Este projeto é propriedade da ETE Pedro Leão Leal. © 2025

### 📞 Suporte

Para problemas ou dúvidas:
- Abra uma issue no GitHub
- Entre em contato com o administrador do sistema

---

**Desenvolvido com ❤️ por Leo Lima**
