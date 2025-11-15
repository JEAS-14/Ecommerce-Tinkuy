# 🧪 Datos de Prueba para Postman API v2

## 📋 Cómo ejecutar el script

### Opción 1: phpMyAdmin (RECOMENDADO)
1. Abre **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Selecciona `tinkuy_db` en el panel izquierdo
3. Click en pestaña **SQL**
4. Copia y pega todo el contenido de `datos_prueba.sql`
5. Click en **"Continuar"**
6. ✅ Verás mensajes de confirmación al final

### Opción 2: MySQL CLI
```cmd
cd C:\xampp\mysql\bin
mysql.exe -u root -p tinkuy_db < C:\xampp\htdocs\Ecommerce-Tinkuy\test\datos_prueba.sql
```

---

## 👥 Usuarios creados

| Usuario | Contraseña | Rol | ID |
|---------|-----------|-----|-----|
| `admin_test` | `admin123` | Admin | 100 |
| `vendedor_test` | `vendedor123` | Vendedor | 101 |
| `cliente_test` | `cliente123` | Cliente | 102 |

---

## 📦 Productos insertados

| ID | Nombre | Categoría | Variantes | Stock |
|-----|--------|-----------|-----------|-------|
| 100 | Chompa de Alpaca Premium | Chompas | 3 (S/M/L Rojo/Azul) | 20 |
| 101 | Gorro Andino | Accesorios | 2 (Multicolor/Verde) | 50 |
| 102 | Manta Cusqueña | Textiles | 2 (Grande/Pequeña) | 15 |

### Variantes disponibles:
- `id_variante` **100**: Chompa S Rojo (S/. 150.00)
- `id_variante` **101**: Chompa M Rojo (S/. 150.00)
- `id_variante` **102**: Chompa L Azul (S/. 150.00)
- `id_variante` **103**: Gorro Única Multicolor (S/. 35.00)
- `id_variante` **104**: Gorro Única Verde (S/. 35.00)
- `id_variante` **105**: Manta Grande Natural (S/. 80.00)
- `id_variante` **106**: Manta Pequeña Natural (S/. 60.00)

---

## 📍 Direcciones de `cliente_test`

| ID | Dirección | Ciudad | Principal |
|-----|-----------|--------|-----------|
| 100 | Av. Arequipa 1234, Miraflores | Lima | ✅ |
| 101 | Jr. Cusco 567, Centro | Cusco | ❌ |

---

## 💳 Tarjetas de `cliente_test`

| ID | Últimos 4 | Tipo | Expiración |
|-----|-----------|------|------------|
| 100 | 4444 | Visa | 12/28 |
| 101 | 5555 | Mastercard | 06/27 |

---

## 🚚 Empresas de envío

| ID | Nombre | Contacto |
|-----|--------|----------|
| 100 | Olva Courier | 01-5551234 |
| 101 | Shalom Empresarial | 01-5555678 |
| 102 | Serpost | 01-5559999 |

---

## 🛒 Pedido de ejemplo

**ID Pedido:** 100  
**Cliente:** cliente_test (ID 102)  
**Estado:** Pagado  
**Total:** S/. 185.00

**Detalles:**
- `id_detalle` **100**: Chompa S Rojo × 1 (S/. 150.00) → Estado: Pagado, listo para envío
- `id_detalle` **101**: Gorro Multicolor × 1 (S/. 35.00) → Estado: Pagado, listo para envío

---

## 🎯 Variables para Postman

Actualiza tu environment `Ecommerce-Tinkuy-local-v2.postman_environment.json` con:

```json
{
  "id_producto": "100",
  "id_variante": "100",
  "id_direccion": "100",
  "id_empresa_envio": "100",
  "id_detalle_envio": "100",
  "buscar": "chompa",
  "categoria": "100",
  "orden": "nombre_asc"
}
```

---

## ⚠️ Notas importantes

1. **Contraseñas hash**: Las contraseñas están hasheadas con `password_hash()` de PHP. El hash en el SQL es de ejemplo; si no funciona el login, ejecuta este PHP para generar hashes reales:

```php
<?php
echo password_hash('admin123', PASSWORD_DEFAULT) . "\n";
echo password_hash('vendedor123', PASSWORD_DEFAULT) . "\n";
echo password_hash('cliente123', PASSWORD_DEFAULT) . "\n";
```

2. **IDs fijos**: Todos los IDs empiezan en 100+ para no chocar con tus datos existentes.

3. **Limpieza opcional**: Descomenta las líneas `DELETE` al inicio del SQL si quieres borrar datos de prueba anteriores.

---

## ✅ Verificación

Después de ejecutar el script, verás en phpMyAdmin:

```
✅ Datos de prueba insertados correctamente
👤 Usuarios: 3 (IDs 100-102)
📦 Productos: 3 (IDs 100-102)
🎨 Variantes: 7 (IDs 100-106)
📍 Direcciones: 2 (IDs 100-101)
🚚 Empresas envío: 3 (IDs 100-102)
```
