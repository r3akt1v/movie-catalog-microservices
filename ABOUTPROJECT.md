# Описание проекта: Микросервисная архитектура каталога фильмов

## Обзор проекта

Проект представляет собой полнофункциональное веб-приложение для просмотра и покупки фильмов, реализованное на микросервисной архитектуре. Приложение демонстрирует современные подходы к разработке: контейнеризацию, балансировку нагрузки, отказоустойчивость и разделение ответственности между сервисами.

**Основные цели проекта:**
- Демонстрация микросервисной архитектуры
- Реализация отказоустойчивой системы
- Использование Docker для контейнеризации
- Показать возможности Nginx как балансировщика нагрузки
- Создание полнофункционального приложения с видеоплеером

---

## Архитектура системы

### Общая схема

```
Пользователь (Браузер)
        ↓
   http://localhost:8080
        ↓
┌─────────────────────────────┐
│   API Gateway (Nginx)      │
│   Балансировщик нагрузки   │
│   Порт: 8080 → 80          │
└─────────────────────────────┘
        ↓
   Маршрутизация запросов
        ↓
┌──────────────────────────────────────────────────────┐
│  Микросервисы (каждый в отдельном контейнере)        │
├──────────────────────────────────────────────────────┤
│  catalog-service  (порт 3001) - Каталог фильмов     │
│  ratings-service  (порт 3002) - Оценки и отзывы     │
│  user-service     (порт 3003) - Личный кабинет       │
└──────────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────┐
│   Общая MySQL БД           │
│   microservices_db          │
│   Порт: 3307 (хост)        │
│   3306 (контейнер)          │
└─────────────────────────────┘
```

### Технологический стек

| Компонент | Технология | Версия |
|-----------|-----------|--------|
| Backend | PHP | 8.2 |
| Web Server | Apache | 2.4 |
| Database | MySQL | 8.0 |
| Gateway | Nginx | Alpine |
| Frontend | HTML + JavaScript | Vanilla (ES6+) |
| Auth | JWT | HMAC SHA256 |
| Containerization | Docker | Latest |
| Orchestration | Docker Compose | Latest |

---

## Микросервисы

### 1. Catalog Service (Сервис каталога фильмов)

**Порт:** 3001 (внутри контейнера: 80)
**Контейнер:** catalog_service
**Образ:** microservices-catalog-service:latest

**Функции:**
- Управление каталогом фильмов
- CRUD операции над фильмами
- Фильтрация по жанрам
- Поиск по названию
- Хранение видео URL для плеера

**API Endpoints:**
- `GET /api/films` - получить все фильмы
- `GET /api/films?genre=action` - фильтр по жанру
- `GET /api/films?search=keyword` - поиск по названию
- `GET /api/films/:id` - получить фильм по ID
- `POST /api/films` - добавить новый фильм
- `PUT /api/films/:id` - обновить фильм
- `DELETE /api/films/:id` - удалить фильм

**Файлы:**
- `catalog-service/src/Database.php` - подключение к MySQL
- `catalog-service/src/CatalogController.php` - бизнес-логика
- `catalog-service/public/index.php` - API роуты
- `catalog-service/Dockerfile` - конфигурация контейнера

**Особенности реализации:**
- Использует PDO для работы с MySQL
- Поддерживает жанры: action, drama, comedy, fantasy
- Каждая операция возвращает JSON
- Обработка ошибок с соответствующими HTTP кодами

---

### 2. Ratings Service (Сервис оценок и отзывов)

**Порт:** 3002 (внутри контейнера: 80)
**Контейнер:** ratings_service
**Образ:** microservices-ratings-service:latest

**Функции:**
- Управление оценками пользователей
- Управление текстовыми отзывами
- Расчёт средней оценки фильма
- Поддержка множества отзывов от одного пользователя

**API Endpoints:**
- `POST /api/ratings` - поставить оценку (1-10)
- `GET /api/films/:id/ratings` - получить оценки фильма с расчётом среднего
- `POST /api/reviews` - написать отзыв (текст опционален)
- `GET /api/films/:id/reviews` - получить отзывы фильма с оценками авторов
- `PUT /api/reviews/:id` - редактировать отзыв
- `DELETE /api/reviews/:id` - удалить отзыв

**Файлы:**
- `ratings-service/src/Database.php` - подключение к MySQL
- `ratings-service/src/RatingsController.php` - бизнес-логика
- `ratings-service/public/index.php` - API роуты
- `ratings-service/Dockerfile` - конфигурация контейнера

**Особенности реализации:**
- Оценки: 1-10 баллов
- Отзывы: до 10000 символов, текст опционален
- Нет UNIQUE ограничений - один пользователь может оставить много отзывов/оценок
- Средняя оценка рассчитывается через SQL AVG()
- Отзывы JOIN с users для отображения имён авторов
- JOIN с ratings для отображения оценки автора отзыва

---

### 3. User Service (Сервис личного кабинета)

**Порт:** 3003 (внутри контейнера: 80)
**Контейнер:** user_service
**Образ:** microservices-user-service:latest

**Функции:**
- Аутентификация и авторизация
- Управление пользователями
- История покупок
- История просмотров
- Личный кабинет с полной статистикой
- JWT токены для защищённых запросов

**API Endpoints:**
- `POST /api/auth/register` - регистрация нового пользователя
- `POST /api/auth/login` - вход (возвращает JWT токен)
- `POST /api/auth/logout` - выход
- `GET /api/user/profile` - профиль пользователя
- `GET /api/user/history` - история покупок и просмотров
- `GET /api/user/ratings` - мои оценки
- `GET /api/user/reviews` - мои отзывы
- `GET /api/user/full-profile` - полный профиль со всей историей
- `POST /api/user/history/purchase` - добавить покупку
- `POST /api/user/history/view` - добавить просмотр

**Файлы:**
- `user-service/src/Database.php` - подключение к MySQL
- `user-service/src/UserController.php` - бизнес-логика
- `user-service/src/AuthMiddleware.php` - JWT генерация и валидация
- `user-service/public/index.php` - API роуты
- `user-service/Dockerfile` - конфигурация контейнера

**Особенности реализации:**
- Хеширование паролей: MD5 + salt (учебный проект)
- JWT токены: HMAC SHA256 подпись
- Payload токена: user_id, exp (время истечения)
- Проверка уникальности login и email
- Минимальная длина логина: 2 символа
- Минимальная длина пароля: 2 символа
- Валидация email через filter_var
- Резервированный логин: admin1

---

## API Gateway (Nginx Балансировщик)

### Что такое балансировщик нагрузки?

**Балансировщик нагрузки (Load Balancer)** - это компонент системы, который распределяет входящие запросы между несколькими серверами или микросервисами. Представьте его как "диспетчера", который принимает звонки от клиентов и перенаправляет их на доступных операторов.

**Простая аналогия:**
Представьте магазин с несколькими кассами. Клиенты встают в очередь, и диспетчер (балансировщик) направляет каждого клиента к той кассе, где сейчас меньше людей. Если одна касса закрыта, диспетчер отправляет клиентов к другим. Все это делает систему быстрее и надёжнее.

**Зачем нужен балансировщик?**

1. **Распределение нагрузки** - запросы равномерно распределяются между серверами, никто не перегружен
2. **Отказоустойчивость** - если один сервер упал, другие продолжают работать
3. **Масштабируемость** - можно добавить больше серверов при росте нагрузки
4. **Безопасность** - скрывает внутреннюю структуру системы от внешних пользователей
5. **Упрощение для клиентов** - пользователи знают только один адрес, не нужно запоминать IP каждого сервиса

**Типы балансировщиков:**

1. **Аппаратные** - физические устройства (F5, Citrix NetScaler) - дорогие, мощные
2. **Программные** - программы на серверах (Nginx, HAProxy) - бесплатные, гибкие
3. **Облачные** - сервисы от провайдеров (AWS ALB, Google Cloud Load Balancing) - управляемые

### Nginx как балансировщик

**Nginx** - это популярный веб-сервер и обратный прокси (reverse proxy), который часто используется как балансировщик. Он лёгкий, быстрый и очень гибкий.

**Что такое обратный прокси?**

Обычный прокси (forward proxy) - это когда пользователь идёт через прокси к интернету (скрывает IP пользователя).

Обратный прокси (reverse proxy) - это когда пользователь идёт к прокси, а прокси перенаправляет запрос на внутренние серверы (скрывает IP серверов).

```
Без балансировщика:
Пользователь → server1:3001 (если знает этот адрес)
Пользователь → server2:3002 (если знает этот адрес)
Пользователь → server3:3003 (если знает этот адрес)

С балансировщиком:
Пользователь → gateway:8080 (единственный адрес)
                  → server1:3001 (автоматически)
                  → server2:3002 (автоматически)
                  → server3:3003 (автоматически)
```

### Как Nginx работает в этом проекте

**Порт:** 8080 (внутри контейнера: 80)
**Контейнер:** api_gateway
**Образ:** nginx:alpine

**Основные функции:**

1. **Единая точка входа** - пользователи знают только один адрес: http://localhost:8080
2. **Маршрутизация API запросов** - перенаправляет запросы к нужному микросервису
3. **Раздача статического фронтенда** - обслуживает HTML, CSS, JS файлы
4. **Управление HTTP заголовками** - добавляет реальные IP пользователей в заголовки

### Конфигурация Nginx

**Файл:** `gateway/nginx.conf`

**Структура конфигурации:**

```nginx
events {
    worker_connections 1024;  # Максимальное количество одновременных соединений
}

http {
    # Upstream - определение группы серверов для балансировки
    upstream catalog_service {
        server catalog-service:80;  # Микросервис внутри Docker сети
    }

    upstream ratings_service {
        server ratings-service:80;
    }

    upstream user_service {
        server user-service:80;
    }

    server {
        listen 80;  # Слушать на порту 80 внутри контейнера
        server_name localhost;

        # Правила маршрутизации (location блоки)
        location /api/ {
            proxy_pass http://catalog_service;  # Перенаправить на catalog
            proxy_set_header Host $host;         # Передать заголовок Host
            proxy_set_header X-Real-IP $remote_addr;  # Реальный IP клиента
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;  # Цепочка прокси
        }

        # Более специфичные правила для ratings
        location ~ ^/api/films/[^/]+/(ratings|reviews)$ {
            proxy_pass http://ratings_service;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        }

        location /api/ratings {
            proxy_pass http://ratings_service;
            # ... заголовки
        }

        location /api/reviews {
            proxy_pass http://ratings_service;
            # ... заголовки
        }

        # Маршрутизация для user-service
        location /api/auth {
            proxy_pass http://user_service;
            # ... заголовки
        }

        location /api/user {
            proxy_pass http://user_service;
            # ... заголовки
        }

        # Health check endpoint
        location /health {
            return 200 'OK';
            add_header Content-Type text/plain;
        }

        # Раздача статического фронтенда
        location / {
            root /usr/share/nginx/html;  # Путь к файлам внутри контейнера
            index index.html;
            try_files $uri $uri/ /index.html;  # SPA routing
        }
    }
}
```

### Правила маршрутизации (детально)

**1. Каталог фильмов:**
```
Запрос: GET /api/films
→ Nginx видит location /api/
→ Перенаправляет на catalog-service:80
→ catalog-service обрабатывает запрос
→ Ответ возвращается через Nginx пользователю
```

**2. Оценки фильма:**
```
Запрос: GET /api/films/film1/ratings
→ Nginx видит location ~ ^/api/films/[^/]+/(ratings|reviews)$
→ Регулярное выражение совпадает
→ Перенаправляет на ratings-service:80
→ ratings-service обрабатывает запрос
```

**3. Авторизация:**
```
Запрос: POST /api/auth/login
→ Nginx видит location /api/auth
→ Перенаправляет на user-service:80
→ user-service обрабатывает JWT генерацию
```

**4. Фронтенд:**
```
Запрос: GET /
→ Nginx видит location /
→ Возвращает файл index.html из /usr/share/nginx/html
→ JavaScript в браузере выполняется дальше
```

### Примеры запросов через балансировщик

**Пример 1: Получить список фильмов**
```
1. Пользователь открывает http://localhost:8080
2. JavaScript делает fetch('/api/films')
3. Запрос идёт на Nginx (port 8080)
4. Nginx видит location /api/
5. Перенаправляет на catalog-service:80
6. catalog-service делает SQL запрос к MySQL
7. Возвращает JSON с фильмами
8. Nginx передаёт ответ пользователю
```

**Пример 2: Оценить фильм**
```
1. Пользователь ставит оценку 8
2. JavaScript делает POST /api/ratings
3. Запрос идёт на Nginx (port 8080)
4. Nginx видит location /api/ratings
5. Перенаправляет на ratings-service:80
6. ratings-service сохраняет в MySQL
7. Возвращает {"message": "Rating saved"}
8. Nginx передаёт ответ пользователю
```

### Upstream конфигурация

**Что такое upstream?**

Upstream - это группа серверов, к которым Nginx может распределять запросы. В нашем проекте каждый upstream содержит только один сервер, но в реальных системах там может быть много серверов.

**Текущая конфигурация:**
```nginx
upstream catalog_service {
    server catalog-service:80;
}
```

**Пример с балансировкой между несколькими серверами:**
```nginx
upstream catalog_service {
    server catalog-service-1:80 weight=3;  # 75% запросов
    server catalog-service-2:80 weight=1;  # 25% запросов
}
```

**Методы балансировки:**
- **Round Robin** - по очереди (по умолчанию)
- **Least Connections** - на сервер с меньшим количеством соединений
- **IP Hash** - по IP клиента (один клиент всегда на один сервер)
- **Weighted** - с весами (как в примере выше)

### Заголовки proxy_set_header

**Зачем они нужны?**

Когда запрос проходит через прокси, исходный IP клиента теряется. Могут быть проблемы:
- Логи показывают IP прокси, а не реального клиента
- Блокировки по IP не работают
- Rate limiting не работает

**Решение:**
```nginx
proxy_set_header X-Real-IP $remote_addr;  # Реальный IP клиента
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;  # Цепочка прокси
proxy_set_header Host $host;  # Сохранить оригинальный Host
```

### Преимущества использования Nginx Gateway

**1. Для пользователей:**
- Один простой адрес: http://localhost:8080
- Не нужно знать порты 3001, 3002, 3003
- Единый интерфейс

**2. Для разработчиков:**
- Можно менять внутреннюю архитектуру без изменения для клиентов
- Добавлять новые сервисы без переделки фронтенда
- Логировать все запросы в одном месте
- Добавлять HTTPS/CORS в одном месте

**3. Для отказоустойчивости:**
- Можно заменить один сервис без остановки всей системы
- Canary deployment (постепенный релиз)
- A/B тестирование

### Docker интеграция

**В docker-compose.yml:**
```yaml
gateway:
  image: nginx:alpine
  container_name: api_gateway
  ports:
    - "8080:80"  # Проброс порта 8080 хоста → 80 контейнера
  volumes:
    - ./gateway/nginx.conf:/etc/nginx/nginx.conf:ro  # Конфигурация
    - ./frontend:/usr/share/nginx/html:ro  # Статические файлы
  depends_on:
    - catalog-service
    - ratings-service
    - user-service
  networks:
    - microservices_network
```

**Сеть Docker:**
- Все контейнеры в одной сети microservices_network
- Nginx обращается к сервисам по их container names (catalog-service, ratings-service, user-service)
- Docker DNS резолвит эти имена в IP адреса

### Тестирование балансировщика

**1. Проверить что Gateway работает:**
```bash
curl http://localhost:8080/health
# Ожидаемый ответ: OK
```

**2. Проверить маршрутизацию:**
```bash
curl http://localhost:8080/api/films
# Должен вернуть список фильмов через catalog-service
```

**3. Проверить прямое обращение к сервису:**
```bash
curl http://localhost:3001/api/films
# Должен вернуть то же самое напрямую
```

**4. Проверить что сервисы независимы:**
```bash
docker stop catalog_service
curl http://localhost:8080/api/films
# Должна быть ошибка (catalog недоступен)

curl http://localhost:8080/api/auth/login
# Должен работать (user-service доступен)
```

### Визуализация потока запроса

```
┌─────────────┐
│   Браузер   │
│  localhost  │
└──────┬──────┘
       │ http://localhost:8080/api/films
       ↓
┌──────────────────────────────────┐
│    Nginx Gateway (порт 8080)    │
│    - Принимает запрос           │
│    - Анализирует URL             │
│    - Выбирает upstream          │
└──────┬───────────────────────────┘
       │ proxy_pass http://catalog_service
       ↓
┌──────────────────────────────────┐
│  Catalog Service (порт 3001)    │
│  - Принимает запрос             │
│  - Обращается к MySQL           │
│  - Возвращает JSON              │
└──────┬───────────────────────────┘
       │
       ↓
┌──────────────────────────────────┐
│   MySQL Database (порт 3307)     │
│   - Выполняет SQL запрос        │
│   - Возвращает данные           │
└──────┬───────────────────────────┘
       │
       ↓ (обратно по цепочке)
┌─────────────┐
│   Браузер   │
│  видит JSON │
└─────────────┘
```

### Альтернативы Nginx

**Если бы мы не использовали Nginx:**
- Фронтенд должен знать адреса всех сервисов (3001, 3002, 3003)
- CORS проблемы между разными портами
- Нет единой точки для добавления HTTPS
- Нет центрального логирования
- Нет возможности кэширования

**Другие балансировщики:**
- **HAProxy** - специализированный балансировщик, очень мощный
- **Envoy** - современный, для Kubernetes, сложнее в настройке
- **Traefik** - автоматический, для Docker/Kubernetes
- **AWS ALB** - облачный, платный, управляемый

**Почему выбран Nginx:**
- Бесплатный и open-source
- Лёгкий и быстрый
- Простая конфигурация
- Отличная документация
- Широко используется в индустрии
- Поддерживает proxy_pass для микросервисов
- Может раздавать статику

### Файлы конфигурации

**1. gateway/nginx.conf**
- Основная конфигурация Nginx
- Определяет upstream блоки
- Настраивает location правила
- Устанавливает proxy заголовки

**2. docker-compose.yml**
- Определяет gateway сервис
- Пробрасывает порт 8080
- Монтирует конфигурацию
- Монтирует frontend файлы

**3. frontend/index.html**
- Статические файлы, которые раздаёт Nginx
- JavaScript делает запросы к /api/* которые проксируются

### Резюме

**Nginx Gateway в этом проекте:**
- Единая точка входа для всех запросов (localhost:8080)
- Маршрутизирует запросы к трём микросервисам
- Раздаёт статический фронтенд
- Скрывает внутреннюю архитектуру от пользователей
- Позволяет отключать любой микросервис без изменения адресов
- Обеспечивает отказоустойчивость на уровне маршрутизации

**Без него было бы:**
- Пользователь должен знать три разных адреса (3001, 3002, 3003)
- CORS проблемы между портами
- Нет единой точки для управления
- Сложнее масштабировать

**С ним:**
- Один адрес для всего приложения
- Прозрачная маршрутизация
- Лёгкое добавление новых сервисов
- Готов к реальному продакшену

---

## База данных

### Общая информация

**Система:** MySQL 8.0
**Имя БД:** microservices_db
**Пользователь:** app_user
**Пароль:** app_password
**Порт:** 3307 (хост), 3306 (контейнер)
**Характер кодировки:** utf8mb4
**Движок:** InnoDB

### Таблицы

#### 1. users (пользователи)
**Сервис:** user-service

**Поля:**
- `id` - INT, AUTO_INCREMENT, PRIMARY KEY
- `login` - VARCHAR(50), UNIQUE, NOT NULL
- `username` - VARCHAR(100), NOT NULL
- `email` - VARCHAR(100), UNIQUE, NOT NULL
- `password_hash` - VARCHAR(255), NOT NULL
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP

**Индексы:**
- idx_login (login)
- idx_email (email)

**Ограничения:**
- login уникален
- email уникален

---

#### 2. user_purchases (покупки)
**Сервис:** user-service

**Поля:**
- `id` - INT, AUTO_INCREMENT, PRIMARY KEY
- `user_id` - INT, NOT NULL, FOREIGN KEY → users(id)
- `film_id` - VARCHAR(50), NOT NULL
- `film_title` - VARCHAR(255), NOT NULL
- `price` - INT, NOT NULL
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP

**Индексы:**
- idx_user_id (user_id)
- idx_film_id (film_id)

**Foreign Keys:**
- user_id → users(id) ON DELETE CASCADE

---

#### 3. user_views (просмотры)
**Сервис:** user-service

**Поля:**
- `id` - INT, AUTO_INCREMENT, PRIMARY KEY
- `user_id` - INT, NOT NULL, FOREIGN KEY → users(id)
- `film_id` - VARCHAR(50), NOT NULL
- `film_title` - VARCHAR(255), NOT NULL
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP

**Индексы:**
- idx_user_id (user_id)
- idx_film_id (film_id)

**Foreign Keys:**
- user_id → users(id) ON DELETE CASCADE

---

#### 4. films (фильмы)
**Сервис:** catalog-service

**Поля:**
- `id` - VARCHAR(50), PRIMARY KEY
- `title` - VARCHAR(255), NOT NULL
- `description` - TEXT
- `price` - INT, NOT NULL
- `genre` - VARCHAR(50), NOT NULL
- `img_url` - VARCHAR(500)
- `video_url` - VARCHAR(500) - YouTube embed URL
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP
- `updated_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**Индексы:**
- idx_genre (genre)
- idx_title (title)

**Допустимые жанры:**
- action
- drama
- comedy
- fantasy

---

#### 5. ratings (оценки)
**Сервис:** ratings-service

**Поля:**
- `id` - INT, AUTO_INCREMENT, PRIMARY KEY
- `user_id` - INT, NOT NULL, FOREIGN KEY → users(id)
- `film_id` - VARCHAR(50), NOT NULL
- `score` - INT, NOT NULL, CHECK (score >= 1 AND score <= 10)
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP
- `updated_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**Индексы:**
- idx_film_id (film_id)
- idx_score (score)

**Foreign Keys:**
- user_id → users(id) ON DELETE CASCADE

**Особенности:**
- Оценка от 1 до 10
- Нет UNIQUE ограничения - пользователь может оценить много раз
- При нескольких оценках берётся среднее AVG()

---

#### 6. reviews (отзывы)
**Сервис:** ratings-service

**Поля:**
- `id` - INT, AUTO_INCREMENT, PRIMARY KEY
- `user_id` - INT, NOT NULL, FOREIGN KEY → users(id)
- `film_id` - VARCHAR(50), NOT NULL
- `text` - VARCHAR(10000) - опциональный
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP
- `updated_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**Индексы:**
- idx_film_id (film_id)
- idx_created_at (created_at)

**Foreign Keys:**
- user_id → users(id) ON DELETE CASCADE

**Особенности:**
- Текст отзыва опционален (может быть NULL)
- Нет UNIQUE ограничения - пользователь может оставить много отзывов
- Максимальная длина текста: 10000 символов

---

### Инициализация БД

**Скрипт:** `database/init/01-schema.sql`

**Содержимое:**
- CREATE TABLE для всех 6 таблиц
- Определение Foreign Keys
- Создание индексов
- INSERT 20 тестовых фильмов с YouTube URL

**Пример данных:**
```sql
INSERT INTO films (id, title, description, price, genre, img_url, video_url) VALUES
('film1', 'Film 1', 'Action movie', 299, 'action', 'assets/images/film1.jpg', 'https://www.youtube.com/embed/dQw4w9WgXcQ'),
-- ... ещё 19 фильмов
```

---

## Frontend

### Технологии
- HTML5
- CSS3 (Grid, Flexbox)
- Vanilla JavaScript (ES6+)
- Fetch API для HTTP запросов
- LocalStorage для хранения токена

### Основные компоненты

#### 1. Главная страница (index.html)

**Разделы:**
- Шапка с информацией о пользователе
- Форма авторизации/регистрации
- Фильтр по жанрам
- Сетка фильмов (films-grid)
- Модальное окно фильма
- Модальное окно видеоплеера
- Модальное окно личного кабинета

#### 2. Авторизация

**Форма входа:**
- Логин
- Пароль
- Кнопка "Войти"
- Ссылка на регистрацию

**Форма регистрации:**
- Логин (минимум 2 символа)
- Имя (минимум 2 символа)
- Email (валидация)
- Пароль (минимум 2 символа)
- Кнопка "Зарегистрироваться"

**Валидация:**
- Login не может быть "admin1"
- Email проверяется через filter_var
- Минимальная длина полей проверяется на сервере

#### 3. Каталог фильмов

**Отображение:**
- Grid layout (CSS Grid)
- Карточки фильмов с градиентным фоном
- Название, жанр, цена
- При клике открывается модальное окно

**Фильтрация:**
- Dropdown с жанрами
- Автоматическая перезагрузка при изменении
- "Все жанры" - показывает все фильмы

#### 4. Модальное окно фильма

**Информация:**
- Название
- Описание
- Жанр
- Цена
- Средняя оценка
- Список отзывов

**Кнопки:**
- "Купить фильм" (если не куплен)
- "Посмотреть фильм" (если куплен)
- "Оценить" (если просмотрен)
- "Написать отзыв" (если просмотрен)

**Состояния:**
- Не куплен → кнопка "Купить"
- Куплен, не просмотрен → кнопка "Посмотреть"
- Просмотрен → сообщение "Фильм просмотрен" + форма оценки/отзыва

#### 5. Видеоплеер

**Реализация:**
- YouTube Embed iframe
- Открывается в отдельном модальном окне
- Responsive (16:9 aspect ratio)
- Кнопка "Закрыть и отметить как просмотренный"

**Источники видео:**
- Каждому фильму назначен YouTube URL
- Хранится в поле `video_url` таблицы films
- Используются реальные YouTube видео (тестовые)

#### 6. Система отзывов и оценок

**Разделение:**
- Отдельная кнопка "Оценить" - только оценка
- Отдельная кнопка "Отправить отзыв" - только текст
- Оценка обязательна, отзыв опционален

**Оценка:**
- Input type="number" min="1" max="10"
- Значение по умолчанию: 8
- Отправляется на `/api/ratings`

**Отзыв:**
- Textarea maxlength="10000"
- Placeholder: "Оставьте отзыв о фильме..."
- Отправляется на `/api/reviews`
- Может быть пустым

**Отображение отзывов:**
- Имя пользователя (username + login)
- Дата создания (локализованная)
- Текст отзыва
- Оценка автора (если есть)

#### 7. Личный кабинет

**Доступ:**
- Кнопка "Личный кабинет" в шапке (после входа)
- Открывается в модальном окне

**Разделы:**
- Информация о пользователе
- История покупок
- История просмотров
- Мои оценки
- Мои отзывы

**API запрос:**
- `GET /api/user/full-profile` с JWT токеном
- Возвращает все данные в одном запросе
- Таймаут 2 секунды

**Отображение:**
- Покупки: фильм, цена, дата
- Просмотры: фильм, дата
- Оценки: фильм, оценка, дата
- Отзывы: фильм, текст, дата

---

## Отказоустойчивость

### Принципы реализации

**1. Graceful Degradation**
- При недоступности сервиса показывается сообщение об ошибке
- Остальные функции продолжают работать
- Пользователь понимает что происходит

**2. Таймауты**
- 2 секунды для опциональных запросов
- AbortController для отмены долгих запросов
- Быстрое определение недоступности

**3. Независимость запросов**
- Отказ от Promise.all для критических путей
- Последовательные запросы с отдельными try-catch
- Каждая часть загружается независимо

### Реализация во фронтенде

**loadFilms():**
```javascript
try {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error('Catalog service unavailable');
    }
    // рендер фильмов
} catch (error) {
    grid.innerHTML = '<div class="error">Сервис каталога недоступен...</div>';
}
```

**openFilmModal():**
- Загрузка фильма (обязательно) → если ошибка, alert и return
- Загрузка оценок (опционально) → если ошибка, показать "Сервис недоступен"
- Загрузка отзывов (опционально) → если ошибка, показать "Сервис недоступен"
- Загрузка истории (опционально) → если ошибка, игнорировать

**loadProfile():**
- Таймаут 2 секунды
- Если ошибка → alert "Сервис недоступен"

### Тестирование отказоустойчивости

**Сценарий 1: Отключить catalog-service**
```bash
docker stop catalog_service
```
**Результат:**
- Главная страница: "Сервис каталога недоступен"
- Фильмы не загружаются
- Авторизация работает
- Отзывы доступны

**Сценарий 2: Отключить ratings-service**
```bash
docker stop ratings_service
```
**Результат:**
- Фильмы открываются
- Показывается "Сервис оценок недоступен"
- "Сервис отзывов недоступен"
- Покупки и просмотры работают

**Сценарий 3: Отключить user-service**
```bash
docker stop user_service
```
**Результат:**
- Невозможно войти в систему
- Невозможно купить/посмотреть фильм
- Каталог и отзывы доступны

---

## Docker Контейнеризация

### docker-compose.yml

**Сервисы:**

1. **mysql**
   - Образ: mysql:8.0
   - Контейнер: microservices_db
   - Переменные окружения:
     - MYSQL_ROOT_PASSWORD: rootpassword
     - MYSQL_DATABASE: microservices_db
     - MYSQL_USER: app_user
     - MYSQL_PASSWORD: app_password
   - Порты: 3307:3306
   - Volumes:
     - ./database/init:/docker-entrypoint-initdb.d
     - mysql_data:/var/lib/mysql
   - Сеть: microservices_network

2. **catalog-service**
   - Build: ./catalog-service
   - Контейнер: catalog_service
   - Порты: 3001:80
   - Depends_on: mysql
   - Environment:
     - DB_HOST: mysql
     - DB_PORT: 3306
     - DB_NAME: microservices_db
     - DB_USER: app_user
     - DB_PASSWORD: app_password
   - Сеть: microservices_network

3. **ratings-service**
   - Build: ./ratings-service
   - Контейнер: ratings_service
   - Порты: 3002:80
   - Depends_on: mysql
   - Environment: (те же креды БД)
   - Сеть: microservices_network

4. **user-service**
   - Build: ./user-service
   - Контейнер: user_service
   - Порты: 3003:80
   - Depends_on: mysql
   - Environment: (те же креды БД)
   - Extra: JWT_SECRET
   - Сеть: microservices_network

5. **gateway**
   - Образ: nginx:alpine
   - Контейнер: api_gateway
   - Порты: 8080:80
   - Volumes:
     - ./gateway/nginx.conf:/etc/nginx/nginx.conf:ro
     - ./frontend:/usr/share/nginx/html:ro
   - Depends_on: catalog, ratings, user
   - Сеть: microservices_network

**Сети:**
- microservices_network (bridge driver)

**Volumes:**
- mysql_data (персистентность данных БД)

### Dockerfile (общий для всех сервисов)

```dockerfile
FROM php:8.2-apache

# Установка расширений
RUN docker-php-ext-install pdo pdo_mysql

# Включение mod_rewrite
RUN a2enmod rewrite

# Рабочая директория
WORKDIR /var/www/html

# Копирование кода
COPY src/ /var/www/html/src/
COPY public/ /var/www/html/public/

# Права доступа
RUN chown -R www-data:www-data /var/www/html

# Конфигурация Apache
RUN echo "DocumentRoot /var/www/html/public" > /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/sites-available/000-default.conf
```

### Команды управления

**Запуск:**
```bash
docker-compose up -d
```

**Пересборка:**
```bash
docker-compose up -d --build
```

**Остановка:**
```bash
docker-compose down
```

**Остановка с удалением данных:**
```bash
docker-compose down -v
```

**Просмотр логов:**
```bash
docker logs <container_name>
```

**Вход в контейнер:**
```bash
docker exec -it <container_name> /bin/bash
```

---

## JWT Авторизация

### Генерация токена

**Файл:** `user-service/src/AuthMiddleware.php`

**Алгоритм:**
1. Создаём payload: { user_id, exp, iat }
2. exp = current_time + 3600 (1 час)
3. HMAC SHA256 подпись с секретным ключом
4. Base64UrlEncode header.payload.signature

**Секретный ключ:**
```php
$secret = 'your-secret-key-change-in-production';
```

### Валидация токена

**Процесс:**
1. Разделить токен на 3 части (header.payload.signature)
2. Декодировать header и payload (Base64Url)
3. Проверить exp (время истечения)
4. Пересчитать подпись
5. Сравнить с полученной подписью
6. Если совпадает → вернуть user_id

### Использование токена

**Frontend:**
```javascript
const token = localStorage.getItem('token');
fetch(url, {
    headers: { 'Authorization': `Bearer ${token}` }
});
```

**Backend:**
```php
$userId = $auth->authenticate();
// возвращает user_id или выбрасывает ошибку
```

### Защищённые endpoints

Все endpoints user-service требующие авторизации:
- `/api/user/profile`
- `/api/user/history`
- `/api/user/ratings`
- `/api/user/reviews`
- `/api/user/full-profile`
- `/api/user/history/purchase`
- `/api/user/history/view`

---

## Frontend JavaScript

### Основные функции

#### 1. Управление состоянием
```javascript
let currentToken = localStorage.getItem('token');
let currentUserId = localStorage.getItem('userId');
let currentFilmId = null;
let currentFilmVideoUrl = null;
```

#### 2. Авторизация
- `register()` - отправка формы регистрации
- `login()` - вход и сохранение токена
- `logout()` - очистка localStorage и UI

#### 3. Загрузка фильмов
- `loadFilms()` - загрузка с фильтрацией
- Таймаут 2 секунды
- Graceful degradation при ошибке

#### 4. Модальные окна
- `openFilmModal(filmId)` - открытие информации о фильме
- `closeModal()` - закрытие
- `openProfileModal()` - личный кабинет
- `closeProfileModal()` - закрытие личного кабинета
- `openVideoPlayer()` - видеоплеер
- `closeVideoModal()` - закрытие плеера
- `closeVideoAndMarkWatched()` - закрытие + отметить просмотренным

#### 5. Действия с фильмами
- `purchaseFilm()` - покупка фильма
- `watchFilm()` - отметка просмотра
- `submitRating()` - отправка оценки
- `submitReview()` - отправка отзыва

#### 6. Отказоустойчивость
Все fetch запросы используют AbortController:
```javascript
const controller = new AbortController();
const timeoutId = setTimeout(() => controller.abort(), 2000);
const response = await fetch(url, { signal: controller.signal });
clearTimeout(timeoutId);
```

---

## Функциональные возможности

### 1. Пользовательский путь

**Новый пользователь:**
1. Регистрация (login, username, email, password)
2. Вход в систему
3. Просмотр каталога фильмов
4. Фильтрация по жанру
5. Покупка фильма
6. Просмотр фильма (YouTube плеер)
7. Оценка фильма (1-10)
8. Написание отзыва (опционально)
9. Просмотр личного кабинета

**Вернувшийся пользователь:**
1. Вход (JWT токен)
2. Просмотр истории покупок
3. Повторный просмотр купленных фильмов
4. Оставление дополнительных отзывов/оценок
5. Редактирование отзывов

### 2. Демонстрация микросервисной архитектуры

**Для преподавателя/защиты:**
1. Показать docker-compose.yml - все сервисы в контейнерах
2. Показать nginx.conf - маршрутизация запросов
3. Показать схему БД - общая для всех сервисов
4. Демонстрация отказоустойчивости:
   - `docker stop catalog_service` → показать что остальное работает
   - `docker stop ratings_service` → показать что остальное работает
   - `docker stop user_service` → показать что остальное работает
5. Показать что каждый сервис доступен напрямую по своему порту
6. Показать ER-диаграмму из dbdiagram.io

### 3. Личный кабинет

**Показывает:**
- Логин, имя, email пользователя
- Дату регистрации
- Все покупки с ценами и датами
- Все просмотры с датами
- Все поставленные оценки
- Все написанные отзывы

**Используется для:**
- Статистики активности пользователя
- Истории взаимодействия с сервисом
- Демонстрации работы user-service

---

## Безопасность

### Реализованные меры

1. **Хеширование паролей**
   - MD5 + salt (учебный проект)
   - Salt: '56s89_))(*$-890fghu2(!*&#@$09AQU'
   - В продакшене нужно использовать bcrypt/argon2

2. **JWT токены**
   - HMAC SHA256 подпись
   - Время жизни: 1 час
   - Хранятся в localStorage (для демонстрации)
   - В продакшене нужно использовать httpOnly cookies

3. **SQL Injection защита**
   - PDO Prepared Statements
   - Все запросы через placeholders (:param)

4. **Валидация ввода**
   - Минимальная длина полей
   - Проверка email через filter_var
   - Проверка диапазона оценок (1-10)
   - Проверка длины текста отзыва (до 10000)

5. **CORS**
   - Access-Control-Allow-Origin: *
   - Для демонстрации localhost

### Не реализовано (учебный проект)

- HTTPS/TLS
- Rate limiting
- CSRF защита
- XSS защита (Content Security Policy)
- httpOnly cookies для JWT
- Refresh токены
- Валидация сложных сценариев

---

## Тестирование

### API Тесты

**1. Регистрация:**
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"login":"testuser","username":"Test User","email":"test@example.com","password":"123456"}'
```

**2. Вход:**
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"testuser","password":"123456"}'
```

**3. Получить фильмы:**
```bash
curl http://localhost:8080/api/films
```

**4. Получить фильм:**
```bash
curl http://localhost:8080/api/films/film1
```

**5. Полный профиль:**
```bash
curl http://localhost:8080/api/user/full-profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Тестирование через браузер

**Main flow:**
1. Открыть http://localhost:8080
2. Зарегистрироваться
3. Войти
4. Купить фильм
5. Посмотреть фильм (YouTube плеер)
6. Оценить фильм
7. Написать отзыв
8. Открыть личный кабинет
9. Проверить историю

**Отказоустойчивость:**
1. Открыть http://localhost:8080
2. `docker stop catalog_service`
3. Обновить страницу → увидеть ошибку
4. `docker start catalog_service`
5. Повторить для других сервисов

---

## Производительность

### Оптимизации

1. **Индексы в БД**
   - idx_login, idx_email (users)
   - idx_genre, idx_title (films)
   - idx_user_id, idx_film_id (user_purchases, user_views, ratings, reviews)
   - idx_score (ratings)
   - idx_created_at (reviews)

2. **Кэширование на фронтенде**
   - Токен в localStorage
   - Не перезагружает фронтенд при каждом запросе

3. **Таймауты**
   - 2 секунды для быстрого обнаружения недоступности
   - Не блокирует UI бесконечно

### Известные ограничения

1. **Количество фильмов:**
   - Тестовые данные: 20 фильмов
   - Без пагинации (все грузятся за раз)
   - Для продакшена нужна пагинация

2. **JWT время жизни:**
   - 1 час
   - Нет refresh токенов
   - После истечения нужно перелогиниться

3. **Видео:**
   - Используются YouTube embed
   - Зависит от внешнего сервиса
   - Нет собственного хостинга видео

---

## Развертывание

### Локальное окружение

**Требования:**
- Docker
- Docker Compose
- 8GB RAM минимум
- 10GB дисковое пространство

**Запуск:**
```bash
cd "/Users/aleksandrsmirnov/Desktop/микросервис практика"
docker-compose up -d --build
```

**Проверка:**
```bash
docker ps  # все контейнеры запущены
curl http://localhost:8080/health  # OK
```

### Production (теоретически)

**Для продакшена нужно:**
1. Заменить MySQL пароли
2. Изменить JWT секрет
3. Использовать bcrypt для паролей
4. Добавить HTTPS
5. Настроить httpOnly cookies
6. Добавить мониторинг (Prometheus + Grafana)
7. Добавить логирование (ELK stack)
8. Настроить CI/CD
9. Добавить rate limiting
10. Настроить бэкапы БД

---

## Визуализация

### ER-диаграмма

**Инструмент:** https://dbdiagram.io/d

**Код для генерации:**
```
// Microservices Database Schema
// Shared database for all services

Table users {
  id INT [pk, increment]
  login VARCHAR(50) [unique]
  username VARCHAR(100)
  email VARCHAR(100) [unique]
  password_hash VARCHAR(255)
  created_at TIMESTAMP
}

Table user_purchases {
  id INT [pk, increment]
  user_id INT
  film_id VARCHAR(50)
  film_title VARCHAR(255)
  price INT
  created_at TIMESTAMP
}

Table user_views {
  id INT [pk, increment]
  user_id INT
  film_id VARCHAR(50)
  film_title VARCHAR(255)
  created_at TIMESTAMP
}

Table films {
  id VARCHAR(50) [pk]
  title VARCHAR(255)
  description TEXT
  price INT
  genre VARCHAR(50)
  img_url VARCHAR(500)
  video_url VARCHAR(500)
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

Table ratings {
  id INT [pk, increment]
  user_id INT
  film_id VARCHAR(50)
  score INT [note: '1-10']
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

Table reviews {
  id INT [pk, increment]
  user_id INT
  film_id VARCHAR(50)
  text VARCHAR(10000)
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

// Relationships
Ref: users.id < user_purchases.user_id [delete: cascade]
Ref: users.id < user_views.user_id [delete: cascade]
Ref: users.id < ratings.user_id [delete: cascade]
Ref: users.id < reviews.user_id [delete: cascade]
Ref: films.id < user_purchases.film_id
Ref: films.id < user_views.film_id
Ref: films.id < ratings.film_id
Ref: films.id < reviews.film_id
```

---

## Структура проекта

```
микросервис практика/
├── docker-compose.yml
├── README.md
├── ABOUTPROJECT.md (этот файл)
├── database/
│   └── init/
│       └── 01-schema.sql
├── catalog-service/
│   ├── Dockerfile
│   ├── src/
│   │   ├── Database.php
│   │   └── CatalogController.php
│   └── public/
│       ├── .htaccess
│       └── index.php
├── ratings-service/
│   ├── Dockerfile
│   ├── src/
│   │   ├── Database.php
│   │   └── RatingsController.php
│   └── public/
│       ├── .htaccess
│       └── index.php
├── user-service/
│   ├── Dockerfile
│   ├── src/
│   │   ├── Database.php
│   │   ├── UserController.php
│   │   └── AuthMiddleware.php
│   └── public/
│       ├── .htaccess
│       └── index.php
├── gateway/
│   └── nginx.conf
└── frontend/
    └── index.html
```

---

## Заключение

Этот проект демонстрирует:

1. **Микросервисная архитектура** - 3 независимых сервиса
2. **Общая база данных** - все сервисы используют одну MySQL
3. **API Gateway** - Nginx как балансировщик и маршрутизатор
4. **Отказоустойчивость** - сервисы работают независимо
5. **Docker контейнеризация** - всё упаковано в контейнеры
6. **JWT авторизация** - защищённые endpoints
7. **Frontend** - SPA без фреймворков
8. **Видеоплеер** - интеграция YouTube
9. **Личный кабинет** - полная история пользователя
10. **Система отзывов** - оценки и текстовые отзывы

Проект готов к демонстрации и защите. Все компоненты документированы, протестированы и работают как единая система.
