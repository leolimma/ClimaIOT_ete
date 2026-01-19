# Guia de Deploy na HostGator

## **ATUALIZAÇÃO: Exportação PDF (v2.0 - HTML + CSS)**

> **Mudança de Estratégia**: Removemos a dependência TCPDF e implementamos uma solução nativa com HTML + CSS que aproveita o navegador para gerar PDFs via impressão.

### **O que mudou:**

| Aspecto | TCPDF (v1.0) | HTML+CSS (v2.0) |
|--------|-------------|-----------------|
| Dependências | TCPDF library | Nenhuma (nativa) |
| Tamanho upload | ~20MB (vendor) | ~0KB (apenas código) |
| Pré-requisitos | GD extension | Navegador moderno |
| Geração de PDF | Server-side | Client-side (navegador) |
| Manutenção | Complexa | Simples |

### **Arquivos a Atualizar no Servidor:**

1. **`src/Controller/AdminController.php`** - Delegação simples para RelatoriosController
2. **`src/Controller/RelatoriosController.php`** - NOVO arquivo com implementação HTML+CSS
3. **`public/index.php`** - Importação do RelatoriosController

### **Passos para Deploy:**

1. **Via FTP/File Manager:**
   - Faça upload de `src/Controller/AdminController.php` 
   - Faça upload de `src/Controller/RelatoriosController.php` (NOVO arquivo)
   - Atualize `public/index.php`
   - **Nenhum arquivo adicional necessário** (sem TCPDF, sem vendor)

2. **Como usar:**
   - Navegue para `/admin/reports?period=7` (ou 1, 30, all)
   - Clique em "Exportar PDF"
   - Digite o nome de quem emite o relatório
   - Clique em "Gerar PDF"
   - Uma página HTML será exibida com estilo de impressão
   - Pressione `Ctrl+P` (Windows/Linux) ou `Cmd+P` (Mac)
   - Selecione "Salvar como PDF" e clique em "Salvar"

### **Recursos:**

- Tabela com 8 colunas: ID, Data/Hora, Temperatura, Umidade, Pressão, UV, Gas, Chuva
- Logo da escola (`agradece.jpg`) no topo
- Informações do relatório: período, data de emissão, quem emitiu
- Rodapé com créditos do sistema
- CSS otimizado para impressão (@media print)
- Sem dependências externas

---

## **Passo 1: Preparar os arquivos localmente**

1. Remova arquivos de teste:
```bash
rm -f test_*.php check_*.php debug_*.php
```

2. Crie/atualize o arquivo `.htaccess` na raiz publica para redirecionar para `index.php`:

**Para domínio principal e subdomínio (Recomendado):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>
```

⚠️ **Importante**: Coloque o arquivo em `public/.htaccess`

---

## **Passo 2: Configurar no Painel HostGator**

### **2.1 Criar Banco de Dados MySQL**
1. Acesse **cPanel** → **MySQL Databases**
2. Crie novo banco (ex: `seu_usuario_seu_projeto_db`)
3. Crie novo usuário MySQL
4. Adicione privilégios ao usuário
5. **Anote**: nome do banco, usuário e senha

### **2.2 Configurar Subdomínio**
1. **cPanel** → **Subdomains**
2. Crie o subdomínio (ex: `clima` no seu domínio)
3. **Edite o subdomínio criado** (ícone de lápis)
4. **Document Root**: Altere para apontar para a pasta `public/`:
   - Se o subdomínio está em `/home3/seu_usuario/clima.cria.click/`, aponte para:
   ```
   /home3/seu_usuario/clima.cria.click/public
   ```
5. Clique em **Save**

**Estrutura final:**
```
/home3/seu_usuario/clima.cria.click/
├── public/                 ← Document Root aponta AQUI
│   ├── .htaccess
│   ├── index.php
│   └── assets/
│       └── img/
│           ├── logo_1.png
│           ├── agradece.png
│           └── favico.png
├── src/
├── vendor/
├── lib/
└── db_config.php
```

---

## **Passo 3: Upload dos Arquivos (via FTP)**

1. **Baixe FileZilla** ou use o gerenciador de arquivos do cPanel
2. Conecte via FTP (credenciais estão no HostGator)
3. Navegue até: `/public_html/seu_dominio/` ou `/public_html/`
4. **Upload**:
   - Envie **tudo EXCETO** `vendor/` (instalaremos via Composer no servidor)
   - Estrutura:
   ```
   public_html/
   ├── public/              (arquivos públicos)
   ├── src/                 (código-fonte)
   ├── lib/                 (helpers)
   ├── db_config.php        (ajuste credenciais!)
   ├── composer.json
   ├── composer.lock
   └── bin/
   ```

---

## **Passo 4: Instalar Dependências**

### **Opção A: Via Terminal SSH (Recomendado)**
1. Acesse **cPanel** → **Terminal** (SSH)
2. Navegue até seu diretório:
   ```bash
   cd ~/public_html/seu_dominio
   ```
3. Instale Composer (se não tiver):
   ```bash
   curl -sS https://getcomposer.org/installer | php
   ```
4. Instale dependências:
   ```bash
   php composer.phar install --no-dev --optimize-autoloader
   ```

### **Opção B: Sem SSH (Upload manual)**
- Faça localmente: `composer install --no-dev --optimize-autoloader`
- Envie a pasta `vendor/` via FTP

---

## **Passo 5: Configurar Credenciais**

Edite `db_config.php` com as credenciais do banco HostGator:
```php
return [
    'host' => 'localhost',  // HostGator usa localhost
    'name' => 'seu_usuario_clima_ete',  // banco criado no Passo 2
    'user' => 'seu_usuario_mysql',       // usuário criado
    'pass' => 'sua_senha_mysql',         // senha
    'charset' => 'utf8mb4',
];
```

---

## **Passo 6: Executar Setup Inicial**

1. Acesse: `https://seu_dominio.com/setup`
2. Execute as migrações do banco
3. Crie o primeiro usuário admin
4. **Remova** `setup.php` após conclusão

---

## **Passo 7: Configurar CRON Automático**

### **Via cPanel (Recomendado)**
1. **cPanel** → **Cron Jobs**
2. Clique em **Add New Cron Job**
3. Configure:
   - **Common Settings**: `Every 15 minutes`
   - **Command**:
   ```bash
   cd /home/seu_usuario/public_html/seu_dominio && /usr/bin/php sync_cron.php -k=protegido
   ```



4. Clique em **Add Cron Job**

---

## **Passo 8: Testes Finais**

1. **Página inicial**: `https://seu_dominio.com/`
   - Deve mostrar: "Tecnoambiente: Estação Ambiental" com status ONLINE
   
2. **Página live**: `https://seu_dominio.com/live`
   - Mesmo título e dados em tempo real

3. **Admin**: `https://seu_dominio.com/admin/login`
   - Login com credenciais criadas

4. **API**: `https://seu_dominio.com/live?api=1`
   - Retorna JSON com dados

5. **Sincronização**: Aguarde a próxima execução do cron ou teste manualmente:
   ```
   https://seu_dominio.com/cron/sync?key=protegido
   ```

---

## **Checklist Final**

- ✅ Arquivos enviados via FTP
- ✅ `vendor/` instalado (Composer)
- ✅ `db_config.php` com credenciais corretas
- ✅ Banco de dados criado e migrado
- ✅ Setup executado e arquivo removido
- ✅ Cron configurado no cPanel
- ✅ Testes de rotas OK
- ✅ HTTPS habilitado (cPanel oferece Let's Encrypt grátis)

---

## **Troubleshooting**

### Erro: "404 Not Found" em `/setup`, `/admin/login`, `/live`
**Isso significa que o `.htaccess` NÃO está funcionando.**

1. **Teste removendo `.htaccess` temporariamente:**
   - No cPanel, delete o arquivo `.htaccess`
   - Acesse: `https://clima.cria.click/index.php/setup`
   - Se funcionar, o problema é o `.htaccess`

2. **Atualize o `.htaccess` para (versão melhorada com suporte à autenticação):**
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
       RewriteBase /
       RewriteRule ^index\.php$ - [L]
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteRule . /index.php [L]
   </IfModule>
   ```

3. **Verifique permissões:**
   - `.htaccess`: 644
   - `public/`: 755

4. **Se ainda não funcionar:**
   - Contate o suporte HostGator
   - Peça para ativar/verificar `mod_rewrite`
   - Forneça o caminho: `/home/seu_usuario/seu_dominio/public`

### Erro: "404 Not Found"
- Verifique se `.htaccess` está correto em `public/`
- Certifique-se de `mod_rewrite` está habilitado (contate suporte)

### Erro: "Connection refused" no banco
- Verifique `.env` com credenciais corretas
- MySQL está ativo no cPanel?

### Cron não executa
- Verifique se o comando foi digitado corretamente
- Confira logs em **cPanel** → **Error Log** ou **Raw Access Logs**

### Setup.php não encontrado
- Certifique-se que `setup.php` está na raiz do projeto
- Se necessário, recrie conforme documentação

---

**Dúvidas? Contate o suporte HostGator ou revise a documentação do projeto.**



**//DATA E HORA**
Alteração 1 - Função formatDateTime() (por volta da linha 163):
<?php
private function formatDateTime(string $datetime): string
{
    try {
        $dt = new DateTimeImmutable($datetime);
        return $dt->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return '---';
    }
}
Alteração 2 - Função formatHour() (por volta da linha 172):
<?php
private function formatHour(string $datetime): string
{
    try {
        $dt = new DateTimeImmutable($datetime);
        return $dt->format('H:i');
    } catch (Throwable $e) {
        return '';
    }
}
Alteração 3 - Função diffSeconds() (por volta da linha 181):
<?php
private function diffSeconds(string $datetime): ?int
{
    try {
        // O banco já retorna na timezone local (Fortaleza)
        $dt = new DateTimeImmutable($datetime);
        $now = new DateTimeImmutable('now');
        return $now->getTimestamp() - $dt->getTimestamp();
    } catch (Throwable $e) {
        return null;
    }
}
Resumo: Removi todas as conversões de timezone (new \DateTimeZone('UTC') e setTimezone()), pois o banco já salva em Fortaleza.