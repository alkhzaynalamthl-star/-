#!/bin/sh
# Regenerate the theme's Arabic translation files. Needs WP-CLI (`wp`).
set -e
cd "$(dirname "$0")/../.."
WP=${WP:-wp}
$WP i18n make-pot optimum-closets /tmp/optimum.pot --domain=optimum --exclude=concepts,assets --allow-root
python3 tools/i18n/build.py /tmp/optimum.pot
$WP i18n make-mo optimum-closets/languages --allow-root
$WP i18n make-php optimum-closets/languages --allow-root
