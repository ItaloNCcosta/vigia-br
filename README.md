# Vigia BR 🇧🇷

Aplicação **Laravel 12** que consome a [API de Dados Abertos da Câmara dos Deputados](https://dadosabertos.camara.leg.br/) para sincronizar e exibir informações de gastos dos deputados federais do Brasil.
O objetivo é oferecer uma ferramenta simples e rápida para consulta de despesas parlamentares, com processamento assíncrono e agendamento de sincronizações automáticas.

---

## 📦 Tecnologias

* **Laravel 12**
* **MySQL**
* **Redis** (filas e cache)
* **Laravel Horizon** (monitoramento de filas)
* **Docker & Docker Compose**

---

## 🔧 Pré-requisitos

* Docker (>= 20.10)
* Docker Compose (>= 1.27)

---

## 🚀 Passo a passo rápido

1. **Clone o repositório**

   ```bash
   git clone git@github.com:ItaloNCcosta/vigia-br.git
   cd vigia-br
   ```

2. **Copie o `.env` e ajuste variáveis**

   ```bash
   cp .env.example .env
   ```

3. **Suba os containers**

   ```bash
   docker compose up -d --build
   ```

4. **Instale dependências e prepare a aplicação**

   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --force
   ```

5. **Acesse**

   * Web: [http://127.0.0.1:8080](http://127.0.0.1:8080)
   * Horizon: [http://127.0.0.1:8080/horizon](http://127.0.0.1:8080/horizon)

---

## 📚 Documentação detalhada

* [🏛 Arquitetura do Projeto](docs/architecture.md)
* [📦 Docker Setup](docs/docker.md)
* [⚡ Jobs, Workers e Horizon](docs/jobs-and-workers.md)

---

## ⚙️ Comandos úteis

```bash
# Rodar migrations novamente
docker compose exec app php artisan migrate --force

# Logs dos containers
docker compose logs -f app
docker compose logs -f horizon
docker compose logs -f scheduler

# Parar e remover tudo
docker compose down
```

---

## 📝 Observações

* A aplicação consome e armazena localmente dados de gastos de deputados federais para consultas rápidas.
* Sincronizações ocorrem em segundo plano através de **jobs** agendados e filas Redis.
* **Horizon** oferece painel gráfico para monitorar workers e status das filas.
* Estrutura pronta para desenvolvimento local com Docker e fácil de implantar em produção.
