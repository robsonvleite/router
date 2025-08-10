#!/bin/bash

echo "=== Executando Testes CoffeeCode Router ==="
echo ""

# Executa todos os arquivos de teste individualmente
for test_file in tests/Unit/*.php tests/Integration/*.php; do
    if [[ "$test_file" != *"Pest.php" && "$test_file" != *"TestCase.php" ]]; then
        echo "Executando: $test_file"
        ./vendor/bin/pest "$test_file"
        echo ""
    fi
done

echo "=== Testes Concluídos ==="