FROM nginx:1.30.3-alpine3.23

# Explicit upgrade for two packages Trivy flagged as HIGH severity
# (CVE-2026-33630 in c-ares, plus three libexpat CVEs) — both are
# transitive OS packages baked into this base image, not something
# we install ourselves. Same reasoning as the equivalent fix in
# backend/Dockerfile: the patched versions already exist in
# Alpine's repos, so there's no need to wait for a new base image
# release. This Dockerfile otherwise runs zero apk commands, so
# this single RUN is added purely for this patch.
RUN apk upgrade --no-cache c-ares libexpat

COPY default.conf /etc/nginx/conf.d/default.conf
