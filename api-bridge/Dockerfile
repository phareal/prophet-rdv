FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM node:20-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production

RUN addgroup --system --gid 1001 nodejs \
 && adduser --system --uid 1001 bridge \
 && chown -R bridge:nodejs /app

COPY --from=builder --chown=bridge:nodejs /app/dist        ./dist
COPY --from=builder --chown=bridge:nodejs /app/node_modules ./node_modules
COPY --from=builder --chown=bridge:nodejs /app/package.json ./package.json

USER bridge
EXPOSE 4000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD wget -qO- http://localhost:4000/health || exit 1

CMD ["node", "dist/index.js"]
