# ============================================
# Nginx para Laravel
# ============================================
FROM nginx:alpine

RUN rm /etc/nginx/conf.d/default.conf

COPY default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

EXPOSE 80
