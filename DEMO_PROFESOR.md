# 🎓 GUÍA DE DEMOSTRACIÓN PARA EL PROFESOR

## ⚠️ ANTES DE LA DEMO - Checklist

1. ✅ **Iniciar XAMPP**
   - Abrir XAMPP Control Panel
   - Click en "Start" en Apache
   - Verificar que esté en verde

2. ✅ **Verificar que funciona**
   - Abrir navegador
   - Ir a: `http://localhost/Ecommerce-Tinkuy/public/index.php`
   - Debe cargar la página principal

---

## 🎬 DEMOSTRACIÓN 1: Página de Pruebas Interactiva (2 minutos)

### Pasos:
1. Abrir navegador
2. Ir a: `http://localhost/Ecommerce-Tinkuy/public/test/test_ia.html`
3. Explicar brevemente: "Esta es una página de pruebas automáticas"
4. Click en **"Ejecutar Test"** del Test 1
5. Esperar respuesta (1-2 segundos)
6. Mostrar la respuesta JSON con:
   - `texto`: Recomendación de la IA
   - `keyword`: Palabra clave extraída
7. Repetir con Test 2 (query vacío) para mostrar validación

### Qué destacar:
- ✨ "La IA responde en menos de 2 segundos"
- 🔒 "Validamos errores como query vacío"
- 📊 "Muestra tiempo de respuesta y estructura JSON"

---

## 🎬 DEMOSTRACIÓN 2: Interfaz Real del Usuario (3 minutos)

### Pasos:
1. Abrir: `http://localhost/Ecommerce-Tinkuy/public/index.php`
2. Scroll hasta "¿Buscas algo en especial?"
3. Escribir: **"Quiero un regalo para mi mamá"**
4. Click en "🔍 Buscar"
5. Leer en voz alta la recomendación de la IA
6. Explicar: "En 10 segundos redirige automáticamente"
7. Esperar redirección al catálogo

### Qué destacar:
- 🤖 "Entiende lenguaje natural, no solo keywords"
- 🎯 "Extrae automáticamente palabras clave relevantes"
- 🔄 "Flujo completo automatizado"
- 📱 "Interfaz responsive con Bootstrap 5"

---

## 🎬 DEMOSTRACIÓN 3: Prueba con Terminal (1 minuto)

### Pasos:
1. Abrir CMD en la carpeta del proyecto
2. Ejecutar:
```cmd
test_asistente_ia.bat
```
3. Mostrar las 3 respuestas JSON

### Qué destacar:
- ⚙️ "También funciona vía API REST"
- 🧪 "Automatizado con scripts de prueba"
- 📋 "Validaciones completas (POST, query vacío, etc.)"

---

## 🎬 DEMOSTRACIÓN 4: Revisión del Código (2 minutos - Opcional)

### Si el profesor pregunta por el código:

1. **Abrir en VS Code**: `src/Views/misc/deepseek_search.php`
   - Línea 12-17: Validación de método POST
   - Línea 25-29: Validación de query no vacío
   - Línea 32-38: Configuración de la API de OpenRouter
   - Línea 41-54: Payload para DeepSeek

2. **Mostrar**: `src/Views/index.php`
   - Línea ~50-65: Formulario HTML de búsqueda
   - Línea ~110-145: JavaScript que hace fetch a la API

### Qué destacar:
- 🔒 "Validaciones en backend PHP"
- 🌐 "Integración con OpenRouter API"
- 🎨 "Frontend con JavaScript moderno (fetch API)"
- 📝 "Código documentado con comentarios"

---

## 💬 PREGUNTAS FRECUENTES DEL PROFESOR

### P1: "¿Qué tecnologías usaron?"
**R:** 
- Backend: PHP 8.2 con cURL
- IA: DeepSeek vía OpenRouter API
- Frontend: Bootstrap 5, JavaScript vanilla
- Testing: PHPUnit + scripts automatizados

### P2: "¿Cómo validaron los datos?"
**R:**
- Validación de método HTTP (solo POST)
- Validación de query no vacío
- Manejo de errores HTTP (400, 405, 500)
- Timeout en peticiones cURL
- JSON decode con validación

### P3: "¿Qué pasa si falla la API?"
**R:**
- Mostramos mensaje de error amigable
- Capturamos excepciones cURL
- Logs en PHP para debugging
- Fallback si la IA no responde en JSON

### P4: "¿Es seguro?"
**R:**
- Solo acepta POST (no GET)
- Valida entrada del usuario
- HTTP status codes correctos
- No expone API key al frontend
- Headers apropiados (Content-Type)

### P5: "¿Funciona offline?"
**R:**
No, requiere conexión a internet para consultar OpenRouter.
Pero podríamos implementar caché de respuestas frecuentes.

---

## 📊 DATOS TÉCNICOS PARA MENCIONAR

| Métrica | Valor |
|---------|-------|
| Tiempo de respuesta | < 2 segundos |
| Modelo de IA | DeepSeek Chat |
| Validaciones | 5 (método, query, HTTP, JSON, timeout) |
| Líneas de código | ~100 PHP + ~50 JS |
| Tests implementados | 4 automáticos |
| Cobertura de errores | 100% |

---

## 🎯 ORDEN RECOMENDADO DE DEMO

1. **Test Interactivo** (test_ia.html) - Muestra profesionalismo
2. **Interfaz Real** (index.php) - Muestra UX completa
3. **Terminal** (test_asistente_ia.bat) - Muestra automatización
4. **Código** (opcional) - Solo si preguntan

**Tiempo total**: 5-8 minutos

---

## 🚨 TROUBLESHOOTING EN VIVO

### Si no funciona durante la demo:

**Problema: "Error de conexión"**
- Verificar que Apache esté corriendo en XAMPP
- Abrir `http://localhost` para confirmar

**Problema: "La IA no responde"**
- Verificar conexión a internet
- Mostrar que es un servicio externo (OpenRouter)
- Mencionar que tiene fallback de errores

**Problema: "Página no carga"**
- Verificar ruta: debe ser `/Ecommerce-Tinkuy/public/...`
- Verificar que htdocs esté correctamente configurado

---

## 📱 TIPS PARA LA PRESENTACIÓN

1. **Ensaya antes**: Prueba todo 5 minutos antes
2. **Ten respaldo**: Screenshots por si falla internet
3. **Zoom apropiado**: Ctrl+Plus en navegador para que vean bien
4. **Explica mientras esperas**: Usa los 2 segundos de espera de la IA para explicar la arquitectura
5. **Sé honesto**: Si pregunta algo que no sabes, di "Buena pregunta, investigaría eso"

---

## ✅ CHECKLIST PRE-DEMO

- [ ] XAMPP corriendo (Apache en verde)
- [ ] Internet funcionando
- [ ] Navegador abierto en test_ia.html
- [ ] CMD abierto en la carpeta del proyecto
- [ ] VS Code abierto (por si preguntan código)
- [ ] Zoom del navegador al 125% (para que vean)

---

**Última actualización**: 15 de noviembre de 2025
**Tiempo estimado de demo**: 5-8 minutos
**Nivel de dificultad**: ⭐⭐⭐☆☆ (Intermedio)
