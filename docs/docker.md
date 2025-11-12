# 📦 Docker Setup

Este projeto usa **Docker** para simplificar o ambiente de desenvolvimento, mantendo tudo padronizado e isolado.  
Com um único comando você sobe toda a stack Laravel + MySQL + Redis + Horizon + Scheduler + Nginx.

---

### 🐳 Estrutura de Containers

| Serviço    | Função                                                        | Porta Exposta |
|------------|---------------------------------------------------------------|---------------|
| **app**    | Container principal do Laravel rodando PHP-FPM                 | —             |
| **nginx**  | Servidor web que encaminha requisições para o PHP-FPM          | 8080 → 80     |
| **mysql**  | Banco de dados MySQL                                          | 3306          |
| **redis**  | Cache e broker de filas (jobs)                                | 6379          |
| **horizon**| Processamento e monitoramento de filas (Laravel Horizon)      | —             |
| **scheduler** | Responsável pelos agendamentos do Laravel (`schedule:work`) | —             |

---

### 🚀 Subindo o ambiente

```bash
docker compose up -d --build
```

Esse comando irá:
- Construir a imagem PHP (com Composer, Redis e extensões necessárias).
- Criar containers para todos os serviços.
- Montar o código da aplicação no container app.

---
### ⚡ Comandos úteis
#### Rodar comandos Artisan

docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app php artisan schedule:list
docker compose exec app php artisan horizon:terminate

#### Acessar MySQL
```bash
docker compose exec mysql mysql -uuser -ppassword vigia_database
```

#### Acessar Redis

```bash
docker compose exec redis redis-cli

```
#### Logs em tempo real

```bash
docker compose logs -f app
docker compose logs -f horizon
docker compose logs -f scheduler
docker compose logs -f nginx
```

#### Derrubar e remover containers

```bash
docker compose down
```

#### Derrubar e remover tudo (incluindo volumes e imagens)

```bash
docker compose down --rmi all --volumes --remove-orphans
```
---

### 📁 Estrutura dos arquivos de Docker
```bash
docker/
├─ app/
│  ├─ Dockerfile           # Imagem PHP-FPM com dependências do Laravel
│  ├─ php.ini              # Configurações customizadas do PHP
│  ├─ local.ini            # Configurações locais adicionais do PHP
│  └─ entrypoint.sh        # Script de inicialização para composer, key:generate, storage:link
└─ nginx/
   └─ default.conf         # Configuração do Nginx para servir o Laravel
```
- Dockerfile — instala PHP, extensões, Composer e configura o usuário app.
- entrypoint.sh — cuida de pequenos ajustes ao subir o container (.env, storage:link, permissões, etc.).
- default.conf — configura Nginx para apontar para public/index.php do Laravel.

---

### 🔄 Escalabilidade

É possível aumentar a quantidade de workers Horizon facilmente:

```
docker compose up -d --scale horizon=3
```

---

### 🛠️ Dicas

- Sempre que alterar variáveis no .env, reinicie o container app:
```
docker compose restart app
```

- Para aplicar mudanças no Dockerfile ou php.ini, reconstrua:
```
    docker compose up -d --build
```

- Use docker compose exec app bash para entrar no container e depurar manualmente.

---

### 🌐 URLs

- App: http://localhost:8080
- Horizon: http://localhost:8080/horizon

---

### 💡 Observação
- Você não precisa do arquivo init.sql a menos que queira criar o banco ou usuários customizados no primeiro build. O MySQL já usa as variáveis definidas no .env.
- Toda a lógica de migração e seed de dados é feita via comandos php artisan migrate e php artisan db:seed.
