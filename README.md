# Tienda de Accesorios para Mascotas con Chatbot (XAMPP)

Este proyecto implementa una tienda virtual completa con autenticación, carrito/checkout simulado, panel administrador, boletas PDF por DomPDF, envío por correo con PHPMailer e integración de un chatbot Python (Flask) con sentimiento y placeholder de identificación de hablante.

## Requisitos
- Windows + XAMPP (PHP 8.x, Apache, MySQL)
- Composer (PHP)
- Python 3.10+ (recomendado) y pip

## Instalación (paso a paso)
1. Copiar la carpeta `Chatbot/` a `htdocs/` (ya está en `c:/xampp/htdocs/Chatbot`).
2. Abrir phpMyAdmin y "Importar" el archivo `Chatbot/schema.sql`.
   - Importante: el usuario admin por defecto se inserta con `password = {PASSWORD_HASH_PLACEHOLDER}`.
   - Opción A (recomendada): Ejecutar `http://localhost/Chatbot/create_admin.php` para generar el hash real de `Admin123!` y actualizar al admin.
   - Opción B: Editar `schema.sql` reemplazando `{PASSWORD_HASH_PLACEHOLDER}` por el resultado de `password_hash('Admin123!', PASSWORD_DEFAULT)` desde un script PHP.
3. Editar `Chatbot/config.php` con tus credenciales:
   - Base de datos: `$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS`.
   - SMTP para correos: `$SMTP_USER`, `$SMTP_PASS`, `$SMTP_HOST`, `$SMTP_PORT`.
   - Google OAuth: `$GOOGLE_CLIENT_ID`, `$GOOGLE_CLIENT_SECRET` (si usas Sign-In).
   - Opcional: `$ASSEMBLYAI_API_KEY` o `$MICROSOFT_SPEAKER_API_KEY` para reconocimiento de hablante (Opción A).
4. Instalar dependencias PHP:
   - En `c:\xampp\htdocs\Chatbot`: `composer install`
5. Instalar dependencias Python:
   - `pip install -r c:\xampp\htdocs\Chatbot\requirements_python.txt`
6. Iniciar el servidor Python (Flask):
   - `python c:\xampp\htdocs\Chatbot\Chatbotconvoz.py`
   - Endpoint: `http://127.0.0.1:5000/chatbot`
7. Abrir la web:
   - `http://localhost/Chatbot/`

## Credenciales admin por defecto
- Email: `admin@local.test`
- Password: `Admin123!`
- Si sigues la Opción A: visita `http://localhost/Chatbot/create_admin.php` una vez para generar el hash y actualizar al admin.

## Funcionalidades
- Tienda con búsqueda, filtro por categorías, detalle de producto, carrito (edición de cantidades) y checkout simulado.
- Autenticación local (password_hash/password_verify) + Google Sign-In (demo con id_token verificado por tokeninfo).
- Panel admin con métricas básicas, CRUD de productos/categorías (subida de imágenes validada), compras y reportes (CSV y PDF si DomPDF instalado).
- Boletas PDF guardadas en `/boletas/` y envío por correo con PHPMailer (si SMTP configurado).
- Chatbot visible en todas las vistas. `chatbot.php` se comunica con Flask y registra logs en `chatbot_logs`.
- CSRF tokens en formularios críticos (login, registro, carrito-actualización, checkout).

## Chatbot (PHP ↔ Python)
- Endpoint Python (`Chatbotconvoz.py`) con Flask: `GET/POST /chatbot`.
  - Parámetros: `mensaje`, `session_id`, `user_id` (opcionales)
  - Respuesta JSON: `{ respuesta, sentimiento, usuario_detectado, action_sugerida, timestamp }`
- Frontend usa Web Speech API (voz a texto y lectura de respuesta).
- Identificación de hablante:
  - Opción A (recomendada): Integrar servicio externo (AssemblyAI/Microsoft). Usar llaves `$ASSEMBLYAI_API_KEY` o `$MICROSOFT_SPEAKER_API_KEY` en `config.php`. Documentar el flujo de enrolamiento (guardar muestra de voz, marcar `voice_enrolled=1`).
  - Opción B (local): `resemblyzer` + `webrtcvad`. Crear un microservicio adicional que reciba muestras de voz, guarde embeddings por usuario y verifique similitud coseno.
- Sentimiento: heurístico simple incluido. Puedes mejorar con librería NLP.

## Scripts útiles
- `create_admin.php`: genera el hash real para `Admin123!` y actualiza el usuario admin.
- `test_chatbot_request.php`: prueba rápida del endpoint `chatbot.php` imprimiendo el JSON.

## Checklist QA
- Acceso a `http://localhost/Chatbot/`.
- Registro/login operativos y contraseñas hasheadas.
- Carrito y checkout crean registros en BD.
- Boleta PDF se genera y se guarda en `/boletas/`; envío por correo funciona con SMTP.
- `Chatbotconvoz.py` levanta el servidor y `chatbot.php` recibe JSON.
- Chat flotante funciona con voz (Web Speech API) y muestra sentimiento.
- Admin (con rol) accede a dashboard, CRUDs, compras y reportes.

## Notas de seguridad
- Uso de PDO con prepared statements.
- CSRF en formularios críticos.
- Sanitización de salida con `htmlspecialchars`.
- Validación de tipo/tamaño en subida de imágenes.
