FROM quay.io/jupyter/pyspark-notebook

USER root

RUN pip install --no-cache-dir pandas sqlalchemy pymysql boto3 s3fs pyarrow

RUN apt-get update && \
    apt-get install -y --no-install-recommends curl ca-certificates && \
    rm -rf /var/lib/apt/lists/*

RUN curl -fL -o /usr/local/spark/jars/hadoop-aws-3.4.2.jar \
    https://repo1.maven.org/maven2/org/apache/hadoop/hadoop-aws/3.4.2/hadoop-aws-3.4.2.jar && \
    curl -fL -o /usr/local/spark/jars/bundle-2.29.52.jar \
    https://repo1.maven.org/maven2/software/amazon/awssdk/bundle/2.29.52/bundle-2.29.52.jar && \
    curl -fL -o /usr/local/spark/jars/analyticsaccelerator-s3-1.2.1.jar \
    https://repo1.maven.org/maven2/software/amazon/s3/analyticsaccelerator/analyticsaccelerator-s3/1.2.1/analyticsaccelerator-s3-1.2.1.jar

USER ${NB_UID}