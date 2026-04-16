cat /etc/docker/daemon.json

sudo systemctl restart docker

- Grafana: http://localhost:3000
- Alloy UI: http://localhost:12345/graph
- cAdvisor: http://localhost:8080
- Prometheus: http://localhost:9090

docker logs -f app-a
docker logs -f app-b

## Todos os logs do LAB

```logql
{env="lab"}
```

### Apenas logs do app-a

```logql
{container="app-a"}
```

### Apenas erros do app-b

```logql
{container="app-b"} |= "level=error"
```

### Montar gŕafico contagem erro por minuto

```logql
count_over_time({container="app-b"} |= "level=error"[1m])
```

```logql
sum by (container) (
  count_over_time({container=~"app-a|app-b"} | logfmt | level="error"[1m])
)
```

### CPU por container

```promql
sum by (name) (
  irate(container_cpu_usage_seconds_total{name!=""}[1m])
) * 100
```

### Memória usada por container

```promql
sum by (name) (
  container_memory_working_set_bytes{name!="",image!=""}
) / 1024 / 1024
```

### Percentual de memória por container

```promql
(
  100 *
  max by (name) (
    container_memory_usage_bytes{name!=""}
    - container_memory_total_inactive_file_bytes{name!=""}
  )
  /
  on(name)
  max by (name) (
    container_spec_memory_limit_bytes{name!=""} > 0
  )
)
or
(
  100 *
  max by (name) (
    container_memory_usage_bytes{name!=""}
    - container_memory_total_inactive_file_bytes{name!=""}
  )
  /
  scalar(machine_memory_bytes)
  unless
  on(name)
  max by (name) (
    container_spec_memory_limit_bytes{name!=""} > 0
  )
)
```