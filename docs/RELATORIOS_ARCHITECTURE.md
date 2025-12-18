# Arquitetura de Relatórios - Sistema de Monitoramento Climático

## 📋 Visão Geral

A partir da versão 2.0, a geração de relatórios foi refatorada para usar uma **estratégia HTML + CSS** (impressão no navegador) em vez de dependências externas como TCPDF.

### Benefícios da Nova Abordagem

✅ **Zero Dependências** - Não requer bibliotecas externas (TCPDF, DomPDF)  
✅ **Simples de Manter** - Código puro PHP + HTML + CSS  
✅ **Fácil de Customizar** - Basta editar o template HTML  
✅ **Melhor UX** - Usuário tem controle total sobre impressão/PDF  
✅ **Sem Problemas de Encoding** - Unicode nativo  
✅ **Deploy Simplificado** - Sem upload de vendor, apenas arquivos de código  

## 🏗️ Arquitetura

```
src/Controller/
├── AdminController.php (delegação)
└── RelatoriosController.php (implementação)

Fluxo:
1. Usuário acessa /admin/reports?period=7
2. AdminController.reports() → RelatoriosController.index()
3. RelatoriosController verifica formato (?format=csv ou ?format=pdf)
4. Se CSV: exportCsv() retorna arquivo Excel
5. Se PDF: exportPdf() retorna HTML com CSS de impressão
```

## 📁 Estrutura de Arquivos

### `src/Controller/RelatoriosController.php` (NOVO)

**Responsabilidades:**
- Buscar dados de `clima_historico` do banco de dados
- Formatar dados em diferentes formatos (HTML, CSV, PDF)
- Gerar HTML com CSS otimizado para impressão

**Métodos Principais:**
- `index(Request $request): Response` - Entrada principal
- `exportCsv(array $records): Response` - Exportação CSV
- `exportPdf(array $records, string $period, string $emitter): Response` - Retorna HTML para impressão
- `buildPdfHtml()` - Template HTML com CSS @media print
- `buildReportsHtml()` - Interface web para visualização e filtros
- `escape(string $value): string` - Sanitização de saída

### `src/Controller/AdminController.php` (MODIFICADO)

**Mudança:**
```php
public function reports(Request $request): Response
{
    if (!$this->authService->isAuthenticated()) {
        $response = new Response(302);
        return $response->withHeader('Location', ADMIN_LOGIN_ROUTE);
    }

    $controller = new RelatoriosController($this->authService, $this->pdo);
    return $controller->index($request);
}
```

Removidas:
- `exportCsv()` - Agora em RelatoriosController
- `exportPdf()` - Agora em RelatoriosController
- `buildReportsHtml()` - Agora em RelatoriosController

## 🔄 Fluxos de Uso

### 1. Visualizar Relatório (HTML)
```
GET /admin/reports?period=7
↓
RelatoriosController.index() com format=html
↓
buildReportsHtml() renderiza página com Tailwind + Lucide
↓
Exibe tabela com botões CSV e PDF
```

### 2. Exportar CSV
```
GET /admin/reports?period=7&format=csv
↓
exportCsv() formata dados com separador `;`
↓
Response com Content-Type: text/csv
↓
Download automático como relatorio_clima_[data].csv
```

### 3. Exportar PDF (via impressão)
```
GET /admin/reports?period=7&format=pdf&emitter=Nome%20do%20Usuario
↓
exportPdf() → buildPdfHtml()
↓
Response com HTML + CSS @media print
↓
Navegador exibe página
↓
Usuário: Ctrl+P → "Salvar como PDF"
↓
PDF gerado localmente no navegador
```

## 🎨 Template HTML + CSS

**Componentes:**
- Cabeçalho com logo, título e informações da escola
- Seção de metadados: período, data, quem emitiu, total de registros
- Tabela de dados com 8 colunas
- Rodapé com créditos do sistema
- CSS @media print para otimizar apresentação

**CSS Print Destacado:**
```css
@media print {
    body { background: white; }
    .container { padding: 0; margin: 0; }
    .print-button { display: none; }
    .header { page-break-after: avoid; }
    table { page-break-inside: avoid; }
}
```

## 📊 Campos do Relatório

| Campo | Tipo | Origem |
|-------|------|--------|
| ID | int | clima_historico.id |
| Data/Hora | datetime | clima_historico.data_registro |
| Temperatura | float | clima_historico.temp |
| Umidade | int | clima_historico.hum |
| Pressão | float | clima_historico.pres |
| UV | float | clima_historico.uv |
| Gas | float | clima_historico.gas |
| Chuva | string | clima_historico.chuva_status |

## 🔐 Segurança

- **Autenticação**: Requer session autenticada (verificação em `AdminController.reports()`)
- **Sanitização**: Uso de `escape()` para todos os valores dinâmicos
- **SQL Injection**: Prepared statements com PDO
- **XSS**: `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`

## 🚀 Como Estender

### Adicionar Nova Coluna ao Relatório

1. **Adicionar ao SQL:**
```php
// em RelatoriosController.index()
$stmt = $this->pdo->prepare('SELECT id, data_registro, temp, hum, pres, uv, gas, chuva_status, SEU_CAMPO FROM clima_historico ...');
```

2. **Adicionar à tabela HTML:**
```html
<!-- em buildReportsHtml() -->
<th class="p-3 text-sm font-bold text-gray-700">Seu Campo</th>
...
<td class="p-3 text-sm">%s</td> <!-- novo valor -->
```

3. **Adicionar à tabela PDF:**
```html
<!-- em buildPdfHtml() -->
<th>Seu Campo</th>
...
<td>%s</td> <!-- novo valor -->
```

### Customizar Estilos CSS

Edite a seção `<style>` em `buildPdfHtml()`:
- Cores: mudança simples em `background`, `color`
- Fonts: ajuste em `font-family`, `font-size`
- Margens/Padding: modifique propriedades CSS diretas

### Adicionar Novo Formato de Export

```php
public function index(Request $request): Response
{
    $format = (string)($params['format'] ?? 'html');
    
    if ($format === 'xml') {
        return $this->exportXml($records);
    }
    
    // ... resto do código
}

private function exportXml(array $records): Response { /* ... */ }
```

## 📝 Mudanças Recentes (v2.0)

- ✅ Removido TCPDF (e todas as dependências)
- ✅ Criado RelatoriosController.php
- ✅ Implementado template HTML com CSS print
- ✅ Movido todas as rotas para o novo controller
- ✅ Atualizado AdminController com delegação simples
- ✅ Atualizado DEPLOY_HOSTGATOR.md com nova estratégia

## 🧪 Testing Local

```bash
# 1. Iniciar servidor
cd c:\PROJETOS\clima_ete_novo
php -S localhost:8080 -t public

# 2. Acessar relatórios
# http://localhost:8080/admin/reports?period=7

# 3. Testar exportação CSV
# http://localhost:8080/admin/reports?period=7&format=csv

# 4. Testar exportação PDF
# http://localhost:8080/admin/reports?period=7&format=pdf&emitter=Teste
# Depois: Ctrl+P → Salvar como PDF
```

## 📦 Deploy

Veja [DEPLOY_HOSTGATOR.md](DEPLOY_HOSTGATOR.md) seção "Atualização: Exportação PDF v2.0"

**Resumo:**
- Faça upload de 3 arquivos (AdminController.php, RelatoriosController.php, public/index.php)
- **Nenhuma dependência adicional necessária**
- **Nenhuma configuração de servidor necessária**
- Teste em `/admin/reports`

---

**Última Atualização**: 14 de dezembro de 2025  
**Versão**: 2.0 (HTML + CSS)
