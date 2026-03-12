# Base Image: PSQL
FROM postgres:16

# Tudo que estiver em /docker-entrypoint-initdb.d/ será executado na inicialização do PSQL somente na primeira vez.
COPY schema.sql /docker-entrypoint-initdb.d/01-schema.sql