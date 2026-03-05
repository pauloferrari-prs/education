FROM mysql:8.0.36

LABEL org.opencontainers.image.title="mysql-volume-lab" \
      org.opencontainers.image.version="1.0.0"

# Script roda somente no 1º start (quando /var/lib/mysql está vazio)
COPY init/ /docker-entrypoint-initdb.d/