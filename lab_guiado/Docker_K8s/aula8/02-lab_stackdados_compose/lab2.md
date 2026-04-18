# COMANDOS
docker compose up -d --build
docker compose up -d
docker compose ps
docker compose logs -f

docker compose stop
docker compose start

docker compose down


JupyterLab: http://localhost:8888/lab?token=lab123
MinIO Console: http://localhost:9001
MinIO API: http://localhost:9000

# Criar sessão SPARK
from pyspark.sql import SparkSession

spark = SparkSession.builder \
    .master("local[*]") \
    .appName("lab-mysql-minio") \
    .getOrCreate()

spark.range(5).show()

# Ler do MySQL
import os
import pandas as pd
from sqlalchemy import create_engine

engine = create_engine(
    f"mysql+pymysql://{os.environ['MYSQL_USER']}:{os.environ['MYSQL_PASSWORD']}"
    f"@{os.environ['MYSQL_HOST']}:{os.environ['MYSQL_PORT']}/{os.environ['MYSQL_DATABASE']}"
)

df = pd.read_sql("SELECT * FROM pedidos", engine)
df.head()

# Salvar localmente em Parquet
output_file = "/home/jovyan/work/pedidos.parquet"
df.to_parquet(output_file, index=False)
output_file

# Criar bucket e enviar para o MinIO
import boto3
from botocore.exceptions import ClientError

s3 = boto3.client(
    "s3",
    endpoint_url=os.environ["S3_ENDPOINT"],
    aws_access_key_id=os.environ["AWS_ACCESS_KEY_ID"],
    aws_secret_access_key=os.environ["AWS_SECRET_ACCESS_KEY"],
)

bucket = "landing"

try:
    s3.head_bucket(Bucket=bucket)
except ClientError:
    s3.create_bucket(Bucket=bucket)

s3.upload_file("/home/jovyan/work/pedidos.parquet", bucket, "raw/pedidos.parquet")
print("Upload realizado com sucesso")

# Ler com Spark o arquivo gerado
spark_df = spark.read.parquet("/home/jovyan/work/pedidos.parquet")
spark_df.show()

# Ler do Minio
try:
    spark.stop()
except:
    pass

from pyspark.sql import SparkSession

spark = (
    SparkSession.builder
    .master("local[*]")
    .appName("lab-mysql-minio")
    .config("spark.hadoop.fs.s3a.endpoint", "http://minio:9000")
    .config("spark.hadoop.fs.s3a.access.key", "minioadmin")
    .config("spark.hadoop.fs.s3a.secret.key", "minioadmin123")
    .config("spark.hadoop.fs.s3a.path.style.access", "true")
    .config("spark.hadoop.fs.s3a.connection.ssl.enabled", "false")
    .config("spark.hadoop.fs.s3a.impl", "org.apache.hadoop.fs.s3a.S3AFileSystem")
    .getOrCreate()
)

spark_df = spark.read.parquet("s3a://landing/raw/pedidos.parquet")
spark_df.show()