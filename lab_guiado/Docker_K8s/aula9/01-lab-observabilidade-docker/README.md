# LAB — Observabilidade em Docker com Grafana, Loki, Alloy, Prometheus e cAdvisor

Este laboratório sobe uma stack completa em **Docker Compose** para demonstrar, na prática, dois pilares da observabilidade em containers:

- **Logs centralizados** com **Grafana Loki + Grafana Alloy**
- **Métricas dos containers** com **cAdvisor + Prometheus**

A ideia é permitir que você veja, no mesmo ambiente:

- Logs saindo dos containers;
- Logs chegando no Loki;
- Consultas no Grafana Explore;
- Criação de gráficos a partir de logs;
- Métricas de CPU, memória, rede, disco e processos dos containers.

## O que você aprenderá neste laboratório

Ao final do LAB, você será capaz de:

- Entender a diferença entre **logs** e **métricas**;
- Centralizar logs de containers Docker;
- Consultar logs no **Grafana** usando **Loki**;
- Transformar logs em gráficos simples;
- Coletar métricas de runtime com **cAdvisor**;
- Consultar métricas com **Prometheus**;
- Comparar visualmente os dados do Grafana com a ideia do `docker stats`.

## Visão geral da arquitetura

```text
Logs:
stdout/stderr dos containers
        -> Docker Engine
        -> Grafana Alloy
        -> Loki
        -> Grafana

Métricas:
Containers Docker
        -> cAdvisor
        -> Prometheus
        -> Grafana
```

### Leitura rápida do fluxo

- As aplicações de exemplo (`app-a` e `app-b`) escrevem logs em **stdout/stderr**.
- O **Alloy** lê esses logs a partir do Docker e envia para o **Loki**.
- O **Grafana** consulta o Loki para exibir e filtrar logs.
- O **cAdvisor** coleta métricas de runtime dos containers.
- O **Prometheus** faz o scrape dessas métricas.
- O **Grafana** consulta o Prometheus para montar gráficos e painéis.

## Componentes da stack

- **Grafana**: Interface visual para explorar logs e métricas, montar dashboards (visualização) e criar alertas.
- **Loki**: Armazenamento e consulta de logs.
- **Alloy**: Agente que coleta logs dos containers Docker.
- **Prometheus**: Coleta e consulta métricas às métricas do cAdvisor.
- **cAdvisor**: Expõe e armazena às métricas de uso dos containers.
- **app-a**: Aplicação de exemplo gerando logs informativos.
- **app-b**: Aplicação de exemplo gerando logs informativos e erros periódicos.

## Estrutura do projeto

```text
.
├── alloy
│   └── config.alloy
├── docker-compose.yml
├── grafana
│   └── provisioning
│       └── datasources
│           ├── datasource.yml
│           └── prometheus.yaml
├── loki-config.yaml
├── prometheus
│   └── prometheus.yml
└── README.md
```

## Papel de cada arquivo

- **docker-compose.yml**: Sobe toda a stack do laboratório.
- **alloy/config.alloy**: Configuração do Alloy para descoberta de containers Docker e envio dos logs para o Loki.
- **loki-config.yaml**: Configuração do Loki, incluindo armazenamento local e retenção.
- **prometheus/prometheus.yml**: Configuração de scrape do Prometheus.
- **grafana/provisioning/datasources/datasource.yml**: Cadastro automático do datasource Loki no Grafana.
- **grafana/provisioning/datasources/prometheus.yaml**: Cadastro automático do datasource Prometheus no Grafana.

## Pré-requisitos

Antes de iniciar, tenha no ambiente:

- Docker instalado;
- Docker Compose disponível via `docker compose`;
- Acesso ao terminal com permissão para executar Docker;
- Portas livres no host:
  - `3000` → Grafana
  - `3100` → Loki
  - `12345` → Alloy
  - `8080` → cAdvisor
  - `9090` → Prometheus

## Ajuste opcional do Docker para rotação de logs

Se quiser padronizar a rotação dos logs do host com `json-file`, você pode usar este arquivo:

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

Salvar em:

```text
/etc/docker/daemon.json
```

Depois reinicie o Docker:

```bash
sudo systemctl restart docker
docker info --format '{{.LoggingDriver}}'
```

> Importante: containers já existentes não herdam automaticamente essa mudança. Recrie os containers se quiser aplicar a nova configuração.

## Serviços que serão criados

Ao subir a stack, você terá os seguintes serviços:

- `grafana`
- `loki`
- `alloy`
- `prometheus`
- `cadvisor`
- `app-a`
- `app-b`

## Subindo o laboratório

No diretório do projeto, execute:

```bash
docker compose up -d
```

Se quiser reconstruir tudo do zero:

```bash
docker compose up -d --build
```

## Validando se os containers subiram

```bash
docker compose ps
docker ps -a
```

Se quiser acompanhar logs da stack:

```bash
docker compose logs -f
```

## URLs de acesso

Após subir o ambiente, acesse:

- **Grafana**: http://localhost:3000
- **Loki health**: http://localhost:3100/ready
- **Alloy UI**: http://localhost:12345/graph
- **cAdvisor**: http://localhost:8080
- **Prometheus**: http://localhost:9090

### Observação sobre o Grafana

Neste laboratório, o Grafana foi configurado com acesso anônimo habilitado. Isso facilita o uso em aula, sem exigir login manual. Porém não recomendado utilizar dessa forma em produção!!!

## Primeira validação: verificar logs no Docker

Antes de ir para o Grafana, valide se as aplicações estão gerando logs diretamente no runtime:

```bash
docker logs -f app-a
docker logs -f app-b
```

Você deve observar algo parecido com:

- `app-a`: mensagens de sucesso/atividade normal
- `app-b`: mensagens informativas e, periodicamente, mensagens de erro


## Segunda validação: verificar o Alloy

O Alloy é quem coleta os logs dos containers e encaminha para o Loki.

```bash
docker logs alloy --tail 50
```

Se tudo estiver correto, o Alloy deve estar rodando sem erros de sintaxe e sem falhas de conexão com o Loki.

## Consultando logs no Grafana

No Grafana:

1. Abra **Explore**
2. Escolha o datasource **Loki**
3. Execute as consultas abaixo

### Todos os logs do laboratório

```logql
{env="lab"}
```

### Apenas logs do `app-a`

```logql
{container="app-a"}
```

### Apenas logs do `app-b`

```logql
{container="app-b"}
```

### Apenas erros do `app-b`

```logql
{container="app-b"} |= "level=error"
```

### Parse de `logfmt`

```logql
{container="app-b"} | logfmt | level="error"
```

## Transformando logs em gráfico

O Loki também pode gerar séries temporais a partir de logs.

### Contagem de erros por minuto

```logql
count_over_time({container="app-b"} | logfmt | level="error"[1m])
```

### Erros por container

```logql
sum by (container) (
  count_over_time({container=~"app-a|app-b"} | logfmt | level="error"[1m])
)
```

### Sugestão de visualização no Grafana

- painel: **Time series**
- título: **Erros por minuto**
- exibição: **Lines** ou **Bars**

## Consultando métricas com Prometheus

Agora utilize o datasource **Prometheus** no Grafana Explore ou em painéis.

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

### Processos/tarefas por container

```promql
sum by (name) (
  container_tasks_state{name!="",state="running"}
)
```

## Painéis sugeridos

Você pode montar uma dashboard simples com estes painéis:

### 1. Erros por minuto
Fonte: **Loki**

```logql
count_over_time({container="app-b"} | logfmt | level="error"[1m])
```

### 2. CPU por container
Fonte: **Prometheus**

```promql
sum by (name) (
  rate(container_cpu_usage_seconds_total{name!=""}[1m])
) * 100
```

### 3. Memória por container
Fonte: **Prometheus**

```promql
sum by (name) (
  container_memory_working_set_bytes{name!=""}
)
```

### 4. Network RX/TX
Fonte: **Prometheus**

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

### 5. Block I/O
Fonte: **Prometheus**

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

## Como relacionar isso com `docker stats`

Este laboratório ajuda a perceber que o `docker stats` mostra uma visão instantânea do runtime, enquanto a stack de observabilidade permite:

- **Histórico** das métricas;
- **Gráficos** ao longo do tempo;
- Comparação entre múltiplos containers;
- Análise de logs e métricas no mesmo ambiente.

Ou seja: o `docker stats` ajuda no diagnóstico rápido, enquanto Grafana + Prometheus + Loki ajudam na análise contínua e visual, além da possibilidade de gerar alertas.

## Troubleshooting

### 1. Alloy caiu com erro de sintaxe

Revise o arquivo `alloy/config.alloy`, principalmente blocos e mapas.

Exemplo correto:

```alloy
values = {
  env = "lab",
}
```

### 2. Não aparecem logs no Grafana

Verifique:

```bash
docker logs alloy --tail 100
docker logs loki --tail 100
docker logs app-b --tail 20
```

Também confirme se a query do Loki está correta e se os containers estão realmente gerando logs.

### 3. Prometheus não coleta métricas

Abra:

```text
http://localhost:9090/targets
```

O alvo `cadvisor` deve aparecer como **UP**.

### 4. cAdvisor não sobe corretamente

Valide:

- os mounts do host;
- o acesso aos diretórios do Docker;
- o modo privilegiado;
- se a porta `8080` está livre.

### 5. Porta já está em uso

Se alguma porta já estiver ocupada no host, ajuste o mapeamento no `docker-compose.yml`.

## Derrubando o ambiente

```bash
docker compose down -v
```

O parâmetro `-v` remove também os volumes criados pela stack.

## Fluxo resumido do laboratório

```bash
# Subir a stack
docker compose up -d

# Validar containers
docker compose ps

# Ver logs das aplicações
docker logs -f app-a
docker logs -f app-b

# Ver logs do Alloy
docker logs alloy --tail 50

# Acessar interfaces
# Grafana:    http://localhost:3000
# Loki:       http://localhost:3100/ready
# Alloy:      http://localhost:12345/graph
# cAdvisor:   http://localhost:8080
# Prometheus: http://localhost:9090

# Derrubar tudo
docker compose down -v
```

## Conclusão

Este laboratório é uma forma simples e prática de apresentar observabilidade em containers Docker.

Com ele, você consegue enxergar que:

- Logs e métricas são sinais diferentes, mas complementares;
- Uma aplicação pode parecer “de pé”, mas ainda assim gerar erro em log;
- Métricas ajudam a entender comportamento ao longo do tempo;
- Grafana centraliza visualização e análise de diferentes fontes.