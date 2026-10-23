# Setup API - Sin Docker

## Paso 1: Instalar dependencias
```bash
cd Desktop\pruebas-sdd\test-manager-library-3239137-api
composer install
```

## Paso 2: Levantar servidor PHP
```bash
php -S localhost:8000 -t public
```

## Paso 3: Probar

Login (credenciales: admin/admin):
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'
```

Health:
```bash
curl http://localhost:8000/api/v1/health
```

## Próximos endpoints a implementar

Según el spec, después de login y health:
- POST /users → crear bibliotecario
- GET /categories → listar categorías
- GET /books → buscar libros
- POST /books → crear libro
- GET /members → listar socios
- POST /loans → registrar préstamo
- etc.
