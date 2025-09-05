# Amplifica Shopify - Dashboard de Ventas

Aplicación web que permite conectar tu tienda de Shopify y visualizar toda la información importante de una tienda, productos y órdenes en un solo lugar. Perfecto para dueños de tiendas que quieren tener control total de su negocio.

** En directorio de Capturas se dejan vistas de proyecto funcionando**

##  Contenido apliacion:

- Dashboard con estadísticas en tiempo real.
- Gestión de productos.
- Gestión órdenes de venta.
- Tablas de analisis qué productos.
- Visualizar gráficos de ventas y categorías.
- Exportaciones de datos para análisis detallados.
- Historial de exportaciones.
- Log de sistema.

##  Cómo instalar y usar la aplicación

### **Se deja copia de .env en archivos adjuntos**

### Paso 1: Prepara tu computadora

Antes de comenzar, necesitas tener instalado:
- **XAMPP** (incluye Apache, MySQL y PHP) - [Descargar aquí](https://www.apachefriends.org/)
- **Composer** (para manejar las librerías de PHP) - [Descargar aquí](https://getcomposer.org/)

### Paso 2: Descarga y configura el proyecto

1. **Descarga el proyecto** en tu carpeta `htdocs` de XAMPP:
    C:\xampp\htdocs\pruebaTecnicaAmplifica\


2. **Abre la terminal/consola** en la carpeta del proyecto y ejecuta:
   composer install

   -Esto descarga todas las librerías necesarias.

3. **Configura la base de datos:**
   - Abre XAMPP y enciende Apache y MySQL
   - Ve a http://localhost/phpmyadmin
   - Crea una nueva base de datos llamada `amplifica_shopify`

4. **Configura las variables del sistema:**
   - Copia el archivo `.env.example` y renómbralo a `.env`
   - Abre el archivo `.env` y cambia estas líneas:
   ```
   DB_DATABASE=amplifica_shopify
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Prepara las tablas de la base de datos:**
   -Desde consola:
   php artisan migrate


6. **Genera la clave de la aplicación:**
   -Desde consola:
   php artisan key:generate


### Paso 3: Enciende la aplicación

    -Desde consola:
    php artisan serve
 

Ahora puedes abrir tu navegador y ir a: http://localhost:8000

## 🔐 Cómo iniciar sesión

La aplicación viene con usuarios de prueba ya creados:

**Usuario Administrador:**
- Usuario: `admin@test.com`
- Contraseña: `password123`


## 🛍️ Cómo conectar tu tienda de Shopify

### Opción 1: Usando los datos de prueba (Recomendado para empezar)

Si solo quieres probar la aplicación, ya viene configurada con datos de ejemplo de una tienda real de Shopify. Solo inicia sesión y podrás ver:
- Productos de ejemplo
- Órdenes de muestra
- Gráficos con datos reales
- Todas las funcionalidades trabajando

### Opción 2: Conectar tu propia tienda de Shopify

Si quieres conectar tu tienda real de Shopify:

1. **Ve a tu tienda de Shopify** y entra al panel de administración

2. **Crea una aplicación privada:**
   - Ve a "Configuración" → "Aplicaciones y canales de venta"
   - Haz clic en "Desarrollar aplicaciones para tu tienda"
   - Crea una nueva aplicación privada

3. **Configura los permisos:**
   Tu aplicación necesita poder leer:
   - Productos (`read_products`)
   - Órdenes (`read_orders`)
   - Información de la tienda (`read_shop`)

4. **Copia tus datos de conexión:**
   Shopify te dará:
   - El nombre de tu tienda (algo como: `mitienda.myshopify.com`)
   - Un token de acceso (una clave larga con letras y números)

5. **Configura la aplicación:**
   - En el archivo `.env` de tu proyecto, busca estas líneas:
   ```
   SHOPIFY_SHOP_DOMAIN=tu-tienda.myshopify.com
   SHOPIFY_ACCESS_TOKEN=tu-token-aqui
   ```
   - Reemplaza con tus datos reales

6. **¡Ya está!** Reinicia la aplicación y verás los datos de tu tienda real.

## 🧪 Cómo probar que todo funciona

### Prueba básica (5 minutos):
1. Abre http://localhost:8000
2. Inicia sesión con `admin@test.com` / `password123`
3. Deberías ver el dashboard con gráficos y estadísticas
4. Haz clic en "Actualizar" - los datos se actualizan automáticamente
5. Ve a "Productos" y "Órdenes" para explorar

### Prueba de exportación (2 minutos):
1. Ve a la sección "Productos"
2. Haz clic en "Exportar a Excel"
3. Deberías poder descargar un archivo Excel con todos los productos
4. Ve a "Historial de Exportaciones" para ver el registro

### Prueba de gráficos (1 minuto):
1. En el dashboard, verifica que aparezcan:
   - Gráfico de productos por categoría
   - Gráfico de pedidos por estado
   - Tabla de productos más vendidos
   - Tabla de productos con más ganancias

### Prueba en móvil (1 minuto):
1. Abre la aplicación en tu teléfono
2. Deberías ver un botón de menú (☰) en la parte superior
3. Al tocarlo se despliegan todas las opciones de navegación

## 🔧 Solución de problemas comunes

**❌ "No puedo acceder a http://localhost:8000"**
- Verifica que XAMPP esté encendido (Apache y MySQL en verde)
- Asegúrate de ejecutar `php artisan serve` en la terminal

**❌ "Error de base de datos"**
- Verifica que MySQL esté corriendo en XAMPP
- Confirma que la base de datos `amplifica_shopify` existe
- Revisa que el archivo `.env` tenga los datos correctos

**❌ "No aparecen datos de Shopify"**
- Primero prueba con los datos de ejemplo (ya configurados)
- Si usas tu tienda, verifica que el token de acceso sea correcto
- Revisa que tu aplicación de Shopify tenga los permisos necesarios

**❌ "Las exportaciones no funcionan"**
- Verifica que la carpeta tenga permisos de escritura
- Asegúrate de que tu navegador no esté bloqueando descargas

**❌ "El menú móvil no aparece"**
- Limpia la caché de tu navegador
- Verifica que estés usando una pantalla pequeña o el modo móvil del navegador

**❌ "Error: Cannot read properties of null"**
- Este error ya está corregido en la versión actual
- Si aparece, refresca la página y debería desaparecer

