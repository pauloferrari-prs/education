# Imagem base que utilizaremos do nginx
FROM nginx:1.28.2-alpine-slim

# Parâmetros do build (ex: usuário/uid/gid), similar ao ENV, porém só existe durante a criação da imagem
ARG APP_USER=appuser
ARG APP_UID=10001
ARG APP_GID=10001

# usuário non-root
RUN addgroup -S -g ${APP_GID} ${APP_USER} \
 && adduser  -S -D -H -u ${APP_UID} -G ${APP_USER} ${APP_USER} \
 && mkdir -p /var/cache/nginx /tmp/nginx \
           /tmp/client_temp /tmp/proxy_temp /tmp/fastcgi_temp /tmp/uwsgi_temp /tmp/scgi_temp \
           /run /var/run \
 && chown -R ${APP_USER}:${APP_USER} /usr/share/nginx/html /var/cache/nginx /tmp /run /var/run

# Diretório de trabalho (onde o COPY vai adicionar os arquivos)
WORKDIR /usr/share/nginx/html

# app dentro da imagem (imutável até rebuild)
COPY --chown=${APP_USER}:${APP_USER} app/index.html .

# configs do nginx
COPY --chown=${APP_USER}:${APP_USER} nginx.conf /etc/nginx/nginx.conf
COPY --chown=${APP_USER}:${APP_USER} default.conf /etc/nginx/conf.d/default.conf

# Documenta a porta
EXPOSE 8080

# Troca para o nosso usuário appuser (non-root)
USER ${APP_USER}

# Processo principal (nginx) + args default
ENTRYPOINT ["nginx"]
CMD ["-g", "daemon off;"]