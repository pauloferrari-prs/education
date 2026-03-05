# Base Image: Mínima + versão fixa (evita "latest")
FROM python:3.12.2-slim-bookworm

# LABEL/ENV (metadados + variáveis padrão)
LABEL org.opencontainers.image.title="python-calc-lab" \
      org.opencontainers.image.version="1.0.0" \
      org.opencontainers.image.description="Calculadora simples (adicao/subtracao) em Python com boas práticas de Dockerfile" \
      org.opencontainers.image.created="2026-03-05" \
      org.opencontainers.image.authors="Paulo Ferrari"

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    APP_ENV=prod \
    APP_DATA_DIR=/var/lib/app

# ARGs para usuário non-root
ARG APP_USER=appuser
ARG APP_UID=10001
ARG APP_GID=10001

# - Minimizar camadas e lixo:
# - Instala apenas o necessário
# - Limpa cache do apt na mesma layer
# - Non-root: cria usuário/grupo e ajusta permissões em caminhos previsíveis
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates; \
    rm -rf /var/lib/apt/lists/*; \
    groupadd -g "${APP_GID}" "${APP_USER}"; \
    useradd  -m -u "${APP_UID}" -g "${APP_GID}" -s /usr/sbin/nologin "${APP_USER}"; \
    mkdir -p /app /etc/app /var/lib/app; \
    chown -R "${APP_UID}:${APP_GID}" /app /etc/app /var/lib/app

# Paths previsíveis
WORKDIR /app

# Copia o manifesto primeiro (requirements.txt) depois instala as deps,
# depois copia o código (mudança no código NÃO invalida deps)
COPY requirements.txt requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# Cópia explícita do código da aplicação (evita vazar arquivos indevidos)
COPY --chown=${APP_UID}:${APP_GID} calc/ ./calc/

# Executar como non-root
USER ${APP_USER}

# Runtime saudável (exec form):
# ENTRYPOINT define o processo principal (PID 1)
ENTRYPOINT ["python", "-m", "calc"]

# Default (pode sobrescrever no docker run)
CMD ["--help"]