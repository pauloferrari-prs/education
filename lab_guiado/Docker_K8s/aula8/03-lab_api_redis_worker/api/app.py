import os
import redis
import psycopg2

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from psycopg2.extras import RealDictCursor

POSTGRES_HOST = os.getenv("POSTGRES_HOST", "postgres")
POSTGRES_PORT = int(os.getenv("POSTGRES_PORT", "5432"))
POSTGRES_DB = os.getenv("POSTGRES_DB", "orders_db")
POSTGRES_USER = os.getenv("POSTGRES_USER", "orders")
POSTGRES_PASSWORD = os.getenv("POSTGRES_PASSWORD", "orders123")

REDIS_HOST = os.getenv("REDIS_HOST", "redis")
REDIS_PORT = int(os.getenv("REDIS_PORT", "6379"))
QUEUE_NAME = os.getenv("QUEUE_NAME", "orders_queue")

app = FastAPI(title="Pedidos API")


class OrderIn(BaseModel):
    customer: str = Field(..., min_length=2)
    product: str = Field(..., min_length=2)
    quantity: int = Field(..., gt=0)


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


@app.get("/health")
def health():
    try:
        with get_db_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT 1")

        redis_client = get_redis_client()
        redis_client.ping()

        return {"status": "ok"}
    except Exception as exc:
        raise HTTPException(status_code=503, detail=str(exc))


@app.post("/orders", status_code=201)
def create_order(order: OrderIn):
    try:
        with get_db_connection() as conn:
            with conn.cursor(cursor_factory=RealDictCursor) as cur:
                cur.execute(
                    """
                    INSERT INTO orders (customer, product, quantity, status)
                    VALUES (%s, %s, %s, 'RECEIVED')
                    RETURNING id, customer, product, quantity, status, created_at
                    """,
                    (order.customer, order.product, order.quantity),
                )
                created_order = dict(cur.fetchone())

        redis_client = get_redis_client()
        redis_client.lpush(QUEUE_NAME, created_order["id"])

        return {
            "message": "Pedido recebido e enviado para fila",
            "order": created_order,
        }
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@app.get("/orders")
def list_orders():
    try:
        with get_db_connection() as conn:
            with conn.cursor(cursor_factory=RealDictCursor) as cur:
                cur.execute(
                    """
                    SELECT id, customer, product, quantity, status, created_at
                    FROM orders
                    ORDER BY id
                    """
                )
                rows = [dict(row) for row in cur.fetchall()]

        return rows
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))


@app.get("/orders/{order_id}")
def get_order(order_id: int):
    try:
        with get_db_connection() as conn:
            with conn.cursor(cursor_factory=RealDictCursor) as cur:
                cur.execute(
                    """
                    SELECT id, customer, product, quantity, status, created_at
                    FROM orders
                    WHERE id = %s
                    """,
                    (order_id,),
                )
                row = cur.fetchone()

        if not row:
            raise HTTPException(status_code=404, detail="Pedido não encontrado")

        return dict(row)
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))