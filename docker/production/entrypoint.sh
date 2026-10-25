#!/bin/sh
# Entrypoint image produksi EduwGo (docs/DEPLOY.md).
# Cache config/route/view dibuat saat start karena env var baru tersedia di runtime.
set -e

cd /var/www/html

# Sertifikat CA MySQL (Aiven). Pilih salah satu:
#  1. Secret File Render -> MYSQL_ATTR_SSL_CA=/etc/secrets/aiven-ca.pem.
#     Disalin ke /tmp/mysql-ca.pem (644) supaya pasti terbaca Apache (www-data).
#  2. Isi PEM lewat env MYSQL_SSL_CA_PEM (teks PEM) atau MYSQL_SSL_CA_BASE64 (PEM di-base64).
#     Isinya ditulis ke /tmp/mysql-ca.pem dan MYSQL_ATTR_SSL_CA diarahkan ke file itu (menimpa cara 1).
# Tanpa semuanya, koneksi MySQL tanpa opsi SSL (sama seperti lokal/Sail).
CA_FILE=/tmp/mysql-ca.pem
if [ -n "${MYSQL_SSL_CA_PEM:-}" ]; then
    printf '%s\n' "$MYSQL_SSL_CA_PEM" > "$CA_FILE"
elif [ -n "${MYSQL_SSL_CA_BASE64:-}" ]; then
    printf '%s' "$MYSQL_SSL_CA_BASE64" | base64 -d > "$CA_FILE"
elif [ -n "${MYSQL_ATTR_SSL_CA:-}" ] && [ -r "$MYSQL_ATTR_SSL_CA" ] && [ "$MYSQL_ATTR_SSL_CA" != "$CA_FILE" ]; then
    cp "$MYSQL_ATTR_SSL_CA" "$CA_FILE"
fi
if [ -s "$CA_FILE" ]; then
    chmod 644 "$CA_FILE"
    export MYSQL_ATTR_SSL_CA="$CA_FILE"
fi
if [ -n "${MYSQL_ATTR_SSL_CA:-}" ] && [ ! -r "$MYSQL_ATTR_SSL_CA" ]; then
    echo "[entrypoint] PERINGATAN: file CA MySQL tidak terbaca: $MYSQL_ATTR_SSL_CA" >&2
fi

php artisan optimize

# Migrasi otomatis hanya bila diminta (default mati). Seeder TIDAK pernah dijalankan di sini.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

# File cache/log yang baru dibuat root harus bisa ditulis Apache (www-data)
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
