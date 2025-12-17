# Funcionalidade: Histórico Público com Exportação PDF/CSV

## Data de Implementação
**Commit**: `d6c5305`  
**Data**: 2025-01-12  
**Arquivos Modificados**:
- `src/Controller/PublicController.php`
- `src/Service/PublicViewService.php`

## Resumo
Estendeu a página pública `/live` com funcionalidade de histórico completo e exportação em múltiplos formatos (PDF, CSV), sem autenticação requerida. Permite aos usuários acessar, visualizar e baixar dados históricos de monitoramento climático.

## Funcionalidades Adicionadas

### 1. Método `getHistoryForExport()` em PublicViewService
**Localização**: [src/Service/PublicViewService.php](src/Service/PublicViewService.php#L156)

```php
public function getHistoryForExport(string $period): array
```

- **Propósito**: Recuperar registros históricos em quantidade variável conforme período
- **Parâmetros**:
  - `'24'` (padrão): Últimas 24 horas
  - `'168'`: Últimos 7 dias
  - `'720'`: Últimos 30 dias
  - `'all'`: Todos os registros (máx 10000)
- **Retorna**: Array de registros históricos em ordem cronológica
- **Dependências**: `HistoricsRepository::getLatest()`

### 2. Novos Métodos em PublicController
**Localização**: [src/Controller/PublicController.php](src/Controller/PublicController.php#L30)

#### 2.1 `live()` - Router Principal
- Aceita query parameters: `?format=csv|pdf|html` e `?period=24|168|720|all`
- Direciona para método apropriado ou renderiza HTML
- Mantém compatibilidade com `?api=1` para JSON

#### 2.2 `liveCsv()` - Exportação CSV
- Gera CSV com BOM UTF-8 para compatibilidade com Excel
- Headers: ID, Data/Hora, Temperatura, Umidade, Pressão, UV, Gas, Chuva, Status Chuva
- Download automático com nome: `historico_clima_YYYY-MM-DD_HHmmss.csv`

#### 2.3 `livePdf()` - Geração PDF
- Renderiza HTML formatado para impressão/PDF
- Aceita múltiplos períodos com label correspondente
- Integração com biblioteca de cliente para converter em PDF (JsPDF)

#### 2.4 `buildPublicPdfHtml()` - Geração HTML para PDF
- Template HTML com estilos CSS inline
- Inclui:
  - Logo e header institucional
  - Metadados: período, data emissão, total registros
  - Tabela formatada com dados históricos
  - Botão de impressão nativa do navegador
- Responsivo e adaptado para impressão

#### 2.5 `buildLiveHistorySection()` - Seção UI de Histórico
- Cards com 6 botões de download:
  - CSV 24h / CSV 7d / CSV 30d
  - PDF 24h / PDF 7d / PDF 30d
- JavaScript inline para chamar exportação com período correto
- Design responsivo com Tailwind CSS

### 3. Integração no Fluxo Renderização
**Localização**: [src/Controller/PublicController.php](src/Controller/PublicController.php#L389)

- Método `renderLive()` estendido para chamar `buildLiveHistorySection()`
- Nova seção adicionada após gráficos de 24h
- Mantém estrutura visual coerente com resto da página

## Endpoints Disponíveis

### Acesso Público (sem autenticação)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/live` | Página HTML interativa com histórico |
| GET | `/live?api=1` | JSON com dados atuais (compatibilidade) |
| GET | `/live?format=csv&period=24` | Download CSV últimas 24h |
| GET | `/live?format=csv&period=168` | Download CSV últimos 7 dias |
| GET | `/live?format=csv&period=720` | Download CSV últimos 30 dias |
| GET | `/live?format=pdf&period=24` | HTML para PDF últimas 24h |
| GET | `/live?format=pdf&period=168` | HTML para PDF últimos 7 dias |
| GET | `/live?format=pdf&period=720` | HTML para PDF últimos 30 dias |

## Fluxo de Uso

### 1. Usuário Acessa `/live`
- Vê leitura atual com métricas e gráficos 24h
- Nova seção "Histórico de Leituras" com 6 botões

### 2. Clica em "CSV 24h" (exemplo)
- GET `/live?format=csv&period=24`
- PublicController.live() chama liveCsv('24')
- PublicViewService.getHistoryForExport('24') retorna 24 registros
- generateCSV() cria arquivo com BOM UTF-8
- Navegador faz download automático

### 3. Clica em "PDF 7d"
- GET `/live?format=pdf&period=168`
- PublicController.live() chama livePdf('168')
- buildPublicPdfHtml() gera HTML com 7 dias de dados
- HTML aberto em nova aba com botão "Imprimir / Salvar como PDF"
- Usuário usa Ctrl+P ou clica botão para gerar PDF

## Segurança e Validação

- **Sem autenticação**: Endpoints públicos, dados climáticos não-sensíveis
- **Escape XSS**: Método `e()` applica htmlspecialchars em valores exibidos
- **Limit de registros**: Máximo 10000 para 'all', protege performance
- **Validação de período**: Switch/match para valores permitidos

## Compatibilidade

- **PHP**: 8.1+
- **Navegadores**: Todos modernos (Chrome, Firefox, Safari, Edge)
- **Excel**: CSV com BOM UTF-8 para caracteres especiais
- **Impressão**: HTML puro, funciona em qualquer navegador

## Testes Realizados

✅ Sem erros de sintaxe PHP  
✅ Métodos retornam conforme esperado  
✅ CSV gera com formato correto  
✅ PDF HTML renderiza completo  
✅ Query parameters parseados corretamente  
✅ Compatibilidade com endpoint antigo `/live?api=1`  
✅ Regressão: /live sem parâmetros continua exibindo página normal  

## Próximas Melhorias (Opcional)

1. Adicionar paginação à tabela histórica na UI HTML
2. Suporte a filtros por data customizada
3. Gráficos adicionais no PDF (charts.js server-side)
4. Compressão gzip para grandes exports
5. Cache de última exportação por período
6. Autenticação opcional com token para acesso programático

## Referência

Padrão implementado baseado em `RelatoriosController` (endpoints protegidos), adaptado para contexto público:
- [RelatoriosController.php](src/Controller/RelatoriosController.php) - Referência para export logic
- [PublicViewService.php](src/Service/PublicViewService.php) - Camada de dados
- [HistoricsRepository.php](src/Repository/HistoricsRepository.php) - Acesso BD

