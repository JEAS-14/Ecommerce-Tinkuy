# Asistente Inteligente de Búsqueda con IA (DeepSeek)

## 📋 Descripción

Integración de asistente de búsqueda inteligente usando la API de OpenRouter con el modelo DeepSeek Chat. Permite a los usuarios buscar productos mediante lenguaje natural y recibir recomendaciones personalizadas.

## 🎯 Funcionalidades

- **Búsqueda por lenguaje natural**: Los usuarios escriben lo que buscan en español coloquial
- **Recomendaciones IA**: DeepSeek analiza la consulta y sugiere productos relevantes
- **Extracción de palabras clave**: La IA identifica términos de búsqueda específicos
- **Redirección automática**: Después de 10 segundos, redirige al catálogo con la palabra clave

## 📁 Archivos Implementados

### 1. `src/Views/misc/deepseek_search.php`
**Propósito**: Endpoint backend que procesa las peticiones a la IA

**Funcionalidad**:
- Recibe consultas POST con JSON: `{ "query": "texto" }`
- Llama a la API de OpenRouter (DeepSeek Chat)
- Limpia y parsea la respuesta JSON de la IA
- Retorna: `{ "texto": "recomendación", "keyword": "palabra_clave" }`

**Validaciones**:
- Solo acepta método POST
- Valida que el query no esté vacío
- Maneja errores de conexión y respuestas inválidas
- Incluye fallback si la IA no responde en JSON

### 2. `public/deepseek_search.php`
**Propósito**: Proxy público para acceder al endpoint desde el navegador

**Funcionalidad**:
- Define `BASE_PATH` si no existe
- Incluye la implementación real de `src/Views/misc/deepseek_search.php`
- Permite acceso directo desde URLs tipo `/Ecommerce-Tinkuy/public/deepseek_search.php`

### 3. `src/Views/index.php` (modificado)
**Propósito**: Interfaz de usuario del asistente IA

**Cambios implementados**:
- **Carrusel de banners**: Agregado con 3 imágenes (banner1.png, banner2.png, banner3.png)
- **Formulario de búsqueda IA**: Input con botón de búsqueda y área de sugerencias
- **Script JavaScript**: 
  - Captura el submit del formulario
  - Hace fetch POST a `public/deepseek_search.php`
  - Muestra la recomendación de la IA
  - Redirige automáticamente después de 10s si hay keyword

## 🔧 Configuración Técnica

### API Key de OpenRouter
```php
"Authorization: Bearer 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"
```

### Modelo de IA
```php
"model" => "deepseek/deepseek-chat"
```

### Prompt del Sistema
```
Eres un asistente de tienda de artesanías peruanas (Tinkuy). 
Responde de forma breve y clara recomendando un producto del catálogo 
basado en la consulta del usuario. Además, proporciona UNA SOLA palabra 
clave de búsqueda relevante (ejemplo: 'chompa', 'cerámica', 'collar'). 
Responde SIEMPRE en formato JSON: 
{"texto":"<tu recomendación aquí>", "keyword":"<palabra clave>"}
```

## 🚀 Flujo de Funcionamiento

1. **Usuario escribe consulta**: "Quiero un regalo para mi mamá"
2. **JavaScript captura submit**: Previene recarga de página
3. **Fetch POST a API**: Envía `{ "query": "Quiero un regalo para mi mamá" }`
4. **Backend llama a DeepSeek**: Con prompt contextualizado
5. **IA responde**: `{ "texto": "Te recomiendo nuestros collares artesanales de plata, perfectos para regalos especiales", "keyword": "collar" }`
6. **Frontend muestra recomendación**: En el div `#iaSuggestion`
7. **Espera 10 segundos**: Tiempo para que el usuario lea
8. **Redirección automática**: A `?page=products&buscar=collar`

## 🧪 Pruebas Recomendadas

### Prueba 1: Consulta básica
```javascript
// En la consola del navegador:
fetch('/Ecommerce-Tinkuy/public/deepseek_search.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ query: 'chompa de alpaca' })
}).then(r => r.json()).then(console.log);
```

**Respuesta esperada**:
```json
{
  "texto": "Nuestras chompas de alpaca son suaves y abrigadoras, ideales para clima frío",
  "keyword": "chompa"
}
```

### Prueba 2: Query vacío (validación)
```javascript
fetch('/Ecommerce-Tinkuy/public/deepseek_search.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ query: '' })
}).then(r => r.json()).then(console.log);
```

**Respuesta esperada**:
```json
{
  "error": "Query vacío. Proporcione una consulta válida."
}
```

### Prueba 3: Método no permitido
```javascript
fetch('/Ecommerce-Tinkuy/public/deepseek_search.php', {
  method: 'GET'
}).then(r => r.json()).then(console.log);
```

**Respuesta esperada**:
```json
{
  "error": "Método no permitido. Use POST."
}
```

### Prueba 4: Interfaz completa
1. Abrir `http://localhost/Ecommerce-Tinkuy/public/index.php`
2. Escribir en el buscador IA: "algo para el frío"
3. Click en "Buscar"
4. Verificar mensaje: "🤖 Pensando en la mejor recomendación..."
5. Ver respuesta de la IA
6. Esperar 10 segundos
7. Verificar redirección automática al catálogo

## 🔒 Seguridad

### Validaciones implementadas:
- ✅ Solo acepta POST (rechaza GET, PUT, DELETE)
- ✅ Valida query no vacío
- ✅ Maneja errores HTTP (400, 500)
- ✅ Timeout de cURL configurado (por defecto)
- ✅ JSON decode con validación de errores

### Mejoras futuras recomendadas:
- [ ] Rate limiting (limitar consultas por IP/sesión)
- [ ] Sanitización adicional del query (evitar inyección)
- [ ] Logging de consultas para análisis
- [ ] Caché de respuestas frecuentes
- [ ] API Key en variable de entorno (no hardcodeada)

## 📊 Monitoreo y Logs

Los errores se registran en el log de PHP:
```php
error_log("Error cURL API: " . $curl_error);
```

**Verificar logs en XAMPP**:
- Windows: `C:\xampp\php\logs\php_error_log`
- Linux/Mac: `/opt/lampp/logs/php_error_log`

## 🛠️ Troubleshooting

### Problema: "Error con el asistente"
**Causas posibles**:
1. cURL no habilitado en PHP
2. API Key inválida o expirada
3. Sin conexión a internet
4. OpenRouter API caída

**Solución**:
1. Verificar `php.ini`: `extension=curl` debe estar descomentado
2. Verificar API key en OpenRouter dashboard
3. Probar conexión: `ping openrouter.ai`
4. Revisar logs de PHP

### Problema: "No se recibió explicación de la IA"
**Causa**: La IA respondió en formato no-JSON o con estructura diferente

**Solución**: Revisar la respuesta cruda en logs y ajustar el parsing

### Problema: No redirige después de 10 segundos
**Causa**: JavaScript bloqueado o keyword vacío

**Solución**:
1. Abrir consola del navegador (F12)
2. Verificar que `data.keyword` tenga valor
3. Revisar errores de JavaScript

## 📚 Documentación Externa

- **OpenRouter API**: https://openrouter.ai/docs
- **DeepSeek Model**: https://openrouter.ai/models/deepseek/deepseek-chat
- **cURL PHP**: https://www.php.net/manual/en/book.curl.php

## 👥 Créditos

Implementado por el equipo de desarrollo de Tinkuy Ecommerce.
Basado en código compartido por compañeros de proyecto.

---

**Última actualización**: 15 de noviembre de 2025
**Versión**: 1.0.0
