import os
import time
import redis
import psycopg2

POSTGRES_HOST = os.getenv("POSTGRES_HOST", "postgres")
POSTGRES_PORT = int(os.getenv("POSTGRES_PORT", "5432"))
POSTGRES_DB = os.getenv("POSTGRES_DB", "orders_db")
POSTGRES_USER = os.getenv("POSTGRES_USER", "orders")
POSTGRES_PASSWORD = os.getenv("POSTGRES_PASSWORD", "orders123")

REDIS_HOST = os.getenv("REDIS_HOST", "redis")
REDIS_PORT = int(os.getenv("REDIS_PORT", "6379"))
QUEUE_NAME = os.getenv("QUEUE_NAME", "orders_queue")


def get_db_connection():
    return psycopg2.connect(
        host=POSTGRES_HOST,
        port=POSTGRES_PORT,
        dbname=POSTGRES_DB,
        user=POSTGRES_USER,
        password=POSTGRES_PASSWORD,
    )


def get_redis_client():
    return redis.Redis(
        host=REDIS_HOST,
        port=REDIS_PORT,
        decode_responses=True
    )


def wait_for_dependencies():
    while True:
        try:
            redis_client = get_redis_client()
            redis_client.ping()

            with get_db_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("SELECT 1")

            print("Redis e Postgres prontos. Worker iniciado.", flush=True)
            return
        except Exception as exc:
            print(f"Aguardando dependências: {exc}", flush=True)
            time.sleep(3)


def update_status(order_id: int, status: str):
    with get_db_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "UPDATE orders SET status = %s WHERE id = %s",
                (status, order_id)
            )


def process_order(order_id: int):
    print(f"Pedido {order_id}: iniciando processamento", flush=True)
    update_status(order_id, "PROCESSING")
    time.sleep(5)
    update_status(order_id, "PROCESSED")
    print(f"Pedido {order_id}: processamento concluído", flush=True)


def main():
    wait_for_dependencies()
    redis_client = get_redis_client()

    while True:
        print("Worker aguardando pedidos na fila...", flush=True)
        _, order_id = redis_client.brpop(QUEUE_NAME)
        process_order(int(order_id))


if __name__ == "__main__":
    main()