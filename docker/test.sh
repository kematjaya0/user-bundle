#!/bin/sh
# Run the tests in Docker for one PHP x Symfony combination.
#   sh docker/test.sh 7.4 5.4    # PHP 7.4 + Symfony 5.4
#   sh docker/test.sh 8.1 6.4    # PHP 8.1 + Symfony 6.4
#   sh docker/test.sh all        # both
# Extra arguments go to phpunit, e.g.: sh docker/test.sh 8.1 6.4 --filter Login
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

run() {
    php="$1"; sf="$2"; shift 2
    image="kmj-user-bundle-test:php${php}"
    echo "=== PHP ${php} / Symfony ${sf}"
    docker build -q --build-arg PHP_VERSION="$php" -t "$image" "$ROOT/docker" > /dev/null
    # Sources are mounted read-only and copied, so each matrix gets its own vendor/.
    docker run --rm \
        -v "$ROOT":/src:ro \
        -v kmj-user-bundle-composer-cache:/root/.composer/cache \
        -e SYMFONY_REQUIRE="${sf}.*" \
        -e SYMFONY_DEPRECATIONS_HELPER="${SYMFONY_DEPRECATIONS_HELPER:-max[self]=0&verbose=0}" \
        "$image" sh -c '
            set -e
            tar -C /src --exclude=./vendor --exclude=./composer.lock --exclude=./.git --exclude=./var -cf - . | tar -C /app -xf -
            # Only Twig versions with security advisories support PHP 7.4; composer blocks them by default.
            composer config audit.block-insecure false
            composer update --no-interaction --no-progress --prefer-dist -q
            composer show "symfony/security-bundle" | grep -E "^versions"
            vendor/bin/phpunit "$@"
        ' phpunit "$@"
}

if [ "${1:-all}" = "all" ]; then
    shift $(( $# > 0 ? 1 : 0 ))
    run 7.4 5.4 "$@"
    run 8.1 6.4 "$@"
else
    php="$1"; sf="$2"; shift 2
    run "$php" "$sf" "$@"
fi
