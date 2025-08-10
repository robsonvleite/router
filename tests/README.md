# Testes do CoffeeCode Router

Este diretório contém todos os testes unitários e de integração para o pacote CoffeeCode Router usando **Pest PHP**.

## Estrutura dos Testes

```
tests/
├── Pest.php                                  # Configuração do Pest
├── TestCase.php                              # Classe base para todos os testes
├── Unit/                                     # Testes unitários
│   ├── RouterTest.php                        # Testes da classe Router
│   ├── DispatchTest.php                      # Testes da classe Dispatch
│   └── RouterTraitTest.php                   # Testes do RouterTrait
├── Integration/                              # Testes de integração
│   └── RoutingIntegrationTest.php            # Testes de cenários completos
└── README.md                                 # Este arquivo
```

## Pré-requisitos

1. **PHP 8.0+** - Versão mínima requerida pelo pacote
2. **Composer** - Para gerenciar dependências
3. **Pest PHP 2.0+** - Framework de testes

## Instalação das Dependências

```bash
# Instalar dependências de desenvolvimento
composer install --dev

# Ou apenas atualizar dependências
composer update --dev
```

## Executando os Testes

### Todos os Testes

```bash
# Executar todos os testes
./vendor/bin/pest

# Ou usando composer script (se configurado)
composer test
```

### Testes por Categoria

```bash
# Apenas testes unitários
./vendor/bin/pest tests/Unit

# Apenas testes de integração
./vendor/bin/pest tests/Integration
```

### Testes Específicos

```bash
# Testar apenas a classe Router
./vendor/bin/pest tests/Unit/RouterTest.php

# Testar apenas a classe Dispatch
./vendor/bin/pest tests/Unit/DispatchTest.php

# Testar apenas o RouterTrait
./vendor/bin/pest tests/Unit/RouterTraitTest.php

# Testar apenas integração
./vendor/bin/pest tests/Integration/RoutingIntegrationTest.php
```

### Testes com Filtros

```bash
# Executar apenas testes que contêm "Constructor" no nome
./vendor/bin/pest --filter="Constructor"

# Executar apenas testes de um grupo específico
./vendor/bin/pest --group=unit

# Executar testes com output detalhado
./vendor/bin/pest --verbose
```

### Testes com Cobertura

```bash
# Gerar relatório de cobertura em HTML
./vendor/bin/pest --coverage --coverage-html=coverage-html

# Gerar relatório de cobertura em texto
./vendor/bin/pest --coverage --coverage-text

# Gerar relatório de cobertura em XML (para CI/CD)
./vendor/bin/pest --coverage --coverage-clover=coverage.xml

# Verificar cobertura mínima
./vendor/bin/pest --coverage --min=80
```

## Configuração do Pest

O arquivo `tests/Pest.php` contém todas as configurações:

- **TestCase Base**: Usa `CoffeeCode\Router\Tests\TestCase`
- **Funções Globais**: `simulateRequest()`, `captureOutput()`, etc.
- **Expectativas Customizadas**: Extensões do `expect()`

## Estrutura dos Testes

### Configuração Base (Pest.php)

O arquivo `Pest.php` fornece:

- Configuração global para todos os testes
- Funções auxiliares globais
- Expectativas customizadas
- Setup automático da classe TestCase

### Funções Auxiliares Globais

```php
// Simular requisições HTTP
simulateRequest('POST', '/users', ['name' => 'John']);

// Capturar output
$output = captureOutput(fn() => $router->dispatch());

// Criar arquivos temporários
$file = createTempFile('content', 'css');

// Remover arquivos temporários
removeTempFile($file);
```

### Sintaxe do Pest

#### Testes Básicos
```php
it('creates router with project URL', function () {
    $router = new Router('http://example.com');
    expect($router)->toBeInstanceOf(Router::class);
    expect($router->home())->toBe('http://example.com');
});
```

#### Grupos de Testes
```php
describe('HTTP Methods', function () {
    it('registers GET route', function () {
        // teste aqui
    });
    
    it('registers POST route', function () {
        // teste aqui
    });
});
```

#### Setup por Teste
```php
beforeEach(function () {
    $this->router = new Router('http://localhost');
});
```

### Testes Unitários

#### RouterTest.php
Testa todos os métodos da classe `Router`:
- Métodos HTTP (GET, POST, PUT, PATCH, DELETE)
- Configuração de cache e assets
- Manipulação de arquivos estáticos
- Validação de parâmetros nullable

#### DispatchTest.php
Testa a funcionalidade base da classe `Dispatch`:
- Roteamento e dispatch
- Grupos e namespaces
- Redirecionamentos
- Tratamento de erros
- Geração de URLs

#### RouterTraitTest.php
Testa as funcionalidades do `RouterTrait`:
- Adição de rotas
- Form spoofing
- Middleware
- Processamento de handlers e actions

### Testes de Integração

#### RoutingIntegrationTest.php
Testa cenários completos de uso:
- Roteamento com parâmetros
- CRUD completo
- Middleware em cadeia
- Form spoofing
- Grupos de rotas
- Controllers e namespaces

## Casos de Teste Cobertos

### Casos de Sucesso
- ✅ Roteamento básico (GET, POST, PUT, PATCH, DELETE)
- ✅ Parâmetros de rota (`/user/{id}`)
- ✅ Múltiplos parâmetros (`/user/{id}/post/{postId}`)
- ✅ Form spoofing (PUT/PATCH/DELETE via POST)
- ✅ Middleware (simples e em cadeia)
- ✅ Grupos de rotas
- ✅ Namespaces de controllers
- ✅ Geração de URLs por nome
- ✅ Redirecionamentos
- ✅ Manipulação de assets estáticos
- ✅ Cache de assets

### Casos de Erro
- ✅ Rota não encontrada (404)
- ✅ Método não implementado (501)
- ✅ Método não permitido (405)
- ✅ Requisição inválida (400)
- ✅ Middleware bloqueando acesso
- ✅ Controller/método inexistente
- ✅ Arquivos fora do diretório permitido

### Casos Extremos
- ✅ Parâmetros nullable
- ✅ URLs com barra final
- ✅ Separadores customizados
- ✅ Dados vazios
- ✅ Middleware sem método handle
- ✅ Rotas com caracteres especiais

## Executando Testes em CI/CD

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.0, 8.1, 8.2, 8.3]
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, xml, ctype, json
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run tests
      run: ./vendor/bin/pest --coverage --coverage-clover=coverage.xml
    
    - name: Upload coverage
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
```

## Comandos Úteis do Pest

```bash
# Executar testes com output bonito
./vendor/bin/pest

# Executar testes em modo watch (reexecuta quando arquivos mudam)
./vendor/bin/pest --watch

# Executar apenas testes que falharam na última execução
./vendor/bin/pest --retry

# Executar testes em paralelo (mais rápido)
./vendor/bin/pest --parallel

# Executar testes com profiling
./vendor/bin/pest --profile

# Listar todos os testes disponíveis
./vendor/bin/pest --list-tests

# Executar testes com timeout customizado
./vendor/bin/pest --timeout=30
```

## Métricas de Qualidade

### Cobertura de Código
- **Meta**: 95%+ de cobertura
- **Verificar**: `./vendor/bin/pest --coverage`

### Tipos de Teste
- **Unitários**: Testam classes isoladamente
- **Integração**: Testam fluxos completos
- **Casos extremos**: Testam limites e erros

## Contribuindo

Ao adicionar novos recursos:

1. **Escreva testes primeiro** (TDD)
2. **Mantenha cobertura alta** (>95%)
3. **Teste casos de erro** além dos de sucesso
4. **Use sintaxe descritiva** do Pest
5. **Documente casos complexos**

### Convenções de Nomenclatura do Pest

```php
// Use describe() para agrupar testes relacionados
describe('HTTP Methods', function () {
    // Use it() para descrever o comportamento esperado
    it('registers GET route', function () {
        // teste aqui
    });
    
    it('registers POST route with middleware', function () {
        // teste aqui
    });
});

// Para testes mais específicos
it('handles form spoofing with DELETE method', function () {
    // teste aqui
});
```

## Troubleshooting

### Problemas Comuns

1. **Erro "Class not found"**
   ```bash
   composer dump-autoload
   ```

2. **Testes falhando por headers**
   - Verifique se não há output antes dos testes
   - Use `captureOutput()` quando necessário

3. **Problemas com arquivos temporários**
   - Verifique permissões do diretório `/tmp`
   - Use as funções auxiliares `createTempFile()` e `removeTempFile()`

4. **Cobertura não funcionando**
   ```bash
   # Instalar Xdebug
   sudo apt-get install php-xdebug
   
   # Ou usar PCOV (mais rápido)
   sudo apt-get install php-pcov
   ```

5. **Pest não encontrado**
   ```bash
   # Verificar se está instalado
   composer show pestphp/pest
   
   # Reinstalar se necessário
   composer require --dev pestphp/pest
   ```

## Recursos Adicionais

- [Documentação do Pest](https://pestphp.com/docs)
- [Expectativas do Pest](https://pestphp.com/docs/expectations)
- [Plugins do Pest](https://pestphp.com/docs/plugins)
- [Documentação do CoffeeCode Router](../README.md)

## Vantagens do Pest sobre PHPUnit

- ✅ **Sintaxe mais limpa**: `it()` e `describe()` são mais legíveis
- ✅ **Menos boilerplate**: Não precisa de classes para cada teste
- ✅ **Expectativas fluentes**: `expect($value)->toBe()`
- ✅ **Funções globais**: Helpers disponíveis em todos os testes
- ✅ **Output bonito**: Interface mais amigável
- ✅ **Compatibilidade**: Roda sobre PHPUnit, mantendo toda funcionalidade