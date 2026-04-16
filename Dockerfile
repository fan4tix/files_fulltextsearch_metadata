# syntax=docker/dockerfile:1
ARG NEXTCLOUD_DEV_IMAGE=ghcr.io/juliusknorr/nextcloud-dev-php82:latest
FROM ${NEXTCLOUD_DEV_IMAGE}

# Keep app ID in one place for easier reuse in scripts/commands.
ARG APP_ID=files_fulltextsearch_metadata
ENV APP_ID=${APP_ID}

USER root

# Copy this app into an app path scanned by this nextcloud-dev image.
COPY . /var/www/html/apps-writable/${APP_ID}

# Ensure web server user can read/write app files in dev environments.
RUN chown -R www-data:www-data /var/www/html/apps-writable/${APP_ID}

# Keep the base image entrypoint/cmd from nextcloud-dev.
