# Aula 1 — Instalar Docker + Hello World (Ubuntu 24.04 LTS)

Este guia traz o **passo a passo completo** para instalar o **Docker Engine** no **Ubuntu 24.04 (Noble)**, validar a instalação e rodar o container **hello-world**.

**Documentação base (oficial):**
- https://docs.docker.com/engine/install/ubuntu/
- https://docs.docker.com/engine/install/linux-postinstall/

---

## 0) Pré-requisitos

- Ubuntu **24.04 LTS (Noble)** 64-bit
- Acesso `sudo`
- Internet para baixar pacotes do repositório oficial da Docker

> Observação (rede/firewall):
> - Se você usa `ufw` ou `firewalld`, saiba que ao expor portas (`-p`) o Docker pode **bypassar** regras do firewall.
> - Docker funciona com `iptables-nft` e `iptables-legacy`; regras criadas apenas com `nft` podem não ser suportadas.

---

## 1) (Recomendado) Remover pacotes conflitantes (se existirem)

Algumas distribuições podem ter pacotes “não oficiais” que conflitam com o repositório oficial da Docker.

```bash
sudo apt remove $(dpkg --get-selections docker.io docker-compose docker-compose-v2 docker-doc podman-docker containerd runc | cut -f1)

## 2) Instalar Dependências + Repo Oficial Docker


```bash
sudo apt update
sudo apt install ca-certificates curl

sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update

## 3) Instalar Docker Engine + CLI + plugins (Buildx e Compose)

```bash
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

## 4) Validar se o Docker está rodando

```bash
sudo systemctl status docker
sudo systemctl start docker

## 5) Check — Ver versão do Docker

```bash
docker --version
docker version

## 6) Check — Ver versão do Docker Compose

```bash
docker compose version

## 7) Hello World — Rodar o container de teste

```bash
sudo docker run hello-world

## 6) Hello World — Rodar o container de teste

```bash
sudo docker run hello-world


--- OPCIONAL (RECOMENDADO) ---

## 1) Rodar Docker sem SUDO (Super User)
```bash
sudo groupadd docker
sudo usermod -aG docker $USER
newgrp docker

## 2) Testar comandos docker sem SUDO
docker run hello-world
docker ps -a