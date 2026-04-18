# LAB - Observabilidade em Docker com Grafana, Loki, Alloy, Prometheus e cAdvisor

Este LAB sobe uma stack simples em **Docker Compose** para demonstrar dois pilares de observabilidade em containers Docker:

- **Logs** centralizados com **Grafana Loki + Grafana Alloy**
- **Métricas de runtime** dos containers com **cAdvisor + Prometheus**

A proposta é reproduzir em aula:

- consulta de logs por container
- filtro de erros
- geração de gráficos de erro no Grafana
- visualização de métricas parecidas com o `docker stats`
  - CPU
  - memória
  - network I/O
  - block I/O
  - PIDs/processos

---

## Arquitetura

```text
Logs:
stdout/stderr dos containers -> Docker -> Alloy -> Loki -> Grafana

Métricas:
Containers Docker -> cAdvisor -> Prometheus -> Grafana
```

---

## Componentes da stack

- **Grafana**: visualização de logs e métricas
- **Loki**: armazenamento e consulta de logs
- **Alloy**: coleta logs direto do Docker via `docker.sock`
- **Prometheus**: coleta e consulta métricas do cAdvisor
- **cAdvisor**: expõe métricas de runtime dos containers
- **app-a**: app de exemplo gerando logs informativos
- **app-b**: app de exemplo gerando logs informativos e erros periódicos

---

## Pré-requisitos

- Docker Engine
- Docker Compose plugin (`docker compose`)
- Linux com acesso ao socket Docker local

---

## Estrutura sugerida do projeto

```text
01-lab-observabilidade-docker/
├── alloy/
│   └── config.alloy
├── grafana/
│   └── provisioning/
│       └── datasources/
│           ├── datasource.yml
│           └── prometheus.yml
├── prometheus/
│   └── prometheus.yml
├── docker-compose.yml
├── loki-config.yaml
└── README.md
```

---

## 1) Ajuste opcional do Docker daemon

Se quiser deixar o host usando rotação padrão de logs com `json-file`, utilize:

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

Arquivo:

```text
/etc/docker/daemon.json
```

Depois reinicie o Docker:

```bash
sudo systemctl restart docker
docker info --format '{{.LoggingDriver}}'
```

> Importante: containers já existentes não herdam essa mudança. Recrie os containers depois.

---

## 2) docker-compose.yml - visão geral

A stack completa terá os seguintes serviços:

- `grafana`
- `loki`
- `alloy`
- `prometheus`
- `cadvisor`
- `app-a`
- `app-b`

---

## 3) Ajuste dos containers de app

Para evitar warning do Docker Compose com variável shell, use `$$i` dentro do `command`:

```yaml
app-a:
  image: alpine:3.20
  container_name: app-a
  command:
    - /bin/sh
    - -c
    - |
      i=0
      while true; do
        i=$((i+1))
        echo "$(date -Iseconds) level=info app=app-a msg=pedido_processado seq=$$i"
        sleep 2
      done
  labels:
    aula: observabilidade-docker
    app: app-a
  logging:
    driver: json-file
    options:
      max-size: "10m"
      max-file: "3"
  networks:
    - observability

app-b:
  image: alpine:3.20
  container_name: app-b
  command:
    - /bin/sh
    - -c
    - |
      i=0
      while true; do
        i=$((i+1))
        if [ $((i % 5)) -eq 0 ]; then
          echo "$(date -Iseconds) level=error app=app-b msg=falha_banco seq=$$i"
        else
          echo "$(date -Iseconds) level=info app=app-b msg=heartbeat_ok seq=$$i"
        fi
        sleep 3
      done
  labels:
    aula: observabilidade-docker
    app: app-b
  logging:
    driver: json-file
    options:
      max-size: "10m"
      max-file: "3"
  networks:
    - observability
```

---

## 4) Configuração do Alloy

Arquivo:

```text
alloy/config.alloy
```

Conteúdo:

```alloy
discovery.docker "containers" {
  host             = "unix:///var/run/docker.sock"
  refresh_interval = "5s"
}

discovery.relabel "containers" {
  targets = []

  rule {
    source_labels = ["__meta_docker_container_name"]
    regex         = "/(.*)"
    target_label  = "container"
  }
}

loki.source.docker "containers" {
  host             = "unix:///var/run/docker.sock"
  targets          = discovery.docker.containers.targets
  relabel_rules    = discovery.relabel.containers.rules
  forward_to       = [loki.process.logs.receiver]
  refresh_interval = "5s"
}

loki.process "logs" {
  stage.static_labels {
    values = {
      env = "lab",
    }
  }

  forward_to = [loki.write.local.receiver]
}

loki.write "local" {
  endpoint {
    url = "http://loki:3100/loki/api/v1/push"
  }
}

livedebugging {
  enabled = true
}
```

> Atenção: no bloco `values`, precisa vírgula após os campos.

---

## 5) Configuração do Prometheus

Crie o arquivo:

```text
prometheus/prometheus.yml
```

Conteúdo:

```yaml
global:
  scrape_interval: 5s

scrape_configs:
  - job_name: cadvisor
    scrape_interval: 5s
    static_configs:
      - targets: ["cadvisor:8080"]
```

---

## 6) Services do cAdvisor e Prometheus

Adicione ao `docker-compose.yml`:

```yaml
cadvisor:
  image: ghcr.io/google/cadvisor:latest
  container_name: cadvisor
  privileged: true
  devices:
    - /dev/kmsg:/dev/kmsg
  volumes:
    - /:/rootfs:ro
    - /var/run:/var/run:rw
    - /sys:/sys:ro
    - /var/lib/docker:/var/lib/docker:ro
    - /dev/disk:/dev/disk:ro
  ports:
    - "8080:8080"
  restart: unless-stopped
  networks:
    - observability

prometheus:
  image: prom/prometheus:latest
  container_name: prometheus
  command:
    - --config.file=/etc/prometheus/prometheus.yml
  volumes:
    - ./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
  ports:
    - "9090:9090"
  restart: unless-stopped
  depends_on:
    - cadvisor
  networks:
    - observability
```

> Esses mounts são bind mounts. Não precisam ser declarados no bloco final `volumes:` do compose.

---

## 7) Provisionando datasource Prometheus no Grafana

Se quiser subir já pronto, crie também:

```text
grafana/provisioning/datasources/prometheus.yml
```

Conteúdo:

```yaml
apiVersion: 1

datasources:
  - name: Prometheus
    type: prometheus
    access: proxy
    url: http://prometheus:9090
    isDefault: false
    editable: true
```

Se você já tem o `datasource.yml` do Loki, a pasta de provisioning pode ficar com os dois arquivos:

- `datasource.yml` para Loki
- `prometheus.yml` para Prometheus

---

## 8) Exemplo de compose completo

Abaixo, apenas a ideia dos serviços principais. Ajuste conforme seu arquivo atual:

```yaml
services:
  loki:
    image: grafana/loki:3.5
    container_name: loki
    command: -config.file=/etc/loki/config.yaml
    ports:
      - "3100:3100"
    volumes:
      - ./loki-config.yaml:/etc/loki/config.yaml:ro
      - loki-data:/loki
    networks:
      - observability

  grafana:
    image: grafana/grafana:12.4
    container_name: grafana
    ports:
      - "3000:3000"
    environment:
      GF_AUTH_ANONYMOUS_ENABLED: "true"
      GF_AUTH_ANONYMOUS_ORG_ROLE: Admin
      GF_AUTH_DISABLE_LOGIN_FORM: "true"
    volumes:
      - grafana-data:/var/lib/grafana
      - ./grafana/provisioning:/etc/grafana/provisioning:ro
    depends_on:
      - loki
      - prometheus
    networks:
      - observability

  alloy:
    image: grafana/alloy:latest
    container_name: alloy
    user: "0:0"
    command: run --server.http.listen-addr=0.0.0.0:12345 --storage.path=/var/lib/alloy/data /etc/alloy/config.alloy
    ports:
      - "12345:12345"
    volumes:
      - ./alloy/config.alloy:/etc/alloy/config.alloy:ro
      - /var/run/docker.sock:/var/run/docker.sock
      - alloy-data:/var/lib/alloy/data
    depends_on:
      - loki
    restart: unless-stopped
    networks:
      - observability

  cadvisor:
    image: ghcr.io/google/cadvisor:latest
    container_name: cadvisor
    privileged: true
    devices:
      - /dev/kmsg:/dev/kmsg
    volumes:
      - /:/rootfs:ro
      - /var/run:/var/run:rw
      - /sys:/sys:ro
      - /var/lib/docker:/var/lib/docker:ro
      - /dev/disk:/dev/disk:ro
    ports:
      - "8080:8080"
    restart: unless-stopped
    networks:
      - observability

  prometheus:
    image: prom/prometheus:latest
    container_name: prometheus
    command:
      - --config.file=/etc/prometheus/prometheus.yml
    volumes:
      - ./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
    ports:
      - "9090:9090"
    restart: unless-stopped
    depends_on:
      - cadvisor
    networks:
      - observability

  app-a:
    image: alpine:3.20
    container_name: app-a
    command:
      - /bin/sh
      - -c
      - |
        i=0
        while true; do
          i=$((i+1))
          echo "$(date -Iseconds) level=info app=app-a msg=pedido_processado seq=$$i"
          sleep 2
        done
    labels:
      aula: observabilidade-docker
      app: app-a
    logging:
      driver: json-file
      options:
        max-size: "10m"
        max-file: "3"
    networks:
      - observability

  app-b:
    image: alpine:3.20
    container_name: app-b
    command:
      - /bin/sh
      - -c
      - |
        i=0
        while true; do
          i=$((i+1))
          if [ $((i % 5)) -eq 0 ]; then
            echo "$(date -Iseconds) level=error app=app-b msg=falha_banco seq=$$i"
          else
            echo "$(date -Iseconds) level=info app=app-b msg=heartbeat_ok seq=$$i"
          fi
          sleep 3
        done
    labels:
      aula: observabilidade-docker
      app: app-b
    logging:
      driver: json-file
      options:
        max-size: "10m"
        max-file: "3"
    networks:
      - observability

networks:
  observability:

volumes:
  loki-data:
  grafana-data:
  alloy-data:
```

---

## 9) Subir a stack

No diretório do projeto:

```bash
docker compose up -d
```

Validar:

```bash
docker compose ps
docker ps -a
```

---

## 10) URLs de acesso

- Grafana: http://localhost:3000
- Loki health: http://localhost:3100/ready
- Alloy UI: http://localhost:12345/graph
- cAdvisor: http://localhost:8080
- Prometheus: http://localhost:9090

---

## 11) Validação rápida dos logs

Ver logs direto no Docker:

```bash
docker logs -f app-a
docker logs -f app-b
```

Ver se o Alloy está saudável:

```bash
docker logs alloy --tail 50
```

---

## 12) Queries de logs no Grafana (Loki)

No Grafana:

1. Abra **Explore**
2. Escolha o datasource **Loki**
3. Teste as queries abaixo

### Todos os logs do LAB

```logql
{env="lab"}
```

### Apenas logs do app-a

```logql
{container="app-a"}
```

### Apenas logs do app-b

```logql
{container="app-b"}
```

### Apenas erros do app-b

```logql
{container="app-b"} |= "level=error"
```

### Parse de logfmt

```logql
{container="app-b"} | logfmt | level="error"
```

### Monta gŕafico contagem erro por minuto

```logql
count_over_time({container="app-b"} |= "level=error"[1m])
```

---

## 13) Criando gráfico de erros no Grafana

Para transformar logs em métrica, use **metric query** do Loki.

### Erros por minuto do app-b

```logql
count_over_time({container="app-b"} | logfmt | level="error"[1m])
```

### Erros por container

```logql
sum by (container) (
  count_over_time({container=~"app-a|app-b"} | logfmt | level="error"[1m])
)
```

Sugestão de visualização:

- painel **Time series**
- título: `Erros por minuto`
- exibição em **Bars** ou **Lines**

---

## 14) Métricas tipo docker stats no Grafana

Agora use o datasource **Prometheus**.

No Explore ou em painéis, use consultas PromQL.

### CPU por container

```promql
sum by (name) (
  rate(container_cpu_usage_seconds_total{name!=""}[1m])
) * 100
```

### Memória usada por container

```promql
sum by (name) (
  container_memory_working_set_bytes{name!=""}
)
```

### Percentual de memória por container

```promql
100 *
sum by (name) (container_memory_working_set_bytes{name!=""})
/
sum by (name) (container_spec_memory_limit_bytes{name!=""})
```

### Network RX por container

```promql
sum by (name) (
  rate(container_network_receive_bytes_total{name!=""}[1m])
)
```

### Network TX por container

```promql
sum by (name) (
  rate(container_network_transmit_bytes_total{name!=""}[1m])
)
```

### Block read por container

```promql
sum by (name) (
  rate(container_fs_reads_bytes_total{name!=""}[1m])
)
```

### Block write por container

```promql
sum by (name) (
  rate(container_fs_writes_bytes_total{name!=""}[1m])
)
```

### PIDs/processos por container

```promql
sum by (name) (
  container_tasks_state{name!="",state="running"}
)
```

> Dependendo da versão do cAdvisor e do ambiente, alguns labels podem variar. Em alguns cenários você pode preferir `container_label_com_docker_compose_service` em vez de `name`.

---

## 15) Painéis sugeridos para a dashboard

Sugestão de dashboard para a aula:

### Painel 1 - Logs de erro
- fonte: Loki
- query:

```logql
count_over_time({container="app-b"} | logfmt | level="error"[1m])
```

### Painel 2 - CPU por container
- fonte: Prometheus
- query:

```promql
sum by (name) (
  rate(container_cpu_usage_seconds_total{name!=""}[1m])
) * 100
```

### Painel 3 - Memória por container
- fonte: Prometheus
- query:

```promql
sum by (name) (
  container_memory_working_set_bytes{name!=""}
)
```

### Painel 4 - Network RX/TX
- fonte: Prometheus
- queries:

```promql
sum by (name) (
  rate(container_network_receive_bytes_total{name!=""}[1m])
)
```

```promql
sum by (name) (
  rate(container_network_transmit_bytes_total{name!=""}[1m])
)
```

### Painel 5 - Block I/O
- fonte: Prometheus
- queries:

```promql
sum by (name) (
  rate(container_fs_reads_bytes_total{name!=""}[1m])
)
```

```promql
sum by (name) (
  rate(container_fs_writes_bytes_total{name!=""}[1m])
)
```

---











## 16) Roteiro rápido de demonstração em aula

### Parte 1 - Logs
1. mostrar `docker logs app-a` e `docker logs app-b`
2. explicar `stdout/stderr`
3. abrir Grafana Explore com Loki
4. filtrar `app-b`
5. mostrar só erros
6. criar gráfico com `count_over_time`

### Parte 2 - Métricas
1. abrir cAdvisor
2. abrir Prometheus targets
3. abrir Grafana com datasource Prometheus
4. mostrar CPU, memória, rede e disco
5. comparar com `docker stats`

### Parte 3 - Fechamento
1. logs = eventos/texto
2. métricas = comportamento numérico ao longo do tempo
3. juntos, dão visão prática de troubleshooting

---

## 17) Troubleshooting

### Alloy caiu com erro de sintaxe
Verifique o `config.alloy`, especialmente vírgulas em maps:

```alloy
values = {
  env = "lab",
}
```

### Warning sobre variável `i` no compose
Troque `$i` por `$$i` dentro do `command`.

### Não aparecem logs no Grafana
Verifique:

```bash
docker logs alloy --tail 100
docker logs loki --tail 100
docker logs app-b --tail 20
```

### Prometheus não coleta
Abra:

```text
http://localhost:9090/targets
```

O target `cadvisor` deve estar `UP`.

### cAdvisor não sobe corretamente
Valide os mounts e o modo privilegiado.

---

## 18) Derrubar o ambiente

```bash
docker compose down -v
```

---

## 19) Resumo do que o aluno aprende

Com este LAB, o aluno consegue praticar:

- geração de logs em containers
- centralização de logs com Loki
- uso do Grafana Explore
- criação de gráfico baseado em logs
- coleta de métricas com cAdvisor
- consulta com Prometheus
- visualização de CPU, memória, rede e disco no Grafana
- comparação prática com `docker stats`

