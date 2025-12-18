# 📋 Guia de Manutenção - Relatórios HTML+CSS

## Introdução

Este documento descreve como manter e evoluir o sistema de relatórios que usa HTML + CSS para geração de PDFs.

---

## 🛠️ Tarefas Comuns

### 1. Adicionar um novo campo ao relatório

**Exemplo: Adicionar campo "Visibilidade" (visibility)**

**Passo 1:** Editar SQL no `RelatoriosController.index()`
```php
// Linha ~31
$stmt = $this->pdo->prepare('SELECT id, data_registro, temp, hum, pres, uv, gas, chuva_status, visibility FROM clima_historico ORDER BY data_registro DESC LIMIT :lim');
```

**Passo 2:** Editar tabela HTML em `buildReportsHtml()`
```php
// Linha ~100 (thead)
<th class="p-3 text-sm font-bold text-gray-700">Visibilidade</th>

// Linha ~125 (tbody)
sprintf(
    '...<td>%.1f</td>...',
    $row['visibility']
)
```

**Passo 3:** Editar tabela PDF em `buildPdfHtml()`
```php
// Linha ~180 (headers)
$headers = ['ID', 'Data/Hora', 'Temp(°C)', 'Umid(%)', 'Pressão(hPa)', 'UV', 'Gas(KΩ)', 'Chuva', 'Visibilidade'];

// Linha ~210 (dados)
sprintf(
    '<td>%.1f</td>',
    $row['visibility']
)
```

**Passo 4:** Editar CSV em `exportCsv()`
```php
// Linha ~65 (cabeçalho)
$csv = "ID;Data/Hora;Temperatura(°C);Umidade(%);Pressão(hPa);UV;Gas(KΩ);Chuva;Visibilidade\n";

// Linha ~75 (dados)
sprintf(
    "%d;%s;%.1f;%d;%.0f;%.1f;%.1f;%.1f;%.1f\n",
    $row['id'],
    // ...
    $row['visibility']
)
```

### 2. Alterar cores/estilos da tabela

**Exemplo: Mudar header de cinza para azul**

Em `buildPdfHtml()`, procure por:
```css
thead {
    background: #2c3e50;  /* ← Mude para #1e40af (azul) */
    color: white;
}
```

Ou em `buildReportsHtml()`:
```html
<thead class="bg-gray-100 border-b-2">  <!-- ← Mude para bg-blue-100 -->
```

### 3. Adicionar um novo filtro (ex: por usuário)

**Passo 1:** Adicionar parâmetro de query
```php
// Em index()
$user = (string)($params['user'] ?? '');

// Adicionar ao buildReportsHtml()
$userEscaped = $this->escape($user);
// Usar em links: ...&user={$userEscaped}
```

**Passo 2:** Aplicar filtro no SQL
```php
if ($user !== '') {
    $stmt = $this->pdo->prepare('SELECT * FROM clima_historico WHERE user = :user ORDER BY data_registro DESC LIMIT :lim');
    $stmt->bindValue(':user', $user);
} else {
    $stmt = $this->pdo->prepare('SELECT * FROM clima_historico ORDER BY data_registro DESC LIMIT :lim');
}
```

### 4. Customizar logo/imagens

**Atualmente:**
```php
// Em buildReportsHtml() - linha 125
<img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-3">

// Em buildPdfHtml() - linha 150
<img src="/assets/img/agradece.jpg" alt="Logo" class="logo">
```

**Para mudar imagem:**
1. Substitua arquivo em `public/assets/img/`
2. Atualize `src="/assets/img/seu_arquivo.png"`
3. Teste em ambos os templates

---

## 🐛 Troubleshooting

### Problema: Caracteres especiais aparecem errado no PDF

**Causa**: Encoding UTF-8 não aplicado  
**Solução**: Verificar que ambos os arquivos têm:
```php
<meta charset="UTF-8">
declare(strict_types=1);
```

### Problema: Tabela quebra entre páginas na impressão

**Causa**: CSS print não está sendo aplicado  
**Solução**: Verificar que método `buildPdfHtml()` contém:
```css
@media print {
    table { page-break-inside: avoid; }
}
```

### Problema: Imagem não aparece no PDF

**Causa**: Caminho relativo ou arquivo não existe  
**Solução**: 
1. Verificar arquivo em `public/assets/img/`
2. Usar caminho absoluto `/assets/img/arquivo.jpg`
3. Testar em navegador: http://localhost:8080/assets/img/arquivo.jpg

### Problema: Modal PDF não abre

**Causa**: JavaScript não está sendo executado  
**Solução**: Verificar:
```html
<script>lucide.createIcons();</script>
<!-- e -->
<button onclick="document.getElementById('pdfModal').classList.toggle('hidden')">
```

---

## 📈 Melhorias Futuras

### 1. Adicionar filtros avançados
- [ ] Filtro por intervalo de datas
- [ ] Filtro por faixa de temperatura
- [ ] Filtro por status de chuva

### 2. Adicionar gráficos
- [ ] Integrar Chart.js ou ApexCharts
- [ ] Gráfico de temperatura vs tempo
- [ ] Gráfico de umidade vs tempo

### 3. Exportar para outros formatos
- [ ] JSON API
- [ ] Excel (usando PhpSpreadsheet)
- [ ] Google Sheets

### 4. Melhorias UX
- [ ] Paginação (20 itens por página)
- [ ] Ordenação por coluna
- [ ] Busca por ID ou data

### 5. Performance
- [ ] Cache de dados
- [ ] Índices em banco de dados
- [ ] Compressão de respostas (gzip)

---

## 🔍 Checklist de Qualidade

Ao fazer mudanças, verificar:

- [ ] **Sintaxe PHP**: `php -l src/Controller/RelatoriosController.php`
- [ ] **Segurança**: Todos os valores escapados com `escape()`
- [ ] **SQL Injection**: Prepared statements com `:placeholder`
- [ ] **Encoding**: Todos os arquivos UTF-8, `<meta charset="UTF-8">`
- [ ] **Responsividade**: Testar em desktop e mobile
- [ ] **Impressão**: `Ctrl+P` em navegador com "Salvar como PDF"
- [ ] **CSV**: Abrir arquivo baixado no Excel/Calc
- [ ] **Performance**: Relatório com 1000+ linhas carrega < 2s

---

## 📚 Referências Rápidas

### Classe RelatoriosController
```php
namespace App\Controller;

class RelatoriosController {
    public function __construct(AuthService $authService, PDO $pdo)
    public function index(Request $request): Response
    private function exportCsv(array $records): Response
    private function exportPdf(array $records, string $period, string $emitter): Response
    private function buildPdfHtml(array $records, string $period, string $emitter): string
    private function buildReportsHtml(string $username, array $records, string $period): string
    private function escape(string $value): string
}
```

### Parâmetros Query

| Parâmetro | Valores | Padrão | Uso |
|-----------|---------|--------|-----|
| `period` | 1, 7, 30, all | 7 | Período do relatório |
| `format` | html, csv, pdf | html | Formato de saída |
| `emitter` | string | Sistema | Nome de quem emitiu |

### Endpoints

```
GET  /admin/reports                           # Relatório HTML (padrão)
GET  /admin/reports?period=7                  # Últimos 7 dias
GET  /admin/reports?period=30                 # Últimos 30 dias
GET  /admin/reports?period=all                # Todos os dados
GET  /admin/reports?format=csv                # Exportar CSV
GET  /admin/reports?format=pdf&emitter=João  # Exportar para impressão
```

---

## 🎓 Padrões de Código

### Escape de valores
```php
// ✅ CORRETO
$escaped = $this->escape($user_input);
$html .= "<td>{$escaped}</td>";

// ❌ ERRADO
$html .= "<td>{$user_input}</td>";
```

### Queries SQL
```php
// ✅ CORRETO
$stmt = $this->pdo->prepare('SELECT * FROM table WHERE id = :id');
$stmt->bindValue(':id', $id);

// ❌ ERRADO
$sql = "SELECT * FROM table WHERE id = $id";
$stmt = $this->pdo->query($sql);
```

### Respostas HTTP
```php
// ✅ CORRETO
$response = new Response();
$response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
$response->getBody()->write($html);
return $response;

// ❌ ERRADO
header('Content-Type: text/html');
echo $html;
```

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Consulte [RELATORIOS_ARCHITECTURE.md](RELATORIOS_ARCHITECTURE.md)
2. Verifique [DEPLOY_HOSTGATOR.md](DEPLOY_HOSTGATOR.md)
3. Abra uma issue no repositório

---

**Última Atualização**: 14 de dezembro de 2025  
**Versão**: 2.0 (HTML + CSS)
