# Base Image: mysql
FROM mysql:8.0

# Tudo que estiver em /docker-entrypoint-initdb.d/ será executado na inicialização do MySQL somente na primeira vez.
COPY schema.sql /docker-entrypoint-initdb.d/01-schema.sql