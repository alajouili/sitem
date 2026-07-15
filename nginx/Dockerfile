FROM nginx:1.30.3-alpine3.23

# Explicit upgrade for OS packages Trivy flagged as HIGH severity.
# c-ares/libexpat: caught in the previous scan. curl/libcurl: NEW
# findings (CVE-2026-5773, CVE-2026-6276) that appeared between
# yesterday's scan and today's — confirming base images keep
# accumulating fresh CVEs even when nothing in OUR code changes.
# This is exactly why the Security workflow runs weekly, not just
# on push — this fix is a real example of that mechanism working
# as intended, not a bug in anything we built.
RUN apk upgrade --no-cache c-ares libexpat curl

COPY default.conf /etc/nginx/conf.d/default.conf
