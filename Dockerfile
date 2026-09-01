# Stage 1: Build frontend assets
FROM node:22 AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install -g npm@latest && npm install

COPY resources ./resources
COPY vite.config.js ./

# Build frontend assets
RUN npm run build
