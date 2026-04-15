# Kardex API

API REST para gestión de inventario (kardex) construida con **Laravel 11** y autenticación **JWT**.

---

## Stack

| Tecnología | Versión |
|---|---|
| PHP | 8.2 |
| Laravel | 11.48 |
| JWT | php-open-source-saver/jwt-auth ^2.8 |
| Patrón | Repository Pattern |

---

## Configuración inicial

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

---

## Autenticación

Todos los endpoints protegidos requieren el header:

```
Authorization: Bearer {token}
```

El token se obtiene desde `POST /api/v1/auth/login`.

---

## Endpoints

**Base URL:** `http://localhost:8000/api/v1`

---

### Autenticación (`/auth`)

| Método | Endpoint | Descripción | Auth | Permiso |
|---|---|---|---|---|
| `POST` | `/auth/login` | Iniciar sesión | No | — |
| `POST` | `/auth/register` | Registrar usuario y empresa | No | — |
| `POST` | `/auth/refresh` | Renovar token JWT | No | — |
| `POST` | `/auth/forgot-password` | Solicitar reset de contraseña | No | — |
| `POST` | `/auth/reset-password` | Restablecer contraseña | No | — |
| `GET` | `/auth/verify-email/{id}/{token}` | Verificar email | No | — |
| `POST` | `/auth/logout` | Cerrar sesión (token actual) | Si | — |
| `POST` | `/auth/logout-all` | Cerrar todas las sesiones | Si | — |
| `GET` | `/auth/me` | Datos del usuario autenticado | Si | — |

#### `POST /auth/login`
```json
{
  "email": "usuario@ejemplo.com",
  "password": "secreto123",
  "branch_id": "uuid-opcional"
}
```

#### `POST /auth/register`
```json
{
  "user": {
    "first_name": "Juan",
    "last_name": "Pérez",
    "email": "juan@ejemplo.com",
    "password": "secreto123",
    "password_confirmation": "secreto123",
    "phone": "3001234567"
  },
  "company": {
    "name": "Mi Empresa S.A.S",
    "nit": "900123456-1"
  }
}
```

#### `POST /auth/forgot-password`
```json
{ "email": "usuario@ejemplo.com" }
```

#### `POST /auth/reset-password`
```json
{
  "token": "token-recibido-por-email",
  "password": "nuevaContraseña123",
  "password_confirmation": "nuevaContraseña123"
}
```

---

### Usuarios (`/users`) — Requiere JWT

| Método | Endpoint | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/users` | Listar usuarios paginados | `users:read` |
| `POST` | `/users` | Crear usuario | `users:create` |
| `GET` | `/users/{id}` | Ver usuario | `users:read` |
| `PUT` | `/users/{id}` | Actualizar usuario | `users:update` |
| `DELETE` | `/users/{id}` | Desactivar usuario | `users:delete` |
| `POST` | `/users/{id}/activate` | Activar usuario | `users:update` |
| `PUT` | `/users/{id}/password` | Cambiar contraseña | — |
| `POST` | `/users/{id}/roles` | Asignar rol | `users:assign-roles` |
| `DELETE` | `/users/{id}/roles/{roleId}` | Revocar rol | `users:assign-roles` |

#### Query params para `GET /users`
| Param | Tipo | Descripción |
|---|---|---|
| `search` | string | Búsqueda por nombre o email |
| `is_active` | boolean | Filtrar por estado |
| `per_page` | integer | Resultados por página (default: 20) |

#### `POST /users`
```json
{
  "first_name": "María",
  "last_name": "López",
  "email": "maria@ejemplo.com",
  "password": "secreto123",
  "password_confirmation": "secreto123",
  "phone": "3109876543",
  "role_id": "uuid-del-rol",
  "branch_id": "uuid-de-la-sucursal"
}
```

#### `POST /users/{id}/roles`
```json
{
  "role_id": "uuid-del-rol",
  "branch_id": "uuid-de-la-sucursal"
}
```

---

### Roles (`/roles`) — Requiere JWT

| Método | Endpoint | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/roles` | Listar roles | `roles:read` |
| `POST` | `/roles` | Crear rol | `roles:create` |
| `GET` | `/roles/{id}` | Ver rol | `roles:read` |
| `PUT` | `/roles/{id}` | Actualizar rol | `roles:update` |
| `DELETE` | `/roles/{id}` | Eliminar rol | `roles:delete` |
| `PUT` | `/roles/{id}/permissions` | Sincronizar permisos del rol | `roles:update` |

#### `POST /roles`
```json
{
  "display_name": "Vendedor",
  "description": "Acceso a ventas y clientes",
  "is_default": false,
  "permission_ids": ["uuid-permiso-1", "uuid-permiso-2"]
}
```

#### `PUT /roles/{id}/permissions`
```json
{
  "permission_ids": ["uuid-permiso-1", "uuid-permiso-2", "uuid-permiso-3"]
}
```

---

### Permisos (`/permissions`) — Requiere JWT

| Método | Endpoint | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/permissions` | Listar todos los permisos | `roles:read` |
| `GET` | `/permissions/by-module` | Permisos agrupados por módulo | `roles:read` |

**Módulos disponibles:** `products`, `sales`, `inventory`, `purchases`, `customers`, `suppliers`, `users`, `roles`, `reports`, `settings`, `system`

---

### Categorías (`/category`) — Requiere JWT

| Método | Endpoint | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/category` | Listar categorías paginadas | `category:read` |
| `POST` | `/category` | Crear categoría | `category:create` |
| `GET` | `/category/{id}` | Ver categoría | `category:read` |
| `PUT` | `/category/{id}` | Actualizar categoría | `category:update` |
| `DELETE` | `/category/{id}` | Desactivar categoría | `category:delete` |
| `POST` | `/category/{id}/activate` | Activar categoría | `category:update` |
| `GET` | `/category/{id}/subcategories` | Listar subcategorías | `category:read` |

#### Query params para `GET /category`
| Param | Tipo | Descripción |
|---|---|---|
| `search` | string | Búsqueda por nombre o descripción |
| `is_active` | boolean | Filtrar por estado |
| `per_page` | integer | Resultados por página (default: 20) |

#### `POST /category`
```json
{
  "name": "Bebidas",
  "description": "Bebidas frías y calientes",
  "slug": "bebidas",
  "image_url": "https://ejemplo.com/imagen.jpg",
  "company_id": "uuid-de-la-empresa",
  "parent_id": null
}
```

---

### Productos (`/products`) — Requiere JWT

| Método | Endpoint | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/products` | Listar productos paginados | `products:read` |
| `POST` | `/products` | Crear producto | `products:create` |
| `GET` | `/products/{id}` | Ver producto | `products:read` |
| `PUT` | `/products/{id}` | Actualizar producto | `products:update` |
| `DELETE` | `/products/{id}` | Desactivar producto | `products:delete` |
| `POST` | `/products/{id}/activate` | Activar producto | `products:update` |
| `GET` | `/products/{categoryId}/products` | Productos por categoría | `products:read` |
| `GET` | `/products/barcode/scan/{code}` | Buscar por código de barras | `products:read` |

#### Query params para `GET /products`
| Param | Tipo | Descripción |
|---|---|---|
| `search` | string | Búsqueda por nombre o descripción |
| `is_active` | boolean | Filtrar por estado |
| `category_id` | uuid | Filtrar por categoría |
| `per_page` | integer | Resultados por página (default: 20) |

#### `POST /products`
```json
{
  "name": "Café Americano",
  "category_id": "uuid-de-categoria",
  "company_id": "uuid-de-empresa",
  "cost_price": 1500.00,
  "sale_price": 3500.00,
  "min_price": 3000.00,
  "sku": "CAF-001",
  "slug": "cafe-americano",
  "description": "Café negro sin leche",
  "type": "service",
  "price_includes_tax": false,
  "tax_rate": 0,
  "has_variants": false,
  "attributes": {}
}
```

> **Tipos de producto:** `physical`, `service`, `digital`, `other`

#### `PUT /products/{id}`
```json
{
  "name": "Café Americano Grande",
  "sale_price": 4000.00,
  "description": "Café negro sin leche, tamaño grande"
}
```

---

### Variantes de Producto (`/products/{productId}/variant`) — Requiere JWT

| Método | Endpoint | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/products/{productId}/variant` | Listar variantes del producto | `products:read` |
| `POST` | `/products/{productId}/variant` | Crear variante | `products:create` |
| `GET` | `/products/{productId}/variant/{id}` | Ver variante | `products:read` |
| `PUT` | `/products/{productId}/variant/{id}` | Actualizar variante | `products:update` |
| `DELETE` | `/products/{productId}/variant/{id}` | Desactivar variante | `products:delete` |
| `POST` | `/products/{productId}/variant/{id}/activate` | Activar variante | `products:update` |

#### `POST /products/{productId}/variant`
```json
{
  "name": "Tamaño Grande",
  "sku": "CAF-001-GR",
  "cost_price": 2000.00,
  "sale_price": 4500.00,
  "attributes": {
    "tamaño": "grande",
    "ml": 400
  },
  "image_url": "https://ejemplo.com/grande.jpg",
  "sort_order": 1,
  "is_active": true,
  "is_default": false
}
```

> Si `cost_price` o `sale_price` son `null`, se hereda el precio del producto base.

---

### Códigos de Barras (`/products/{productId}/barcode`) — Requiere JWT

| Método | Endpoint | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/products/{productId}/barcode` | Listar barcodes del producto | `products:read` |
| `POST` | `/products/{productId}/barcode` | Crear barcode | `products:create` |
| `GET` | `/products/{productId}/barcode/{id}` | Ver barcode | `products:read` |
| `PUT` | `/products/{productId}/barcode/{id}` | Actualizar barcode | `products:update` |
| `DELETE` | `/products/{productId}/barcode/{id}` | Eliminar barcode | `products:delete` |
| `GET` | `/products/barcode/scan/{code}` | Escanear barcode (POS) | `products:read` |

#### `POST /products/{productId}/barcode`
```json
{
  "product_variant_id": "uuid-de-la-variante",
  "code": "7702345678901",
  "type": "ean13",
  "is_primary": true
}
```

> **Tipos de barcode:** `ean13`, `ean8`, `upc`, `qr`, `custom`

#### `GET /products/barcode/scan/{code}`

Busca el barcode por código y retorna la variante y el producto asociado. Útil para integraciones POS.

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "code": "7702345678901",
    "type": "ean13",
    "is_primary": true,
    "variant": {
      "id": "uuid",
      "name": "Tamaño Grande",
      "sku": "CAF-001-GR"
    }
  }
}
```

---

## Formato de respuestas

### Éxito
```json
{
  "success": true,
  "data": { },
  "message": "Operación realizada correctamente."
}
```

### Éxito paginado
```json
{
  "success": true,
  "data": [ ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

### Error de validación (422)
```json
{
  "success": false,
  "errors": {
    "email": ["El email es obligatorio."],
    "password": ["La contraseña debe tener al menos 8 caracteres."]
  }
}
```

### Error de dominio (422)
```json
{
  "success": false,
  "error": {
    "code": "DOMAIN_ERROR",
    "message": "Descripción del error de negocio."
  }
}
```

### No encontrado (404)
```json
{
  "success": false,
  "error": {
    "code": "PRODUCT_NOT_FOUND",
    "message": "Producto no encontrado."
  }
}
```

### No autorizado (401)
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHENTICATED",
    "message": "Token inválido o expirado."
  }
}
```

### Sin permiso (403)
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No tienes permiso para realizar esta acción."
  }
}
```

---

## Throttle (límites de peticiones)

| Endpoint | Límite |
|---|---|
| `POST /auth/login` | 5 peticiones / minuto |
| `POST /auth/forgot-password` | 3 peticiones / minuto |

---

## Permisos del sistema

Los permisos siguen el patrón `modulo:accion`:

| Módulo | Permisos disponibles |
|---|---|
| `users` | `read`, `create`, `update`, `delete`, `assign-roles` |
| `roles` | `read`, `create`, `update`, `delete` |
| `category` | `read`, `create`, `update`, `delete` |
| `products` | `read`, `create`, `update`, `delete` |

> Los usuarios con el permiso `system:manage` tienen acceso completo a todos los endpoints.
