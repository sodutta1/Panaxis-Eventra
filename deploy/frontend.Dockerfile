# Panaxis Eventra frontend (React + Vite -> static build on nginx)
FROM node:22-alpine AS build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci || npm install
COPY frontend/ ./
ARG VITE_API_URL
ARG VITE_DEMO_MODE=false
ENV VITE_API_URL=$VITE_API_URL VITE_DEMO_MODE=$VITE_DEMO_MODE
RUN npm run build

FROM nginx:1.27-alpine
COPY deploy/nginx-frontend.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
