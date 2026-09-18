#!/usr/bin/env bash
set -euo pipefail

docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp core install \
    --url="http://localhost:8080" \
    --title="Prophète Jeremiah Nahoum" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.test \
    --skip-email

docker compose -f docker-compose.cms.dev.yml exec -T wpcli \
  wp rewrite structure '/%postname%/'
