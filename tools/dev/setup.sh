#!/usr/bin/env bash
# Local development store: WordPress + WooCommerce on SQLite with this theme
# and the demo data. No MySQL or Docker needed; PHP 8.1+ (with pdo_sqlite, gd,
# intl, zip), Composer, Node 18+, git.
#
#   tools/dev/setup.sh            # installs into ./.dev (git-ignored)
#   tools/dev/serve.sh            # http://localhost:8080  (admin / admin)
#
# Everything is downloaded from GitHub / Packagist / npm, so it also works where
# wordpress.org is not reachable. WooCommerce is assembled from its source tag.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DEV="${DEV_DIR:-$ROOT/.dev}"
WP_VERSION="${WP_VERSION:-6.8.3}"
WC_VERSION="${WC_VERSION:-9.8.5}"
SQLITE_VERSION="${SQLITE_VERSION:-v2.2.3}"
PORT="${PORT:-8080}"
export COMPOSER_ALLOW_SUPERUSER=1

mkdir -p "$DEV" && cd "$DEV"

echo "→ WordPress $WP_VERSION"
[ -d wp ] || git clone -q --depth 1 --branch "$WP_VERSION" https://github.com/WordPress/WordPress wp

echo "→ WP-CLI"
if [ ! -x wpcli/vendor/bin/wp ]; then
	mkdir -p wpcli && (cd wpcli && composer require -q --prefer-source wp-cli/wp-cli-bundle)
fi
WP="$DEV/wpcli/vendor/bin/wp --path=$DEV/wp --allow-root"

echo "→ WooCommerce $WC_VERSION (from source)"
if [ ! -f wcsrc/plugins/woocommerce/woocommerce.php ]; then
	git clone -q --depth 1 --filter=blob:none --sparse --branch "$WC_VERSION" https://github.com/woocommerce/woocommerce wcsrc
	(cd wcsrc && git sparse-checkout set plugins/woocommerce packages/php)
fi
WC="$DEV/wcsrc/plugins/woocommerce"
if [ ! -d "$WC/vendor" ]; then
	(cd "$WC" && composer install -q --no-dev --no-interaction --prefer-source)
fi
if [ ! -f "$WC/includes/react-admin/feature-config.php" ]; then
	(cd "$WC/client/admin" && php ../../bin/generate-feature-config.php)
fi
if [ ! -d "$WC/assets/js/frontend" ]; then
	# Classic storefront scripts/styles (the block editor bundles are not needed by this theme).
	(cd "$WC/client/legacy/js" && find . -name '*.js' | while read -r f; do
		d="$WC/assets/js/$(dirname "$f")"; mkdir -p "$d"; cp "$f" "$d/"
		b="$(basename "$f" .js)"; case "$f" in *.min.js) ;; *) [ -f "$(dirname "$f")/$b.min.js" ] || cp "$f" "$d/$b.min.js";; esac
	done)
	npm i -s --prefix "$DEV/node" sass@1 sourcebuster >/dev/null
	mkdir -p "$WC/assets/css" "$WC/assets/js/sourcebuster"
	for f in "$WC"/client/legacy/css/*.scss; do
		b="$(basename "$f" .scss)"; case "$b" in _*) continue;; esac
		"$DEV/node/node_modules/.bin/sass" --no-source-map --quiet "$f" "$WC/assets/css/$b.css" && cp "$WC/assets/css/$b.css" "$WC/assets/css/$b-rtl.css" || true
	done
	cp "$DEV/node/node_modules/sourcebuster/dist/sourcebuster.min.js" "$WC/assets/js/sourcebuster/sourcebuster.min.js"
	cp "$DEV/node/node_modules/sourcebuster/dist/sourcebuster.min.js" "$WC/assets/js/sourcebuster/sourcebuster.js"
fi
ln -sfn "$WC" wp/wp-content/plugins/woocommerce

echo "→ SQLite database integration"
P=wp/wp-content/plugins/sqlite-database-integration
[ -d "$P" ] || git clone -q --depth 1 --branch "$SQLITE_VERSION" https://github.com/WordPress/sqlite-database-integration "$P"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$DEV/$P#" -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" "$P/db.copy" > wp/wp-content/db.php

echo "→ Theme"
ln -sfn "$ROOT/optimum-closets" wp/wp-content/themes/optimum-closets

if [ ! -f wp/wp-config.php ]; then
	$WP config create --dbname=wp --dbuser=x --dbpass=x --skip-check --extra-php <<'PHP'
define( 'DB_DIR', __DIR__ . '/wp-content/database/' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
define( 'WP_ENVIRONMENT_TYPE', 'development' );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
PHP
fi
if ! $WP core is-installed 2>/dev/null; then
	$WP core install --url="http://localhost:$PORT" --title="الخزائن الأمثل" --admin_user=admin --admin_password=admin --admin_email=dev@example.com --skip-email
fi
$WP plugin activate woocommerce >/dev/null
$WP theme activate optimum-closets >/dev/null
$WP rewrite structure '/%postname%/' >/dev/null
$WP wc tool run install_pages --user=admin >/dev/null 2>&1 || true
$WP optimum demo import --store-settings

cat > "$DEV/router.php" <<'PHP'
<?php
$root = __DIR__ . '/wp';
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $root . $path;
if ( '/' !== $path && file_exists( $file ) && ! is_dir( $file ) ) { return false; }
if ( is_dir( $file ) && file_exists( $file . '/index.php' ) ) { $_SERVER['SCRIPT_NAME'] = rtrim( $path, '/' ) . '/index.php'; require $file . '/index.php'; return; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir( $root );
require $root . '/index.php';
PHP

echo
echo "Done. Start the store with: tools/dev/serve.sh   →  http://localhost:$PORT  (admin / admin)"
