# Guia de Execução dos Testes - Router @HellFiveOsborn (Fork of CoffeeCode Router)

## Instalação Rápida

```bash
# Instalar dependências
composer install

# Executar todos os testes
./vendor/bin/pest

# Executar apenas testes unitários
./vendor/bin/pest tests/Unit

# Executar apenas testes de integração
./vendor/bin/pest tests/Integration
```

## Estrutura Criada

### ✅ Arquivos de Configuração
- `phpunit.xml` - Configuração base (Pest usa PHPUnit por baixo)
- `tests/Pest.php` - Configuração específica do Pest
- `composer.json` - Atualizado com Pest PHP e binário de listagem de rotas

### ✅ Testes Unitários (tests/Unit/)
- `RouterTest.php` - 26 testes para classe Router
- `DispatchTest.php` - 33 testes para classe Dispatch  
- `RouterTraitTest.php` - 29 testes para RouterTrait

### ✅ Testes de Integração (tests/Integration/)
- `RoutingIntegrationTest.php` - 23 testes de cenários completos

### ✅ Infraestrutura de Testes
- `TestCase.php` - Classe base com utilitários
- `Pest.php` - Configuração e funções globais do Pest
- `README.md` - Documentação completa dos testes

## Cobertura de Testes

### Funcionalidades Testadas ✅
- Métodos HTTP (GET, POST, PUT, PATCH, DELETE)
- Roteamento com parâmetros
- Form spoofing
- Middleware (simples e em cadeia)
- Grupos de rotas
- Namespaces
- Geração de URLs
- Redirecionamentos
- Manipulação de assets
- Cache de assets
- Tratamento de erros (404, 405, 501, 400)

### Casos Extremos ✅
- Parâmetros nullable
- URLs com barra final
- Separadores customizados
- Dados vazios
- Middleware bloqueando acesso
- Arquivos fora do diretório permitido

## Status dos Testes

**Total**: 111 testes criados usando **Pest PHP**
- **Unitários**: 88 testes
- **Integração**: 23 testes

**Vantagens do Pest**:
- ✅ Sintaxe mais limpa e legível
- ✅ Menos código boilerplate
- ✅ Expectativas fluentes (`expect()->toBe()`)
- ✅ Funções auxiliares globais
- ✅ Output mais bonito e informativo

## Comandos Úteis do Pest

```bash
# Executar todos os testes
./vendor/bin/pest

# Executar com output detalhado
./vendor/bin/pest --verbose

# Executar testes específicos
./vendor/bin/pest tests/Unit/RouterTest.php

# Executar com filtro
./vendor/bin/pest --filter="Constructor"

# Executar em modo watch (reexecuta quando arquivos mudam)
./vendor/bin/pest --watch

# Executar apenas testes que falharam
./vendor/bin/pest --retry

# Executar em paralelo (mais rápido)
./vendor/bin/pest --parallel

# Gerar cobertura de código
./vendor/bin/pest --coverage

# Gerar cobertura em HTML
./vendor/bin/pest --coverage --coverage-html=coverage

# Verificar cobertura mínima
./vendor/bin/pest --coverage --min=80

# Listar todos os testes
./vendor/bin/pest --list-tests

# Executar com profiling
./vendor/bin/pest --profile
```

## Sintaxe do Pest

### Estrutura Básica
```php
<?php

use CoffeeCode\Router\Router; // namespace mantido por compatibilidade

beforeEach(function () {
    $this->router = new Router('http://localhost');
});

describe('Router Constructor', function () {
    it('creates router with project URL', function () {
        $router = new Router('http://example.com');
        expect($router)->toBeInstanceOf(Router::class);
        expect($router->home())->toBe('http://example.com');
    });
});
```

### Funções Auxiliares Globais
```php
// Simular requisições (definida em tests/Pest.php)
simulateRequest('POST', '/users', ['name' => 'John']);

// Capturar output
$output = captureOutput(fn() => $router->dispatch());

// Criar/remover arquivos temporários
$file = createTempFile('content', 'css');
removeTempFile($file);
```

### Expectativas Fluentes
```php
// Verificações básicas
expect($value)->toBe('expected');
expect($array)->toHaveKey('key');
expect($object)->toBeInstanceOf(Router::class);

// Verificações de string
expect($string)->toContain('substring');
expect($string)->toBeEmpty();

// Verificações de array
expect($array)->toBeArray();
expect($array)->toHaveCount(3);

// Verificações de tipo
expect($value)->toBeString();
expect($value)->toBeNull();
expect($value)->toBeBool();

// Verificações customizadas
expect($callable)->toBeCallable();
expect($result)->toBeFalse();
expect($result)->toBeTrue();
```

## Próximos Passos

1. **Instalar Pest**: `composer require --dev pestphp/pest`
2. **Executar testes**: `./vendor/bin/pest`
3. **Configurar CI/CD**: Usar Pest em pipelines
4. **Monitorar cobertura**: `./vendor/bin/pest --coverage`
5. **Adicionar novos testes**: Seguir sintaxe do Pest

## Configuração para CI/CD

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

## Estrutura de Arquivos Criada

```
/workspaces/router/
├── phpunit.xml                              # Configuração base
├── composer.json                            # Dependências atualizadas (Pest)
├── TESTING.md                               # Este guia
└── tests/
    ├── Pest.php                             # Configuração do Pest
    ├── TestCase.php                         # Classe base
    ├── README.md                            # Documentação detalhada
    ├── Unit/                                # Testes unitários
    │   ├── RouterTest.php                   # Testes da classe Router
    │   ├── DispatchTest.php                 # Testes da classe Dispatch
    │   └── RouterTraitTest.php              # Testes do RouterTrait
    └── Integration/                         # Testes de integração
        └── RoutingIntegrationTest.php       # Cenários completos
```

## Por que Pest?

### Vantagens sobre PHPUnit tradicional

1. **Sintaxe mais limpa**:
   ```php
   // PHPUnit
   public function testConstructorWithProjectUrl(): void
   {
       $router = new Router('http://example.com');
       $this->assertInstanceOf(Router::class, $router);
   }
   
   // Pest
   it('creates router with project URL', function () {
       $router = new Router('http://example.com');
       expect($router)->toBeInstanceOf(Router::class);
   });
   ```

2. **Menos boilerplate**: Não precisa de classes para cada teste
3. **Expectativas fluentes**: Mais legível que assertions
4. **Funções globais**: Helpers disponíveis em todos os testes
5. **Output bonito**: Interface mais amigável
6. **Compatibilidade total**: Roda sobre PHPUnit

**Todos os arquivos foram convertidos para Pest com sucesso!** 🎉

### Comandos de Teste Rápidos:
```bash
# Instalar e testar
composer install && ./vendor/bin/pest

# Ver cobertura
./vendor/bin/pest --coverage

# Modo desenvolvimento (watch)
./vendor/bin/pest --watch