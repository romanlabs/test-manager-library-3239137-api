# Setup - Manager Library API

## Pasos para terminar la configuración

Cuando Docker esté levantado, ejecuta esto:

```bash
# Entra al contenedor PHP
docker-compose exec php bash

# Dentro del contenedor:
composer install
php artisan migrate
php artisan db:seed --class=CategorySeeder
```

Eso es todo. La API estará en `http://localhost:8000`

## Endpoints disponibles

- `POST /api/v1/auth/login` - login
- `GET /api/v1/health` - salud
- `GET /api/v1/categories` - categorías
- `GET /api/v1/books` - búsqueda de libros
- `GET /api/v1/books/{id}` - detalle de libro

## Próximos pasos

1. Implementar endpoints de usuarios (POST /users)
2. Implementar endpoints de miembros (POST, GET, etc)
3. Implementar endpoints de préstamos
4. Implementar devoluciones
