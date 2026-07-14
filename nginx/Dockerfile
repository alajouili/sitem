FROM nginx:1.30.3-alpine3.23

# IMPORTANT — read this before docker-compose.yml, it explains a
# classic FastCGI gotcha:
#
# When Nginx proxies a request to PHP-FPM, it does NOT send the
# file's contents. It sends a PATH (via fastcgi_param
# SCRIPT_FILENAME) and tells PHP-FPM "go read and execute this
# file yourself." That means the path Nginx sends must be a path
# that's valid ON THE PHP-FPM CONTAINER'S OWN FILESYSTEM — the two
# containers are otherwise completely separate filesystems, so if
# Nginx's copy of public/ lived at a different path than PHP-FPM's
# copy, PHP-FPM would get a SCRIPT_FILENAME pointing at a path that
# doesn't exist on ITS side, and every request would fail with a
# "No input file specified" error — one of the most common
# PHP-FPM+Nginx misconfigurations in production.
#
# The fix: both containers must agree on ONE path for public/. We
# do NOT copy public/ into this image at all. Instead,
# docker-compose.yml mounts the backend's public/ directory as a
# SHARED, READ-ONLY volume into both containers at the identical
# path (/var/www/html/public). Nginx uses it to serve static
# assets and resolve try_files; PHP-FPM uses the identical path to
# actually execute index.php. One source of truth, no drift.
#
# So this Dockerfile intentionally does NOT COPY any app code —
# it only ships the config. The volume is wired up next, in
# docker-compose.yml.
COPY default.conf /etc/nginx/conf.d/default.conf
