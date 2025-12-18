# 🔧 Erro de Sessão no HostGator - Solução

## ❌ Erro Recebido

```
Warning: session_start(): open(/var/cpanel/php/sessions/ea-php83/sess_..., O_RDWR) failed: No such file or directory (2)
Warning: session_start(): Failed to read session data: files (path: /var/cpanel/php/sessions/ea-php83)
```

## 🎯 Causa

HostGator utiliza PHP gerenciado via cPanel com suporte de múltiplas versões (ea-php83, ea-php82, etc). O diretório padrão de sessão:
- Pode não existir
- Pode não ter permissões de escrita
- Pode estar desabilitado pela configuração do cPanel

## ✅ Solução Implementada

### 1. Configuração Automática em [src/Middleware/SessionMiddleware.php](src/Middleware/SessionMiddleware.php)

O código agora:

```php
private function configureSessionPath(): void
{
    // 1. Tentar usar diretório local var/sessions
    $sessionsDir = __DIR__ . '/../../var/sessions';
    
    // 2. Criar diretório se não existir
    if (!is_dir($sessionsDir)) {
        @mkdir($sessionsDir, 0755, true);
    }

    // 3. Usar se conseguir escrever
    if (is_dir($sessionsDir) && is_writable($sessionsDir)) {
        ini_set('session.save_path', $sessionsDir);
        ini_set('session.save_handler', 'files');
    } else {
        // Fallback: usar padrão do servidor
        ini_set('session.save_handler', 'files');
    }
}
```

### 2. Estrutura de Diretórios

```
var/
├── log/
│   └── .gitkeep
├── pdf/
│   └── .gitkeep
└── sessions/           ← NOVO (criado pelo SessionMiddleware)
    └── .gitkeep
```

## 🚀 Como Funciona no HostGator

### Cenário 1: Diretório var/sessions Disponível ✅
```
1. SessionMiddleware detecta var/sessions
2. Verifica se tem permissão de escrita
3. Configura: ini_set('session.save_path', 'var/sessions')
4. Sessões salvas localmente no projeto
5. Sem conflitos com cPanel
```

### Cenário 2: Diretório Sem Permissão ⚠️
```
1. SessionMiddleware tenta criar/escrever
2. Falha silenciosa: @mkdir()
3. Usa padrão do servidor
4. Se servidor também falhar, erro é mostrado
5. Administrador pode corrigir permissões manualmente
```

## 📋 Passos para Deploy (HostGator)

### 1️⃣ Fazer Upload
```bash
# Incluir o diretório var/sessions/ no ZIP
# O arquivo .gitkeep garante que o diretório seja criado
```

### 2️⃣ Após Descompactar
```bash
# Ssh para o servidor
ssh usuario@seu-dominio.com.br

# Dar permissão de escrita
chmod -R 777 var/sessions/
```

### 3️⃣ Testar
```bash
# Acessar página de login
curl https://seu-dominio.com.br/admin/login

# Fazer login
# Deve funcionar agora
```

## 🛠️ Se o Erro Persistir

### Opção A: Verificar Permissões
```bash
ssh usuario@seu-dominio.com.br
cd public_html
ls -la var/
# Deve mostrar: drwxrwxrwx (777) para var/sessions
```

### Opção B: Criar Diretório Manualmente
```bash
ssh usuario@seu-dominio.com.br
cd public_html
mkdir -p var/sessions
chmod 777 var/sessions
```

### Opção C: Usar .htaccess (Alternativo)
Criar arquivo `.htaccess` na raiz:
```apache
# Configurar session.save_path via .htaccess
php_value session.save_path "/home/usuario/public_html/var/sessions"
```

### Opção D: Usar php.ini Local
Criar arquivo `public/php.ini`:
```ini
session.save_path = "/home/usuario/public_html/var/sessions"
session.save_handler = "files"
session.auto_start = 0
session.use_strict_mode = 1
session.use_cookies = 1
session.use_only_cookies = 1
session.name = "SESSIONID"
session.gc_maxlifetime = 86400
session.cookie_lifetime = 86400
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = "Lax"
```

## ✅ Checklist Pós-Correção

- [x] [src/Middleware/SessionMiddleware.php](src/Middleware/SessionMiddleware.php) atualizado com `configureSessionPath()`
- [x] Diretório `var/sessions/` criado localmente
- [x] Arquivo `.gitkeep` adicionado para versionamento Git
- [x] Solução compatível com HostGator
- [x] Fallback automático se diretório não estiver disponível
- [ ] Fazer upload novo do ZIP (incluindo var/sessions/)
- [ ] Testar login no HostGator
- [ ] Se falhar, executar `chmod 777 var/sessions/` via SSH

## 📚 Referências

- [PHP: session.save_path](https://www.php.net/manual/en/session.configuration.php#ini.session.save-path)
- [PHP: ini_set()](https://www.php.net/manual/en/function.ini-set.php)
- [HostGator: PHP Configuration](https://suporte.hostgator.com.br/)

## 🔍 Debug

Para verificar qual diretório está sendo usado:

```php
<?php
// Adicionar em login.php temporariamente
error_log("Session Save Path: " . ini_get('session.save_path'));
error_log("Session Handler: " . ini_get('session.save_handler'));
error_log("Sessions Dir Writable: " . (is_writable('/var/sessions') ? 'Yes' : 'No'));
?>
```

---

**Arquivo Corrigido**: [src/Middleware/SessionMiddleware.php](src/Middleware/SessionMiddleware.php)  
**Data da Correção**: 17 de dezembro de 2025  
**Versão**: 2.0 (com suporte HostGator)
