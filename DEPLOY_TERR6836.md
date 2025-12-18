# 🚀 DEPLOY FINAL - INSTRUÇÕES PARA TERR6836

## ✅ Tudo Pronto!

O `php.ini` já está configurado para seu usuário:

```ini
session.save_path = "/home3/terr6836/clima_tmp"
```

---

## 📋 PASSO A PASSO FINAL

### 1️⃣ Preparar Upload Local

```bash
cd c:\PROJETOS\clima_ete_novo

# Gerar vendor otimizado
composer install --no-dev --optimize-autoloader

# Criar ZIP para upload
Compress-Archive -Path . -DestinationPath "DEPLOY_20251217.zip" `
  -Exclude ".env", ".git", "docs", "backup", "vendor_bkp", "node_modules"
```

**Resultado**: `DEPLOY_20251217.zip` (~5-6 MB)

---

### 2️⃣ Upload via FTP

```
Host: ftp.clima.cria.click
Usuário: terr6836
Pasta: public_html/

Upload:
  ✅ DEPLOY_20251217.zip
  ✅ backup/clima_ete_backup_20251217.sql
```

---

### 3️⃣ Descompactar no Servidor (SSH)

```bash
ssh terr6836@clima.cria.click

cd public_html

# Descompactar
unzip DEPLOY_20251217.zip

# Remover ZIP
rm DEPLOY_20251217.zip

# Verificar estrutura
ls -la public/php.ini
ls -la src/
ls -la vendor/
```

---

### 4️⃣ Criar Diretório de Sessão

```bash
# Criar se não existir
mkdir -p /home3/terr6836/clima_tmp
chmod 700 /home3/terr6836/clima_tmp

# Verificar
ls -la /home3/terr6836/clima_tmp
# Deve retornar: drwx------
```

---

### 5️⃣ Configurar Banco de Dados

```bash
# Importar SQL
cd /home3/terr6836/public_html

mysql -h localhost -u terr6836_clima -p terr6836_clima_ete < clima_ete_backup_20251217.sql

# Verificar
mysql -h localhost -u terr6836_clima -p terr6836_clima_ete -e "SHOW TABLES;"
```

---

### 6️⃣ Criar .env

```bash
cat > /home3/terr6836/public_html/.env << 'EOF'
DB_HOST=localhost
DB_NAME=terr6836_clima_ete
DB_USER=terr6836_clima
DB_PASS=SUA_SENHA_AQUI
DB_CHARSET=utf8mb4

THINGER_USER=seu_thinger_user
THINGER_DEVICE=seu_device_id
THINGER_RESOURCE=data
THINGER_TOKEN=seu_token

CLIMA_CRON_KEY=sua_chave_segura
EOF

# Proteger arquivo
chmod 600 .env
```

---

### 7️⃣ Permissions

```bash
cd /home3/terr6836/public_html

chmod -R 755 .
chmod 777 var/log var/pdf var/sessions
chmod 600 .env
```

---

### 8️⃣ Testar Login

```
1. Abrir navegador
2. Acessar: https://clima.cria.click/admin/login
3. Fazer login (admin / admin123)
4. Deve redirecionar para /admin ✅

Se erro de sessão aparecer:
  → Verificar: /home3/terr6836/clima_tmp existe?
  → Verificar permissões: chmod 700
  → Verificar: public/php.ini tem session.save_path correto
```

---

## 🔍 Debug Rápido (SSH)

```bash
# Verificar se clima_tmp existe
ls -la /home3/terr6836/clima_tmp/

# Verificar php.ini
grep "session.save_path" public_html/public/php.ini

# Testar PHP
cd public_html
php -r "echo 'Session path: ' . session_save_path();"
# Deve retornar: /home3/terr6836/clima_tmp

# Verificar permissões do diretório
ls -la /home3/terr6836/ | grep clima_tmp
# Deve ser: drwx------
```

---

## ✅ Checklist Final

- [ ] ZIP criado localmente
- [ ] ZIP enviado via FTP para public_html/
- [ ] ZIP descompactado
- [ ] Diretório /home3/terr6836/clima_tmp criado
- [ ] Banco de dados importado com sucesso
- [ ] .env configurado com credenciais corretas
- [ ] Permissões corretas (777 para var/*)
- [ ] Login testado com sucesso ✅
- [ ] Dashboard carrega normalmente
- [ ] Sem erros de sessão

---

## 🚨 Se Algo Der Errado

### Erro de Sessão Persistir?

```bash
# 1. Remover sessões antigas
rm -rf /home3/terr6836/clima_tmp/*

# 2. Verificar php.ini exists
cat public_html/public/php.ini | grep session.save_path

# 3. Se ainda falhar, adicionar ao .htaccess
cat >> public_html/.htaccess << 'EOF'
php_value session.save_path "/home3/terr6836/clima_tmp"
EOF
```

### Banco Não Conectar?

```bash
# Verificar credenciais
mysql -h localhost -u terr6836_clima -p
# Digite: SUA_SENHA_AQUI

# Se falhar, recriar usuário via cPanel
```

---

**Status**: ✅ Pronto para Deploy  
**Usuário**: terr6836  
**Domínio**: clima.cria.click  
**Data**: 17 de dezembro de 2025
