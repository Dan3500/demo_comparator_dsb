# Comparador de Seguros de Coche

Esta aplicación permite comparar precios de seguros de coche entre diferentes proveedores de forma rápida y sencilla.

## ¿Qué hace la app?
- Recoge los datos del conductor y del vehículo mediante un formulario web.
- Consulta en paralelo a varios proveedores simulados para obtener cotizaciones de seguro.
- Muestra los resultados ordenados por precio, destacando la oferta más barata.
- Aplica automáticamente un descuento si hay una campaña activa.
- Permite ver los resultados y errores de cada proveedor.

## Tecnologías utilizadas
- **Backend:** Symfony (PHP)
- **Frontend:** Vue 3 (Vite)
- **API:** REST (JSON y XML)

## Estructura
- `/src` - Código fuente del backend (Symfony)
- `/frontend` - Aplicación frontend (Vue)
- `/public` - Archivos públicos y punto de entrada de la API

## Instalación rápida
1. Clona el repositorio
2. Configura las variables de entorno (`.env` y `frontend/.env.production`)
3. Instala dependencias backend:
   ```bash
   composer install
   ```
4. Instala dependencias frontend:
   ```bash
   cd frontend && npm install && npm run build
   ```
5. Configura tu servidor web para que el DocumentRoot apunte a `/public`

## Uso
- Accede a la web, rellena el formulario y compara precios de seguro de coche en segundos.
