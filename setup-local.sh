#!/usr/bin/env bash
# Local dev bootstrap for FutureBD (Laravel 11 + Inertia/React).
# Installs PHP 8.5, required extensions, and MySQL, then creates the dev database.
# Run once:  sudo bash setup-local.sh
set -euo pipefail

echo "==> Installing PHP 8.5 + extensions and MySQL server..."
apt-get update
apt-get install -y \
  php8.5-cli php8.5-mysql php8.5-mbstring php8.5-xml php8.5-curl \
  php8.5-zip php8.5-gd php8.5-bcmath php8.5-intl php8.5-sqlite3 \
  mysql-server unzip

echo "==> Starting MySQL..."
service mysql start || systemctl start mysql || true

echo "==> Creating database 'futurebdecom' and user 'futurebd'..."
mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS futurebdecom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'futurebd'@'127.0.0.1' IDENTIFIED BY 'futurebd';
CREATE USER IF NOT EXISTS 'futurebd'@'localhost' IDENTIFIED BY 'futurebd';
GRANT ALL PRIVILEGES ON futurebdecom.* TO 'futurebd'@'127.0.0.1';
GRANT ALL PRIVILEGES ON futurebdecom.* TO 'futurebd'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> Done. PHP $(php -r 'echo PHP_VERSION;') and MySQL are ready."
echo "    DB: futurebdecom  user: futurebd  pass: futurebd"
