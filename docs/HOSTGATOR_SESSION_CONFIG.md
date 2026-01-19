# 🔧 Configuração de Sessão para HostGator - GUIA RÁPIDO

## 🎯 O Problema

```
Warning: session_start(): open(/var/cpanel/php/sessions/..., O_RDWR) failed
Warning: session_start(): Failed to read session data: files
```

## ✅ A Solução

Use uma das três opções abaixo (em ordem de preferência):

---

## 📋 OPÇÃO 1: Usar Arquivo de Configuração (Recomendado)

### 1.1 Editar arquivo `config/session.php`

```php
<?php
// config/session.php

// CONFIGURE PARA SEU USUARIO HOSTING
define('CUSTOM_SESSION_PATH', '/home/seu_usuario_hosting/tmp');
// Exemplo:
// define('CUSTOM_SESSION_PATH', '/home/seu_usuario_hosting/tmp');
```

### 1.2 Criar diretório no servidor (via SSH)

```bash
ssh seu_usuario@seu-dominio.com.br
mkdir -p /home3/seu_usuario/tmp
chmod 700 /home3/seu_usuario/tmp
```

### 1.3 Fazer upload do arquivo atualizado

- Upload `config/session.php` para HostGator

### ✅ Pronto! Sessões funcionarão em `/home3/seu_usuario/tmp`

---

## 📋 OPÇÃO 2: Usar Variável de Ambiente .env

### 2.1 Editar `.env` no HostGator

```bash
ssh seu_usuario@seu-dominio.com.br
cd public_html
nano .env
```

### 2.2 Adicionar linha

```env
SESSION_PATH=/home3/seu_usuario/tmp
```

### 2.3 Criar diretório

```bash
mkdir -p /home3/seu_usuario/tmp
chmod 700 /home3/seu_usuario/tmp
```

### ✅ Pronto! SessionMiddleware lerá de SESSION_PATH

---

## 📋 OPÇÃO 3: Usar Diretório Local (Automático)

Se nenhuma configuração for definida, o SessionMiddleware usa:

```
/public_html/var/sessions/
```

### Garantir permissões:

```bash
ssh seu_usuario@seu-dominio.com.br
chmod 777 public_html/var/sessions/
```

### ✅ Pronto! Sessões em /var/sessions localmente

---

## 🔍 Como Descubrir SEU USUARIO no HostGator

```bash
# Conecte via SSH
ssh seu_usuario@seu-dominio.com.br

# Execute para descobrir seu usuário:
whoami
# Retorna: seu_usuario_hosting (seu usuário)

# Agora saiba o home path:
echo $HOME
# Retorna: /home/seu_usuario_hosting (ou similar)
```

---

## ✅ Testar se Funciona

```bash
# 1. Acessar login
curl https://seu-dominio.com.br/admin/login

# 2. Fazer login
# Deve redirecionar para /admin (dashboard)

# 3. Verificar se sessão foi criada
ls -la /home3/seu_usuario/tmp/
# Ou
ls -la public_html/var/sessions/

# Deve haver arquivo sess_* criado
```

---

## 🚨 Se Ainda Não Funcionar

### Debug: Ativar Logs

No SessionMiddleware, adicione:

```php
private function configureSessionPath(): void
{
    error_log("=== SESSION CONFIG DEBUG ===");
    error_log("Custom path: " . $this->getCustomSessionPath());
    error_log("Var/sessions writable: " . (is_writable(__DIR__ . '/../../var/sessions') ? 'yes' : 'no'));
    error_log("========================");
    
    // ... resto do código
}
```

Depois verifique `var/log/php_errors.log`

### Contactar Suporte HostGator

Se persistir, envie para suporte:
- Erro completo do PHP
- Versão do PHP (`php -v`)
- Output de `whoami` e `echo $HOME`

---

## 📊 Resumo de Prioridades

| Opção | Prioridade | Notas |
|-------|-----------|-------|
| 1. config/session.php | ⭐⭐⭐ | Recomendado - específico para projeto |
| 2. .env SESSION_PATH | ⭐⭐ | Bom - flexível por ambiente |
| 3. var/sessions/ | ⭐ | Automático - se 1 e 2 não funcionarem |

---

**SessionMiddleware agora tenta na ordem:**
1. Caminho customizado (config/session.php)
2. Variável de ambiente (SESSION_PATH)
3. Diretório local (var/sessions/)
4. Padrão do servidor (último recurso)

**Criado em**: 17 de dezembro de 2025  
**Versão**: 2.0 - Com suporte completo a HostGator
