# syntax=docker/dockerfile:1

# ============================================================
# STAGE 1: "builder" — build-time only, discarded after build
# ============================================================
FROM node:20-alpine AS builder

WORKDIR /app

# Same layer-caching principle as the backend: copy ONLY the
# manifest files first. `npm install` only re-runs when
# package.json/package-lock.json actually change — editing a
# .jsx file won't invalidate this cached layer, so rebuilds
# during active development stay fast.
COPY package.json package-lock.json ./

# `npm ci` (not `npm install`) is deliberate here, not a typo:
#   - npm ci installs EXACTLY what's in package-lock.json,
#     failing loudly if package.json and the lockfile disagree.
#   - npm install can silently update the lockfile and install
#     slightly different versions than what's committed.
# For a reproducible build (the entire point of this Dockerfile),
# ci is the correct choice — same lockfile in, same node_modules
# out, every single time, on every machine.
RUN npm ci

# Now bring in the actual source
COPY . .

# Reads VITE_-prefixed vars at BUILD time (see the note below —
# this is the one genuinely tricky part of this file).
ARG VITE_API_BASE_URL=/api
ARG VITE_APP_NAME="Archive System"
ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}
ENV VITE_APP_NAME=${VITE_APP_NAME}

RUN npm run build
# Output: /app/dist  (static HTML/CSS/JS only, per Section 3
# of the handover doc — nothing else in this directory)


# ============================================================
# STAGE 2: "runtime" — this is what actually ships
# ============================================================
FROM nginx:1.30.3-alpine3.23 AS runtime

# c-ares/libexpat: caught in the previous scan. curl/libcurl: NEW
# findings (CVE-2026-5773, CVE-2026-6276) that appeared since
# yesterday — same base image drift as nginx/Dockerfile.
RUN apk upgrade --no-cache c-ares libexpat curl

# Nothing from Stage 1 comes across except the compiled static
# output. No Node.js binary, no npm, no node_modules/, no React
# source, no package-lock.json — none of it exists in this image.
COPY --from=builder /app/dist /usr/share/nginx/html

COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

# Nginx's default image already has a sensible master process
# setup; we only need to confirm it's actually answering HTTP,
# since that's the one thing a static file server can genuinely
# fail at (misconfigured conf, permissions, etc.)
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s \
    CMD wget --quiet --spider http://127.0.0.1/ || exit 1
    # Using 127.0.0.1 explicitly instead of "localhost" is
    # deliberate: BusyBox wget on Alpine (musl libc) can resolve
    # "localhost" to the IPv6 loopback (::1) first. If Nginx isn't
    # cleanly listening on IPv6 loopback inside this container —
    # a known rough edge with Docker Desktop's networking on
    # Windows — the healthcheck fails with "Connection refused"
    # even though the app is genuinely healthy and reachable via
    # IPv4 (which is what all real external traffic uses anyway,
    # via Docker's NAT). Forcing IPv4 explicitly removes the
    # ambiguity entirely. --tries=1 was also removed: BusyBox
    # wget doesn't support that flag at all (GNU wget does) —
    # --spider already exits immediately on failure without
    # retrying, so it added nothing besides a parse error.
