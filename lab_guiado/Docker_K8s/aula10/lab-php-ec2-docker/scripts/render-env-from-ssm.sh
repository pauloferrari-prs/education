#!/usr/bin/env bash
set -euo pipefail

APP_ENV_FILE="${1:-.env.runtime}"
PARAM_PREFIX="${2:-/labphp/prod}"
AWS_REGION="${AWS_REGION:-us-east-1}"

required_cmds=(aws)
for cmd in "${required_cmds[@]}"; do
  command -v "$cmd" >/dev/null 2>&1 || { echo "Comando ausente: $cmd"; exit 1; }
done

fetch_parameter() {
  local name="$1"
  aws ssm get-parameter \
    --region "$AWS_REGION" \
    --name "$name" \
    --with-decryption \
    --query 'Parameter.Value' \
    --output text
}

cat > "$APP_ENV_FILE" <<ENVEOF
APP_IMAGE=$(fetch_parameter "$PARAM_PREFIX/APP_IMAGE")
APP_NAME=$(fetch_parameter "$PARAM_PREFIX/APP_NAME")
APP_URL=$(fetch_parameter "$PARAM_PREFIX/APP_URL")
APP_SESSION_NAME=$(fetch_parameter "$PARAM_PREFIX/APP_SESSION_NAME")
DB_HOST=$(fetch_parameter "$PARAM_PREFIX/DB_HOST")
DB_PORT=$(fetch_parameter "$PARAM_PREFIX/DB_PORT")
DB_NAME=$(fetch_parameter "$PARAM_PREFIX/DB_NAME")
DB_USER=$(fetch_parameter "$PARAM_PREFIX/DB_USER")
DB_PASSWORD=$(fetch_parameter "$PARAM_PREFIX/DB_PASSWORD")
ENVEOF

echo "Arquivo ${APP_ENV_FILE} gerado com sucesso usando SSM Parameter Store."
