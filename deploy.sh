#!/bin/bash
echo "==> Enviando para o GitHub..."
git add .
git commit -m "${1:-deploy}"
git push

echo "==> Fazendo deploy no servidor..."
ssh root@209.126.103.238 "deploy-pierfans"

echo "==> Pronto!"
