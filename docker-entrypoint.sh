#!/bin/bash
set -e

# Build .env.local.php from actual OS environment variables
# (composer dump-env only reads .env files, not runtime env vars)
php -r "
\$vars = [
    'APP_ENV' => getenv('APP_ENV') ?: 'prod',
    'APP_SECRET' => getenv('APP_SECRET') ?: '',
    'APP_DEBUG' => '0',
    'DATABASE_URL' => getenv('DATABASE_URL') ?: '',
    'JWT_SECRET_KEY' => getenv('JWT_SECRET_KEY') ?: '%kernel.project_dir%/config/jwt/private.pem',
    'JWT_PUBLIC_KEY' => getenv('JWT_PUBLIC_KEY') ?: '%kernel.project_dir%/config/jwt/public.pem',
    'JWT_PASSPHRASE' => getenv('JWT_PASSPHRASE') ?: '',
    'CORS_ALLOW_ORIGIN' => getenv('CORS_ALLOW_ORIGIN') ?: '^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$',
    'MESSENGER_TRANSPORT_DSN' => getenv('MESSENGER_TRANSPORT_DSN') ?: 'doctrine://default?auto_setup=0',
    'MAILER_DSN' => getenv('MAILER_DSN') ?: 'null://null',
    'GOOGLE_CLIENT_ID' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'GOOGLE_CLIENT_SECRET' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    'TRUSTED_PROXIES' => getenv('TRUSTED_PROXIES') ?: '0.0.0.0/0',
    'DEFAULT_URI' => getenv('DEFAULT_URI') ?: 'doctrine://default',
];
// Remove empty values so Symfony doesn't set blank strings
\$vars = array_filter(\$vars, fn(\$v) => \$v !== '');
file_put_contents('/var/www/html/.env.local.php', '<?php return ' . var_export(\$vars, true) . ';' . PHP_EOL);
echo \"Built .env.local.php with \" . count(\$vars) . \" variables\n\";
"

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

# Update database schema (migrations are MySQL-specific, this works for PostgreSQL)
php bin/console doctrine:schema:update --force --no-interaction || true

# Fix permissions AFTER cache rebuild so www-data can access everything
chown -R www-data:www-data /var/www/html/var || true
chmod -R 775 /var/www/html/var || true

exec "$@"
