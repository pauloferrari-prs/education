# COMANDOS
docker compose up -d --build
docker compose up -d
docker compose ps
docker compose logs -f
docker compose down



docker run -d \
  --name mysql-agendav2 \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=agenda_db \
  -e MYSQL_USER=agenda \
  -e MYSQL_PASSWORD=agenda123 \
  -v agenda-mysql-data_v2:/var/lib/mysql \
  mysql-agenda-custom:1.0

docker inspect mysql-agendav2

getent host
nc -vz db 5432
curl http://web:80

docker network inspect