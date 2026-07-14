# syntax=docker/dockerfile:1

# ============================================================
# STAGE 1: "builder" — build-time only, discarded after build
# ============================================================
FROM composer:2 AS builder

WORKDIR /app

# Copy only what composer needs first (not the whole app yet).
# This is a caching optimization: Docker caches each layer, and
# only re-runs a layer if the files it depends on changed. If we
# copied all app code before running composer install, ANY code
# change (even a typo fix in a Controlleit r) would invalidate the
# composer cache layer and force a full re-resolve. By copying
# just composer.json/composer.lock first, `composer install`
# only re-runs when dependencies actually change.
COPY composer.json composer.lock* ./

# --no-dev: skip any dev-only tooling (irrelevant today since you
#           have zero packages, but this is the correct default
#           for every PHP project so it's here as a durable habit)
# --no-scripts: don't run composer lifecycle scripts (security
#           best practice — scripts can execute arbitrary code
#           during install; we don't need any here)
# --optimize-autoloader: generates a class map instead of doing
#           PSR-4 filesystem lookups at runtime — faster app boot
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# Now copy the rest of the application source
COPY . .

# Re-run to regenerate the autoloader against the FULL app/ tree
# (the first install only had composer.json, no actual classes yet)
RUN composer dump-autoload --optimize --no-dev


# ============================================================
# STAGE 2: "runtime" — this is what actually ships
# ============================================================
FROM php:8.3-fpm-alpine AS runtime

# Install OS-level packages needed to BUILD certain PHP extensions,
# install the extensions, then remove the build-only packages in
# the same layer — keeps the final image lean.
#
# Mapping back to Section 2 of the handover doc:
#   zip        -> reading .xlsx as zip archives (ExcelHelper.php)
#   simplexml/ -> parsing XML inside .xlsx (worksheets, shared
#   dom           strings, drawing anchors/image metadata)
#   gd         -> thumbnails, image dimension reading
#   mysqli/    -> not required (we use pdo_mysql only, per
#   fileinfo      Section 1's "single PDO singleton" architecture)
RUN apk add --no-cache \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        file \
    # Explicitly upgrade c-ares — a transitive dependency baked
    # into the php:8.3-fpm-alpine base image itself (not something
    # WE install), flagged by Trivy as CVE-2026-33630 (HIGH,
    # use-after-free/double-free). The fixed version already exists
    # in Alpine's package repos, so we don't need to wait for a new
    # base image release — patching it explicitly, in our own
    # layer, closes the gap immediately regardless of when (or if)
    # the upstream php image gets rebuilt against a newer Alpine
    # snapshot. This is a real, ongoing DevSecOps habit, not a
    # one-time fix: base images accumulate new CVEs over time even
    # when your own code never changes, which is exactly why the
    # security workflow also runs on a weekly schedule, not just
    # on push.
    && apk upgrade --no-cache c-ares \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    # $PHPIZE_DEPS is provided by the official php base image itself
    # (autoconf, gcc, make, etc — the full compiler toolchain needed
    # to build extensions from source on Alpine). Folding it into
    # OUR OWN .build-deps virtual group, instead of letting
    # docker-php-ext-install manage it separately and silently,
    # guarantees `apk del .build-deps` below removes it too. Without
    # this, the compiler toolchain survives into the final image
    # untracked — this was the dominant cause of the 806MB bloat.
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        zip \
        gd \
    && apk del .build-deps \
    && docker-php-source delete

# mbstring, json, simplexml, dom, fileinfo ship built into the
# official php:8.3-fpm-alpine image already — no install needed.
# (We verified this matches your local `php -m` output from Phase 1.)

WORKDIR /var/www/html

# Copy the application code AND the vendor/ produced in Stage 1.
# Note what's NOT here: no composer binary, no composer.json
# download cache, no build-only apk packages (removed above).
COPY --from=builder /app /var/www/html

# Create the runtime-writable directories from Section 6 of the
# handover doc, and set ownership to the standard php-fpm user
# (www-data) instead of root — running as root inside a container
# is a security anti-pattern we're avoiding from day one, not
# retrofitting later in Phase 10.
RUN mkdir -p storage/uploads/excel storage/uploads/images storage/logs \
    && chown -R www-data:www-data storage

USER www-data

EXPOSE 9000

# PHP-FPM's own health check: confirms the FPM master process is
# alive and accepting connections on port 9000. This is a
# CONTAINER-level check, separate from the application-level
# /api/health endpoint Nginx will expose over HTTP — we'll wire
# the HTTP-level check into docker-compose.yml next.
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s \
    CMD php -r "exit(0);" || exit 1

CMD ["php-fpm"]