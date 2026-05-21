#!/bin/bash
set -e

# Generate JWT keys if they don't exist
if [ ! -f config/jwt/private.pem ]; then
    echo "Generating JWT keys..."
    openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:${JWT_PASSPHRASE}
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:${JWT_PASSPHRASE}
    chown www-data:www-data config/jwt/*.pem
fi

# Clear and warm up cache
php bin/console cache:clear --env=prod --no-debug || true
php bin/console cache:warmup --env=prod --no-debug || true

# Run database migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true

exec "$@"
