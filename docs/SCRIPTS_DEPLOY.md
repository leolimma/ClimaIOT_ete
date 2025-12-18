# 🔧 Scripts Prontos para Deploy

Coleção de scripts e comandos para agilizar o processo de deploy.

---

## 📦 Script 1: Preparação Local Completa

**Arquivo**: `scripts/prepare_local.sh`

```bash
#!/bin/bash
# Script de preparação local para deploy
# Uso: bash scripts/prepare_local.sh

echo "🚀 Iniciando preparação local..."

# Validar PHP
echo "✓ Validando PHP..."
php -l src/Controller/PublicController.php
php -l src/Controller/AdminController.php
php -l src/Controller/AuthController.php

# Limpar cache
echo "✓ Limpando cache..."
rm -rf vendor
mkdir -p var/log
mkdir -p var/pdf
echo "" > var/log/.gitkeep
echo "" > var/pdf/.gitkeep

# Reinstalar Composer
echo "✓ Reinstalando Composer (modo produção)..."
composer install --no-dev --optimize-autoloader

# Criar backup do banco
echo "✓ Exportando banco de dados..."
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p backup
mysqldump -u root -p clima_ete > backup/clima_ete_backup_$BACKUP_DATE.sql

# Validar estrutura
echo "✓ Validando estrutura..."
if [ -f "public/index.php" ] && [ -d "src/Controller" ] && [ -d "vendor" ]; then
    echo "✅ Estrutura OK"
else
    echo "❌ Estrutura inválida!"
    exit 1
fi

# Criar arquivo de checklist
echo "✓ Gerando checklist..."
cat > DEPLOY_CHECKLIST_$BACKUP_DATE.txt << 'EOF'
DEPLOY CHECKLIST - $(date)

[ ] Código validado (sem erros PHP)
[ ] Backup criado: backup/clima_ete_backup_$BACKUP_DATE.sql
[ ] Composer otimizado
[ ] Variáveis de ambiente (.env) listadas
[ ] Usuários documentados
[ ] Arquivo compactado gerado
[ ] Arquivo enviado para FTP
[ ] HostGator pronto (banco criado, estrutura de pastas)

DADOS PARA HOSTGATOR:
DB_HOST: localhost
DB_NAME: seu_usuario_clima_ete
DB_USER: seu_usuario_clima
DB_PASS: [GERADO_NO_CPANEL]

EOF

echo ""
echo "✅ Preparação completa!"
echo "📁 Backup disponível em: backup/clima_ete_backup_$BACKUP_DATE.sql"
```

---

## 📤 Script 2: Upload para HostGator (PowerShell)

**Arquivo**: `scripts/upload_hostgator.ps1`

```powershell
# Script de upload para HostGator
# Uso: .\scripts\upload_hostgator.ps1

param(
    [string]$ftpHost = "seu_ftp_host",
    [string]$ftpUser = "seu_ftp_user",
    [string]$ftpPass = "sua_ftp_senha",
    [string]$deployDate = (Get-Date -Format "yyyyMMdd_HHmmss")
)

Write-Host "🚀 Iniciando upload para HostGator..." -ForegroundColor Cyan

# 1. Criar arquivo compactado
Write-Host "📦 Compactando código..." -ForegroundColor Yellow
$archiveName = "clima_ete_$deployDate.zip"

$exclude = @(".git", ".env", "backup", "vendor_bkp", "node_modules", ".vscode", "*.pdf")
Compress-Archive -Path "src", "lib", "public", "vendor", ".env.example" `
    -DestinationPath $archiveName -Force

Write-Host "✅ Arquivo criado: $archiveName" -ForegroundColor Green

# 2. Upload via FTP
Write-Host "📤 Enviando para HostGator..." -ForegroundColor Yellow

$ftpCredential = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
$ftpConnection = New-Object System.Net.FtpWebRequest("ftp://$ftpHost/public_html/$archiveName")
$ftpConnection.Credentials = $ftpCredential
$ftpConnection.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$ftpConnection.UseBinary = $true

$fileStream = [System.IO.File]::OpenRead($archiveName)
$uploadStream = $ftpConnection.GetRequestStream()
$fileStream.CopyTo($uploadStream)
$uploadStream.Dispose()
$fileStream.Dispose()

Write-Host "✅ Upload concluído!" -ForegroundColor Green

# 3. Gerar report
$report = @"
UPLOAD REPORT
=============
Data: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Arquivo: $archiveName
Tamanho: $(Get-Item $archiveName | ForEach-Object {$_.Length / 1MB} )MB
Destino: ftp://$ftpHost/public_html/

PRÓXIMOS PASSOS NO HOSTGATOR (SSH):
1. cd public_html
2. unzip $archiveName
3. php setup.php
4. Testar: curl https://seu-dominio.com.br/

"@

Write-Host $report

# Salvar report
$report | Out-File -FilePath "UPLOAD_REPORT_$deployDate.txt"
Write-Host "📄 Report salvo: UPLOAD_REPORT_$deployDate.txt" -ForegroundColor Cyan
```

---

## 🔐 Script 3: Configuração HostGator (SSH)

**Arquivo**: `scripts/setup_hostgator.sh`

```bash
#!/bin/bash
# Script de configuração no HostGator
# Executar via SSH: bash setup_hostgator.sh

set -e  # Exit on error

echo "🚀 Configurando HostGator..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 1. Validar permissões
echo -e "${YELLOW}1. Validando permissões...${NC}"
cd public_html

chmod -R 755 src/
chmod -R 755 lib/
chmod -R 755 public/
chmod 777 var/log
chmod 777 var/pdf

echo -e "${GREEN}✅ Permissões OK${NC}"

# 2. Criar .env
echo -e "${YELLOW}2. Criando .env...${NC}"

cat > .env << 'EOF'
DB_HOST=localhost
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_CHARSET=utf8mb4

THINGER_USER=${THINGER_USER}
THINGER_DEVICE=${THINGER_DEVICE}
THINGER_RESOURCE=data
THINGER_TOKEN=${THINGER_TOKEN}

CLIMA_CRON_KEY=$(openssl rand -base64 32)
EOF

chmod 600 .env
echo -e "${GREEN}✅ .env criado${NC}"

# 3. Instalar Composer
echo -e "${YELLOW}3. Instalando Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

echo -e "${GREEN}✅ Composer OK${NC}"

# 4. Executar setup
echo -e "${YELLOW}4. Executando setup...${NC}"
php setup.php

echo -e "${GREEN}✅ Setup concluído${NC}"

# 5. Validar banco
echo -e "${YELLOW}5. Validando banco de dados...${NC}"
mysql -h localhost -u ${DB_USER} -p${DB_PASS} ${DB_NAME} << 'MYSQL'
SHOW TABLES;
SELECT COUNT(*) as usuarios FROM clima_users;
SELECT COUNT(*) as historicos FROM clima_historico;
MYSQL

echo -e "${GREEN}✅ Banco validado${NC}"

# 6. Testar acesso
echo -e "${YELLOW}6. Testando acesso HTTP...${NC}"
curl -s https://seu-dominio.com.br/ | grep -q "<!DOCTYPE" && \
    echo -e "${GREEN}✅ Acesso HTTP OK${NC}" || \
    echo -e "${RED}❌ Erro no acesso HTTP${NC}"

# 7. Configurar .htaccess
echo -e "${YELLOW}7. Configurando .htaccess...${NC}"
cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L]
</IfModule>

# Redirect HTTP to HTTPS
<IfModule mod_rewrite.c>
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
EOF

echo -e "${GREEN}✅ .htaccess configurado${NC}"

echo ""
echo -e "${GREEN}✅ Configuração HostGator concluída!${NC}"
echo ""
echo "PRÓXIMAS AÇÕES:"
echo "1. Configurar Cron Job no cPanel"
echo "2. Testar login em /admin/login"
echo "3. Verificar sincronização"
echo "4. Monitorar logs em var/log/"
```

---

## 📊 Script 4: Backup Automático

**Arquivo**: `scripts/auto_backup.sh`

```bash
#!/bin/bash
# Script de backup automático
# Adicionar ao crontab: 0 2 * * * bash /home/seu_usuario/public_html/scripts/auto_backup.sh

BACKUP_DIR="/home/seu_usuario/backups"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)

# Criar diretório se não existir
mkdir -p $BACKUP_DIR

# Fazer backup do banco
echo "Iniciando backup do banco ($DATE)..."
mysqldump -h localhost -u ${DB_USER} -p${DB_PASS} ${DB_NAME} \
    | gzip > $BACKUP_DIR/clima_ete_$DATE.sql.gz

# Fazer backup do código (excluir vendor e .git)
tar -czf $BACKUP_DIR/codigo_$DATE.tar.gz \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='var/log' \
    /home/seu_usuario/public_html

# Remover backups antigos (> 30 dias)
find $BACKUP_DIR -name "clima_ete_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "codigo_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Log
echo "Backup concluído em $DATE" >> $BACKUP_DIR/backup.log

# Opcional: enviar para nuvem (AWS S3, Google Drive, etc)
# aws s3 cp $BACKUP_DIR/clima_ete_$DATE.sql.gz s3://seu-bucket/backups/
```

---

## 🧪 Script 5: Teste Pós-Deploy

**Arquivo**: `scripts/test_deployment.sh`

```bash
#!/bin/bash
# Script de testes pós-deploy
# Uso: bash scripts/test_deployment.sh https://seu-dominio.com.br

DOMAIN=$1

if [ -z "$DOMAIN" ]; then
    echo "Uso: bash scripts/test_deployment.sh https://seu-dominio.com.br"
    exit 1
fi

echo "🧪 Iniciando testes pós-deploy..."
echo "Domínio: $DOMAIN"
echo ""

PASSED=0
FAILED=0

# Teste 1: Página pública
echo -n "1. Página pública... "
if curl -s $DOMAIN | grep -q "<!DOCTYPE"; then
    echo "✅ PASS"
    ((PASSED++))
else
    echo "❌ FAIL"
    ((FAILED++))
fi

# Teste 2: API JSON
echo -n "2. API (JSON)... "
if curl -s $DOMAIN/live?api=1 | grep -q "ultima_atualizacao"; then
    echo "✅ PASS"
    ((PASSED++))
else
    echo "❌ FAIL"
    ((FAILED++))
fi

# Teste 3: Painel ao vivo
echo -n "3. Painel ao vivo... "
if curl -s $DOMAIN/live | grep -q "Painel"; then
    echo "✅ PASS"
    ((PASSED++))
else
    echo "❌ FAIL"
    ((FAILED++))
fi

# Teste 4: Export CSV
echo -n "4. CSV export... "
RESPONSE=$(curl -s -I $DOMAIN/live?format=csv&period=24)
if echo "$RESPONSE" | grep -q "text/csv"; then
    echo "✅ PASS"
    ((PASSED++))
else
    echo "❌ FAIL"
    ((FAILED++))
fi

# Teste 5: Login page
echo -n "5. Página de login... "
if curl -s $DOMAIN/admin/login | grep -q "password"; then
    echo "✅ PASS"
    ((PASSED++))
else
    echo "❌ FAIL"
    ((FAILED++))
fi

# Teste 6: HTTPS
echo -n "6. HTTPS ativo... "
if curl -s -o /dev/null -w "%{http_code}" https://$DOMAIN | grep -q "200\|301"; then
    echo "✅ PASS"
    ((PASSED++))
else
    echo "❌ FAIL"
    ((FAILED++))
fi

echo ""
echo "===== RESULTADO ====="
echo "✅ Passed: $PASSED"
echo "❌ Failed: $FAILED"
echo "Total: $((PASSED + FAILED))"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "🎉 Todos os testes passaram!"
    exit 0
else
    echo "⚠️  Alguns testes falharam. Verifique os logs."
    exit 1
fi
```

---

## 📋 Script 6: Verificação de Saúde do Sistema

**Arquivo**: `scripts/health_check.sh`

```bash
#!/bin/bash
# Script de verificação de saúde do sistema
# Uso: bash scripts/health_check.sh

echo "🏥 Verificação de Saúde do Sistema"
echo "=================================="
echo ""

# 1. Banco de dados
echo "1. Banco de dados:"
MYSQL_STATUS=$(mysql -h localhost -u ${DB_USER} -p${DB_PASS} ${DB_NAME} \
    -e "SHOW STATUS LIKE 'Uptime'" 2>/dev/null | tail -1 | awk '{print $2}')

if [ -n "$MYSQL_STATUS" ]; then
    echo "   ✅ MySQL rodando (Uptime: $MYSQL_STATUS)"
else
    echo "   ❌ Erro ao conectar MySQL"
fi

# 2. Espaço em disco
echo ""
echo "2. Espaço em disco:"
DISK_USAGE=$(df -h / | tail -1 | awk '{print $5}')
echo "   Uso: $DISK_USAGE"

# 3. Processos PHP
echo ""
echo "3. Processos PHP:"
PHP_PROCS=$(ps aux | grep php | grep -v grep | wc -l)
echo "   Processos ativos: $PHP_PROCS"

# 4. Logs de erro
echo ""
echo "4. Logs recentes:"
if [ -f "var/log/clima_ete.log" ]; then
    ERRORS=$(tail -5 var/log/clima_ete.log)
    if [ -z "$ERRORS" ]; then
        echo "   ✅ Nenhum erro recente"
    else
        echo "   ⚠️  Últimos erros:"
        echo "$ERRORS" | sed 's/^/      /'
    fi
fi

# 5. Cron jobs
echo ""
echo "5. Cron jobs:"
NEXT_SYNC=$(at -l 2>/dev/null | head -1)
if [ -n "$NEXT_SYNC" ]; then
    echo "   ✅ Próxima sincronização: $NEXT_SYNC"
else
    echo "   ⚠️  Nenhum cron job agendado"
fi

# 6. Conectividade
echo ""
echo "6. Conectividade:"
if ping -c 1 thinger.io &> /dev/null; then
    echo "   ✅ Acesso a Thinger.io OK"
else
    echo "   ❌ Sem acesso a Thinger.io"
fi

echo ""
echo "=================================="
```

---

## 🆘 Troubleshooting Rápido

### Erro: "SQLSTATE[HY000]: General error: 2006 MySQL server has gone away"

```bash
# No HostGator, via SSH:

# 1. Reiniciar MySQL
/etc/init.d/mysql restart

# 2. Verificar conexão
mysql -h localhost -u seu_usuario_clima -p seu_usuario_clima_ete -e "SELECT 1"

# 3. Se ainda falhar, contactar suporte HostGator
```

### Erro: "Permission denied" ao escrever logs

```bash
# Corrigir permissões
chmod 777 var/log
chmod 777 var/pdf
chown -R nobody var/log
chown -R nobody var/pdf
```

### Erro: "Class not found"

```bash
# Regenerar autoload do Composer
composer dump-autoload --optimize
composer install --optimize-autoloader
```

---

## 📞 Resumo Rápido

| Fase | Arquivo | Comando |
|------|---------|---------|
| Preparação Local | prepare_local.sh | `bash scripts/prepare_local.sh` |
| Upload | upload_hostgator.ps1 | `.\scripts\upload_hostgator.ps1` |
| Setup HostGator | setup_hostgator.sh | `bash setup_hostgator.sh` |
| Testes | test_deployment.sh | `bash scripts/test_deployment.sh https://seu-dominio` |
| Saúde | health_check.sh | `bash scripts/health_check.sh` |

---

**Documentação criada em**: 16 de dezembro de 2025  
**Versão**: 1.0.0
