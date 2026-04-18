# COMANDOS
docker compose up -d --build
docker compose up -d

docker compose ps
docker compose logs -f

docker compose stop
docker compose start

docker compose down

# Testar Fluxo API
curl http://localhost:8081/health

curl -X POST http://localhost:8081/orders \
  -H "Content-Type: application/json" \
  -d '{"customer":"Paulo","product":"Notebook","quantity":33}'

curl http://localhost:8081/orders

curl http://localhost:8081/orders/1

docker compose exec postgres psql -U orders -d orders_db -c "SELECT * FROM orders;"

# Validar fila
docker compose stop worker

curl -X POST http://localhost:8081/orders \
  -H "Content-Type: application/json" \
  -d '{"customer":"Maria","product":"Mouse","quantity":2}'

docker compose exec redis redis-cli LLEN orders_queue
docker compose exec redis redis-cli LRANGE orders_queue 0 -1

docker compose start worker

docker compose logs -f worker

docker compose up -d --scale worker=10