# 📦 Guia Completo de Deploy no HostGator com Mesclagem de Banco de Dados

**Data**: 16 de dezembro de 2025  
**Sistema**: Estação Climática ETE - Sistema de Monitoramento Ambiental  
**Versão**: 1.0.0  

---

## 📋 Índice

1. [Pré-requisitos](#pré-requisitos)
2. [Fase 1: Preparação Local](#fase-1-preparação-local)
3. [Fase 2: Backup e Exportação](#fase-2-backup-e-exportação)
4. [Fase 3: Preparação HostGator](#fase-3-preparação-hostgator)
5. [Fase 4: Migração do Banco de Dados](#fase-4-migração-do-banco-de-dados)
6. [Fase 5: Deploy do Código](#fase-5-deploy-do-código)
7. [Fase 6: Configuração Pós-Deploy](#fase-6-configuração-pós-deploy)
8. [Fase 7: Testes e Validação](#fase-7-testes-e-validação)
9. [Troubleshooting](#troubleshooting)
10. [Rollback](#rollback)

---

## 🔧 Pré-requisitos

### Local (Seu Computador)
- ✅ Git instalado e configurado
- ✅ PHP 8.1+ (testar: `php -v`)
- ✅ Composer (testar: `composer --version`)
- ✅ MySQL/MariaDB client (testar: `mysql --version`)
- ✅ 7-Zip ou WinRAR para compactação

### HostGator
- ✅ Conta HostGator ativa com acesso SSH
- ✅ cPanel disponível
- ✅ MySQL/MariaDB habilitado
- ✅ Mínimo 1GB espaço livre
- ✅ PHP 8.1+ no servidor (verificar com suporte)

---

## 🚀 Fase 1: Preparação Local

### 1.1 Validar Código Localmente

```bash
# Entrar no diretório do projeto
cd c:\PROJETOS\clima_ete_novo

# Validar sintaxe PHP
php -l src/Controller/PublicController.php
php -l src/Controller/AdminController.php
php -l src/Controller/AuthController.php
php -l src/Service/*.php
php -l src/Repository/*.php

# Verificar se há erros
composer validate

# Listar todos os controllers
dir src/Controller
```

### 1.2 Limpar Arquivos Desnecessários

```bash
# Remover arquivos temporários
Remove-Item -Path "var/log/*" -Exclude ".gitkeep"
Remove-Item -Path "var/pdf/*" -Exclude ".gitkeep"

# ✅ NOTA: Vendor está em .gitignore - não será versionado
# Ele será instalado automaticamente no servidor com composer install

# Remover arquivos de configuração sensíveis
Remove-Item -Path ".env"  # Não incluir em deploy
Remove-Item -Path "db_config.php" -Force  # Será recriado no HostGator

# Validar que tudo está pronto
composer validate
composer install --no-dev --optimize-autoloader  # Testa instalação localmente
```

### 1.3 Verificar Estrutura do Banco

```bash
# Conectar ao banco local
mysql -u root -p clima_ete

# Executar dentro do MySQL:
SHOW TABLES;
DESCRIBE clima_users;
DESCRIBE clima_historico;
DESCRIBE clima_config;

# Contar registros importantes
SELECT COUNT(*) as total_users FROM clima_users;
SELECT COUNT(*) as total_historicos FROM clima_historico;
SELECT * FROM clima_config;

# Sair do MySQL
EXIT;
```

---

## 💾 Fase 2: Backup e Exportação

### 2.1 Exportar Banco de Dados Local

```bash
# Criar pasta de backup
mkdir c:\PROJETOS\clima_ete_novo\backup
cd c:\PROJETOS\clima_ete_novo\backup

# Exportar banco de dados COMPLETO
# (Data: 16-12-2025)
mysqldump -u root -p clima_ete > clima_ete_backup_20251216.sql

# Exportar com estrutura e dados separados (opcional)
mysqldump -u root -p --no-data clima_ete > clima_ete_schema_20251216.sql
mysqldump -u root -p --no-create-info clima_ete > clima_ete_data_20251216.sql
```

**Arquivo gerado**: `clima_ete_backup_20251216.sql` (~2-5 MB)

### 2.2 Exportar Usuários

```bash
# Dentro do MySQL:
SELECT * FROM clima_users;

# Salvar resultado em arquivo de texto
# Formato: username | password_hash | role
# Exemplo:
# admin | $2y$10$... | admin
# user1 | $2y$10$... | user
```

### 2.3 Criar Arquivo de Configuração de Variáveis

Criar arquivo `.env.example` atualizado:

```bash
# Arquivo: c:\PROJETOS\clima_ete_novo\.env.example
DB_HOST=localhost
DB_NAME=clima_ete
DB_USER=clima_ete_user
DB_PASS=MUDAR_SENHA_AQUI
DB_CHARSET=utf8mb4

THINGER_USER=seu_usuario_thinger
THINGER_DEVICE=seu_device_id
THINGER_RESOURCE=data
THINGER_TOKEN=seu_token_thinger

CLIMA_CRON_KEY=sua_chave_secreta_cron
```

### 2.4 Criar Arquivo de Checklist Pré-Deploy

```bash
# Criar arquivo: DEPLOY_CHECKLIST_20251216.md

## ✅ Checklist Pré-Deploy

- [ ] Banco de dados local validado
- [ ] Backup exportado: clima_ete_backup_20251216.sql
- [ ] Usuários documentados
- [ ] Variáveis de ambiente listadas
- [ ] Código validado (sem erros PHP)
- [ ] Composer dependencies otimizadas
- [ ] Arquivos temporários removidos
- [ ] README.md atualizado
- [ ] CHANGELOG.md atualizado
```

---

## 🌐 Fase 3: Preparação HostGator

### 3.1 Acessar cPanel HostGator

1. Abrir browser
2. Acessar: `https://seu-dominio.com.br:2083` ou `https://seu-ip-hostgator:2083`
3. Login com credenciais cPanel

### 3.2 Criar Banco de Dados

**Via cPanel:**

1. Ir para **MySQL Databases** (ou **Databases**)
2. Criar novo banco:
   - Nome: `seu_usuario_clima_ete` (HostGator adiciona prefixo automaticamente)
   - Clicar em "Create Database"

3. Criar usuário MySQL:
   - Nome: `seu_usuario_clima`
   - Senha: **Gerar senha forte** (mínimo 16 caracteres)
   - Clicar em "Create User"

4. Associar usuário ao banco:
   - Selecionar usuário e banco
   - Dar permissões: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP
   - Clicar em "Add User to Database"

**Salvar as credenciais:**
```
DB_HOST: localhost (geralmente)
DB_NAME: seu_usuario_clima_ete
DB_USER: seu_usuario_clima
DB_PASS: senha_gerada_acima
```

### 3.3 Verificar Versão PHP

**Via cPanel:**

1. Ir para **PHP Configuration** (ou **PHP Version**)
2. Selecionar versão 8.1 ou superior
3. Se não disponível, contactar suporte HostGator

### 3.4 Criar Estrutura de Diretórios

**Via FTP ou File Manager:**

```
public_html/
├── index.php (será substituído)
├── assets/
│   └── img/
├── var/
│   ├── log/
│   └── pdf/
├── src/
├── lib/
├── vendor/
└── .env (será criado)
```

---

## 🔄 Fase 4: Migração do Banco de Dados

### 4.1 Caso A: Banco HostGator VAZIO (Primeira Deploy)

```bash
# Acesso via SSH (terminal no HostGator)
ssh seu_usuario@seu_ip_hostgator

# Importar backup completo
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete < /home/seu_usuario/clima_ete_backup_20251216.sql

# Verificar importação
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete
> SHOW TABLES;
> SELECT COUNT(*) FROM clima_historico;
> EXIT;
```

### 4.2 Caso B: Banco HostGator COM DADOS (Mesclagem)

**⚠️ IMPORTANTE: Fazer backup do banco HostGator primeiro!**

```bash
# 1. Exportar banco HostGator ATUAL
mysqldump -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete > clima_ete_hostgator_backup_20251216.sql

# 2. Comparar estruturas
# Tabelas que DEVEM existir no HostGator:
# - clima_users (usuários do sistema)
# - clima_historico (dados de sensores)
# - clima_config (configurações)

# 3. Opção A: Preservar usuários HostGator e adicionar dados novo
# Exportar apenas estrutura e dados (sem usuários)
mysqldump -h localhost -u seu_usuario_clima -p --ignore-table=seu_usuario_clima_ete.clima_users seu_usuario_clima_ete > clima_ete_dados_20251216.sql

# 4. Importar dados (mantém usuários antigos)
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete < clima_ete_dados_20251216.sql

# 5. Verificar integridade
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete
> SELECT * FROM clima_users;  -- Deve mostrar usuários HostGator + novos
> SELECT COUNT(*) FROM clima_historico;
> EXIT;
```

### 4.3 Caso C: Sincronizar Tabelas (Recomendado)

```bash
# 1. No HostGator, executar script de schema:
# (Se tiver acesso SSH)

# Conectar via SSH
ssh seu_usuario@seu_ip_hostgator

# Entrar na pasta public_html
cd public_html

# Executar setup para atualizar schema
php setup.php

# Será criado/atualizado:
# - clima_users (preserva dados existentes)
# - clima_historico (preserva dados existentes)
# - clima_config (preserve dados existentes)
```

---

## 📤 Fase 5: Deploy do Código

### 5.1 Preparar Arquivo para Upload

```bash
# Criar arquivo compactado
# O vendor NÃO será incluído (está em .gitignore)
# Será instalado no servidor com: composer install --no-dev --optimize-autoloader

# Arquivo será compactado com tudo que está versionado:
# - src/, lib/, public/, docs/
# - composer.json, composer.lock
# - README.md, .gitignore

# Usando 7-Zip:
cd c:\PROJETOS\clima_ete_novo

# Criar arquivo .7z (git tracked files only)
git archive --format zip -o clima_ete_2025_12_17.zip HEAD

# Ou com 7z (mais compacto):
7z a -xr!.env -xr!.git -xr!backup -xr!vendor_bkp -xr!node_modules -xr!vendor clima_ete_2025_12_17.7z

# Resultado: arquivo ~5-10 MB (código sem vendor)
# Vendor será instalado no servidor (~8-10 MB após install)
```

### 5.2 Upload via FTP

**Usando FileZilla ou similar:**

1. Conectar ao servidor FTP HostGator
   - Host: `ftp.seu-dominio.com.br`
   - Usuário: seu_usuario_ftp
   - Senha: senha_ftp
   - Porta: 21

2. Navegar para `public_html/`

3. Fazer upload de:
   - `clima_ete_2025_12_17.zip` (ou .7z - arquivo compactado, ~5-10 MB)
   - `backup/clima_ete_backup_20251217.sql` (para referência/rollback)

### 5.3 Descompactar no Servidor

**Via SSH:**

```bash
# SSH no HostGator
ssh seu_usuario@seu_ip_hostgator

# Entrar na pasta
cd public_html

# Descompactar (escolha um)
unzip clima_ete_2025_12_17.zip
# OU
7z x clima_ete_2025_12_17.7z

# Remover arquivo compactado
rm clima_ete_2025_12_17.zip  # ou .7z

# Listar para confirmar
ls -la
```

### 5.4 Restaurar Diretórios Importantes

```bash
# No HostGator, restaurar permissões
chmod -R 755 public_html/
chmod -R 755 src/
chmod -R 755 lib/
chmod 777 var/log
chmod 777 var/pdf

# Garantir que .htaccess está correto
cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L]
</IfModule>
EOF
```

---

## ⚙️ Fase 6: Configuração Pós-Deploy

### 6.1 Criar Arquivo .env no HostGator

**Via SSH:**

```bash
# Criar arquivo .env
cat > .env << 'EOF'
DB_HOST=localhost
DB_NAME=seu_usuario_clima_ete
DB_USER=seu_usuario_clima
DB_PASS=SENHA_GERADA_NO_CPANEL
DB_CHARSET=utf8mb4

THINGER_USER=seu_usuario_thinger
THINGER_DEVICE=seu_device_id
THINGER_RESOURCE=data
THINGER_TOKEN=seu_token_thinger

CLIMA_CRON_KEY=gerar_chave_segura_aqui
EOF

# Proteger arquivo
chmod 600 .env
```

### 6.2 Instalar Dependências Composer

**Via SSH:**

```bash
# Entrar na pasta
cd /home/seu_usuario/public_html

# Instalar dependências (vendor será criado aqui)
# Dependências foram otimizadas: 5 packages diretos, ~15 totais
composer install --no-dev --optimize-autoloader

# Resultado: vendor/ criado com ~8-10 MB

# Verificar se há erros
php -l src/Controller/PublicController.php
php -l src/Service/*.php
php -l src/Repository/*.php

# Validar composer
composer validate
```

### 6.3 Executar Setup Script

**Via SSH:**

```bash
# Executar setup para criar/validar tabelas
php setup.php

# Resultado esperado:
# ✅ Database connected
# ✅ Tables created/verified
# ✅ Setup completed successfully
```

### 6.4 Configurar Cron Job (Sincronização)

**Via cPanel > Cron Jobs:**

1. Ir para **Cron Jobs**
2. Adicionar novo cron job:
   - **Minute**: 15 (a cada hora, no minuto 15)
   - **Hour**: * (todas as horas)
   - **Day**: * (todos os dias)
   - **Month**: * (todos os meses)
   - **Day of Week**: * (todos os dias)
   - **Command**: 
     ```
     /usr/bin/php /home/seu_usuario/public_html/sync_cron.php -k=SEU_CLIMA_CRON_KEY
     ```

3. Clicar em "Add New Cron Job"

**Ou via SSH:**

```bash
# Editar crontab
crontab -e

# Adicionar linha:
15 * * * * /usr/bin/php /home/seu_usuario/public_html/sync_cron.php -k=SEU_CLIMA_CRON_KEY >> /home/seu_usuario/public_html/var/log/cron.log 2>&1
```

### 6.5 Configurar Email para Alertas (Opcional)

Se quiser receber alertas de sincronização:

```bash
# Editar lib/thinger.php
# Adicionar código para enviar email em caso de erro

# Ou configurar webhook do Thinger.io para notificar
```

---

## ✅ Fase 7: Testes e Validação

### 7.1 Testes de Conectividade

```bash
# 1. Acessar página pública
curl https://seu-dominio.com.br/

# Resultado esperado: HTML da página inicial

# 2. Testar API
curl https://seu-dominio.com.br/live?api=1 -H "Accept: application/json"

# Resultado esperado: JSON com dados climáticos

# 3. Testar painel ao vivo
curl https://seu-dominio.com.br/live

# Resultado esperado: HTML do painel ao vivo
```

### 7.2 Testes de Banco de Dados

**Via SSH:**

```bash
# Conectar ao banco
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete

# Dentro do MySQL:
-- Verificar dados
SELECT COUNT(*) as total_historicos FROM clima_historico;
SELECT COUNT(*) as total_usuarios FROM clima_users;
SELECT COUNT(*) as total_configs FROM clima_config;

-- Verificar integridade
SHOW TABLES;
DESCRIBE clima_historico;

-- Sair
EXIT;
```

### 7.3 Testes de Login

1. Abrir browser
2. Acessar: `https://seu-dominio.com.br/admin/login`
3. Testar login com usuários do HostGator
4. Verificar se acesso funciona

### 7.4 Testes de Exportação

1. Acessar `/live` (painel ao vivo)
2. Clicar em "Baixar CSV 24h"
   - Deve gerar arquivo `.csv` com dados
3. Clicar em "Imprimir / Salvar como PDF"
   - Deve abrir caixa de impressão do navegador

### 7.5 Testes de Sincronização

```bash
# No HostGator, forçar sincronização manual
php sync_cron.php -k=SEU_CLIMA_CRON_KEY

# Verificar log
tail -f var/log/clima_ete.log

# Resultado esperado:
# ✅ Sync completed successfully
# ✅ Records inserted: 10
# ✅ Last sync: 2025-12-16 14:30:00
```

### 7.6 Checklist de Validação

```markdown
## ✅ Validação Pós-Deploy

- [ ] Página pública carrega corretamente
- [ ] Painel ao vivo exibe dados
- [ ] Login funciona
- [ ] Banco de dados conectado
- [ ] Tabelas possuem dados
- [ ] Usuários visíveis
- [ ] CSV export funciona
- [ ] PDF export funciona
- [ ] Sync cron executado
- [ ] Sem erros nos logs
- [ ] HTTPS funciona
- [ ] Redirecionamento HTTP → HTTPS OK
```

---

## 🔧 Troubleshooting

### ❌ Problema: "Error: No such file or directory"

**Solução:**
```bash
# Verificar se arquivo existe
ls -la setup.php
ls -la sync_cron.php

# Se não existir, fazer upload novamente
# Se existir, verificar permissões
chmod +x setup.php
chmod +x sync_cron.php
```

### ❌ Problema: "Fatal error: Class 'Slim\Psr7\Response' not found"

**Solução:**
```bash
# Reinstalar Composer dependencies
cd public_html
rm -rf vendor
composer install --no-dev --optimize-autoloader

# Verificar se autoload existe
ls -la vendor/autoload.php
```

### ❌ Problema: "SQLSTATE[HY000]: General error: 2006 MySQL server has gone away"

**Solução:**
```bash
# Verificar conexão MySQL
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete

# Se falhar, contactar suporte HostGator
# Se funcionar, problema pode ser timeout - aumentar em .env
# Adicionar: DB_TIMEOUT=600
```

### ❌ Problema: "Permission denied" em var/log ou var/pdf

**Solução:**
```bash
# Mudar permissões
chmod 777 var/log
chmod 777 var/pdf

# Ou usar permissão mais restrita
chmod 755 var/log
chmod 755 var/pdf
chown -R seu_usuario_apache var/log
chown -R seu_usuario_apache var/pdf
```

### ❌ Problema: Sync não está rodando

**Solução:**
```bash
# Verificar se cron job está ativo
crontab -l

# Se não aparecer, adicionar novamente:
crontab -e

# Testar manualmente
/usr/bin/php /home/seu_usuario/public_html/sync_cron.php -k=SEU_CLIMA_CRON_KEY

# Verificar se há erros
php sync_cron.php -k=SEU_CLIMA_CRON_KEY 2>&1 | tail -20
```

### ❌ Problema: CSV export não funciona

**Solução:**
```bash
# Testar acesso direto
curl https://seu-dominio.com.br/live?format=csv&period=24

# Se retornar erro, verificar se method liveCsv() existe
grep -n "private function liveCsv" src/Controller/PublicController.php

# Se não encontrar, fazer upload novamente do arquivo
```

---

## ⏮️ Rollback

### Caso 1: Problema no Código

```bash
# No HostGator via SSH:

# 1. Backup do código problemático
mv public_html public_html.broken_20251216

# 2. Restaurar código anterior (salvo em arquivo .tar.gz)
tar -xzf clima_ete_anterior.tar.gz -C public_html

# 3. Testear
curl https://seu-dominio.com.br/

# 4. Se funcionar, pode remover backup quebrado
rm -rf public_html.broken_20251216
```

### Caso 2: Problema no Banco de Dados

```bash
# No HostGator via SSH:

# 1. Fazer backup do banco atual (se precisar manter dados)
mysqldump -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete > clima_ete_broken_20251216.sql

# 2. Restaurar backup anterior
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete < clima_ete_backup_antes_deploy.sql

# 3. Verificar
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete
> SHOW TABLES;
> SELECT COUNT(*) FROM clima_historico;
> EXIT;
```

### Caso 3: Reverter Tudo

```bash
# Se tudo falhar e HostGator ficou inutilizável:

# 1. Limpar public_html completamente
rm -rf public_html/*

# 2. Restaurar código anterior
cd public_html
tar -xzf /home/seu_usuario/backup/clima_ete_anterior.tar.gz

# 3. Restaurar banco de dados
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete < /home/seu_usuario/backup/clima_ete_anterior.sql

# 4. Restaurar .env
cp /home/seu_usuario/backup/.env .env

# 5. Testar
curl https://seu-dominio.com.br/
```

---

## 📞 Suporte Rápido

### Contatos Importantes

| Serviço | Contato | Prioridade |
|---------|---------|-----------|
| HostGator Support | suporte@hostgator.com.br | Alta |
| Thinger.io Support | support@thinger.io | Média |
| Sistema Local | seu_email@dominio.com | Alta |

### Documentação Referência

- [HostGator Knowledge Base](https://suporte.hostgator.com.br/)
- [Slim Framework 4 Docs](https://www.slimframework.com/docs/v4/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [PHP 8.1 Documentation](https://www.php.net/docs.php)

---

## 📝 Notas Importantes

1. **Sempre fazer backup antes de qualquer operação**
2. **Testar tudo localmente antes de deploy**
3. **Manter senha do banco segura (mínimo 16 caracteres)**
4. **Registrar todas as alterações realizadas**
5. **Documentar problemas encontrados e soluções**
6. **Comunicar com suporte HostGator antes de problemas maiores**

---

## ✨ Próximas Ações

- [ ] Revisar este guia
- [ ] Coletar credenciais HostGator
- [ ] Fazer backup local
- [ ] Preparar cronograma de deploy
- [ ] Comunicar a equipe sobre downtime (se houver)
- [ ] Executar deploy em horário de baixa demanda
- [ ] Monitorar sistema por 24h após deploy
- [ ] Documentar lições aprendidas

---

**Documento criado em**: 16 de dezembro de 2025  
**Responsável**: Seu Nome / Equipe  
**Versão**: 1.0.0  
**Status**: ✅ Pronto para Deploy
