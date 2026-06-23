#!/bin/bash
#
# Deploy pierfans — com gate de validacao ANTES de empurrar para producao.
# O gate roda local (a maquina tem PHP, mas nao tem vendor), entao validamos
# o que da pra validar sem o framework: Blade suspeito + sintaxe PHP.
#

echo "==> Validando antes do deploy..."

# -------------------------------------------------------------------
# 1. Proibe '@{{' em views Blade.
#    @{{ }} escapa o Blade e renderiza as chaves LITERAIS na tela.
#    Este projeto usa jQuery (sem Vue/Angular), entao '@{{' e sempre bug.
#    Foi exatamente o que vazou pra producao em 23/06/2026.
# -------------------------------------------------------------------
if grep -rn --include='*.blade.php' '@{{' resources/views; then
    echo ""
    echo "ERRO: '@{{' encontrado na(s) view(s) acima — renderiza literal na tela."
    echo "      Troque por '{{' antes de fazer deploy."
    exit 1
fi

# -------------------------------------------------------------------
# 2. Lint de sintaxe PHP nos arquivos .php modificados/novos.
#    Pega aspas nao fechadas, parenteses, etc. antes de ir pro ar.
# -------------------------------------------------------------------
falhou=0
for arquivo in $(git status --porcelain | awk '{print $NF}' | grep '\.php$'); do
    if [ -f "$arquivo" ]; then
        if ! php -l "$arquivo" > /dev/null 2>&1; then
            echo "ERRO de sintaxe PHP em: $arquivo"
            php -l "$arquivo"
            falhou=1
        fi
    fi
done
if [ "$falhou" -ne 0 ]; then
    echo ""
    echo "Deploy abortado por erro de sintaxe PHP."
    exit 1
fi

echo "==> Validacao OK."

echo "==> Enviando para o GitHub..."
git add .
git commit -m "${1:-deploy}"
git push

echo "==> Fazendo deploy no servidor..."
ssh root@209.126.103.238 "deploy-pierfans"

echo "==> Pronto!"
