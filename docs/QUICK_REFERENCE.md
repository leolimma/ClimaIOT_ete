# 🚀 Quick Start - Relatórios v2.0

## Arquivo Único de Referência para Desenvolvimento

### O que mudou?
- ❌ Removido: TCPDF e todas as dependências
- ✅ Adicionado: RelatoriosController com HTML+CSS
- ✅ Modificado: AdminController (simplificado)

### 3 Arquivos para Deploy
```
src/Controller/AdminController.php         (130 linhas)
src/Controller/RelatoriosController.php    (280 linhas) NEW
public/index.php                           (1 linha adicionada)
```

---

## Estrutura RelatoriosController

```php
class RelatoriosController {
    public function index(Request $request): Response
    ├─ exportCsv(array $records): Response
    ├─ exportPdf(array $records, string $period, string $emitter): Response
    │  └─ buildPdfHtml(...): string (HTML com CSS print)
    ├─ buildReportsHtml(string $username, ...): string (Interface web)
    └─ escape(string $value): string (Sanitização)
}
```

---

## Endpoints da API

| Method | Endpoint | Resultado |
|--------|----------|-----------|
| GET | `/admin/reports` | HTML (padrão) |
| GET | `/admin/reports?period=7` | Últimos 7 dias |
| GET | `/admin/reports?period=30` | Últimos 30 dias |
| GET | `/admin/reports?period=all` | Todos os dados |
| GET | `/admin/reports?format=csv` | Download CSV |
| GET | `/admin/reports?format=pdf&emitter=Nome` | HTML para imprimir |

---

## Fluxo de Impressão

```
Usuário acessa /admin/reports?format=pdf&emitter=João
                      ↓
        RelatoriosController.index()
                      ↓
        exportPdf() chama buildPdfHtml()
                      ↓
        Retorna HTML com CSS @media print
                      ↓
        Navegador exibe página
                      ↓
        Usuário: Ctrl+P
                      ↓
        Seleciona "Salvar como PDF"
                      ↓
        PDF gerado no navegador (SEM servidor)
```

---

## CSS para Impressão

```css
@media print {
    body { background: white; }
    .container { padding: 0; margin: 0; }
    .print-button { display: none; }
    table { page-break-inside: avoid; }
    .header { page-break-after: avoid; }
}
```

---

## Adicionar Campo ao Relatório (3 passos)

### 1. SQL - buildPdfHtml()
```php
foreach ($records as $row) {
    $date = date('d/m/Y H:i', strtotime($row['data_registro']));
    $rows .= sprintf(
        '<tr><td>%d</td><td>%s</td><td>%.1f</td>...<td>%.1f</td></tr>',
        $row['id'],
        $date,
        $row['temp'],
        // ... adicionar aqui
        $row['seu_campo']  // ← AQUI
    );
}
```

### 2. HTML - buildReportsHtml()
```html
<tr>
    <th>ID</th>
    <th>Data</th>
    <!-- ... -->
    <th>Seu Campo</th>  <!-- ← AQUI -->
</tr>
```

### 3. CSV - exportCsv()
```php
$csv = "ID;Data/Hora;...;SeuCampo\n";
foreach ($records as $row) {
    $csv .= sprintf(
        "%d;%s;...;%.1f\n",
        $row['id'],
        $row['data_registro'],
        // ...
        $row['seu_campo']  // ← AQUI
    );
}
```

---

## Segurança Essencial

### ✅ Sempre fazer
```php
// 1. Sanitizar output
$safe = $this->escape($user_input);
echo "<td>{$safe}</td>";

// 2. Prepared statements
$stmt = $this->pdo->prepare('SELECT * WHERE id = :id');
$stmt->bindValue(':id', $id);

// 3. Autenticação
if (!$this->authService->isAuthenticated()) {
    return $error_response;
}
```

### ❌ NUNCA fazer
```php
// NÃO
echo "<td>{$user_input}</td>";
$sql = "SELECT * WHERE id = $id";
header('Location: ' . $_GET['redirect']);
```

---

## Troubleshoot Rápido

| Problema | Solução |
|----------|---------|
| 404 em /admin/reports | Verificar route em public/index.php |
| RelatoriosController not found | Adicionar `use App\Controller\RelatoriosController;` |
| Tabela vazia | `SELECT * FROM clima_historico LIMIT 10;` no DB |
| Modal não abre | Verificar console (F12) por erros JavaScript |
| PDF não salva | Usar Ctrl+P em vez de botão (navegador prefs) |
| Caracteres errados | Verificar `<meta charset="UTF-8">` |

---

## Files Check ✅

```bash
# 1. Verificar arquivo existe
ls -la src/Controller/RelatoriosController.php

# 2. Verificar syntax
php -l src/Controller/RelatoriosController.php

# 3. Verificar imports
grep "RelatoriosController" public/index.php

# 4. Verificar delegação
grep -A3 "public function reports" src/Controller/AdminController.php
```

---

## Deploy Checklist

- [ ] BackUp dos 3 arquivos antes de modificar
- [ ] Enviar 3 arquivos PHP via FTP
- [ ] Testar /admin/reports em produção
- [ ] Verificar dados no relatório
- [ ] Testar CSV download
- [ ] Testar PDF (Ctrl+P)
- [ ] Validar em navegadores (Chrome, Firefox, Edge)

---

## Documentação Completa

| Arquivo | Propósito |
|---------|-----------|
| RELATORIOS_ARCHITECTURE.md | Guia técnico completo |
| MANUTENCAO_RELATORIOS.md | Tarefas comuns e troubleshoot |
| TESTE_RELATORIOS_V2.md | Guia de testes detalhado |
| DEPLOY_HOSTGATOR.md | Instruções de produção |
| CHANGELOG_RELATORIOS_V2.md | Histórico de mudanças |
| RELATORIOS_V2_RESUMO.md | Resumo executivo |

---

## Contato Rápido

Arquivo problemático? Acesse:
- `src/Controller/RelatoriosController.php` → Lógica
- `public/index.php` → Rotas
- `src/Controller/AdminController.php` → Delegação

---

**Status**: ✅ PRONTO  
**Versão**: 2.0 (HTML + CSS)  
**Última Update**: 14 de dezembro de 2025
