#!/usr/bin/env bash
# ينشئ optimum-closets.zip الجاهز للرفع على ووردبريس.
set -euo pipefail
cd "$(dirname "$0")"
rm -f optimum-closets.zip
zip -rq optimum-closets.zip optimum-closets -x '*.DS_Store'
echo "تم إنشاء optimum-closets.zip"
