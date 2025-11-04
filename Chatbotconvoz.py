# ============================================
# CHATBOT CON VOZ (Python 3.13)
# ============================================

# Instalación previa de librerías (opcional voz local):
# py -3.13 -m pip install SpeechRecognition pyttsx3 pyaudio

try:
    import speech_recognition as sr   # Para reconocer la voz y convertirla a texto
except Exception:
    sr = None
try:
    import pyttsx3                    # Para que el bot hable
except Exception:
    pyttsx3 = None
try:
    import pyaudio                    # Para usar el micrófono
except Exception:
    pyaudio = None
from flask import Flask, request, jsonify
import os
import pymysql
try:
    import numpy as np
    from resemblyzer import VoiceEncoder, preprocess_wav
    HAS_SPEAKER = True
except Exception:
    HAS_SPEAKER = False
from datetime import datetime

// Inicializamos los módulos (opcional)
r = sr.Recognizer() if sr else None
engine = pyttsx3.init() if pyttsx3 else None

# Configurar voz en español si está disponible
if engine:
    voices = engine.getProperty('voices')
    for voice in voices:
        if "Spanish" in voice.name or "ES" in voice.id:
            engine.setProperty('voice', voice.id)
            break

# Diccionario de respuestas
respuestas = {
    "hola": "Hola cumpita, ¿cómo estás?",
    "como estas": "Estoy bien, gracias por preguntar.",
    "quien eres": "Soy tu chatbot con voz hecho en Python.",
    "gracias": "De nada, cumpita, para eso estoy.",
    "adios": "Adiós cumpita, cuídate mucho."
}

# Heurística simple de sentimiento
POS = {"bien","excelente","genial","gracias","perfecto","buen","hola"}
NEG = {"mal","triste","problema","error","no funciona","odio","malo"}

def sentimiento_simple(t: str) -> str:
    t = (t or '').lower()
    pos = sum(1 for w in POS if w in t)
    neg = sum(1 for w in NEG if w in t)
    if pos > neg: return "positivo"
    if neg > pos: return "negativo"
    return "neutral"

def hablar(texto):
    """Convierte texto a voz (si engine está disponible) y lo muestra en consola."""
    print(f"Bot: {texto}")
    if engine:
        try:
            engine.say(texto)
            engine.runAndWait()
        except Exception:
            pass

def escuchar():
    """Escucha al usuario y devuelve el texto reconocido (si SR disponible)."""
    if not r or not sr:
        return ""
    with sr.Microphone() as source:
        print("\n Escuchando...")
        r.adjust_for_ambient_noise(source)
        audio = r.listen(source)
    try:
        texto = r.recognize_google(audio, language="es-ES")
        print(f"Tú: {texto}")
        return texto.lower()
    except Exception:
        return ""

def chatbot():
    """Ejecuta la conversación con el usuario."""
    hablar("Hola cumpita, soy tu asistente con voz. ¿Qué quieres hacer?")

    while True:
        texto = escuchar()
        if not texto:
            continue

        respuesta = None
        for clave in respuestas:
            if clave in texto:
                respuesta = respuestas[clave]
                break

        if respuesta:
            hablar(respuesta)
            if "adios" in texto:
                break
        else:
            hablar("No tengo una respuesta para eso, cumpita.")

if __name__ == "__main__":
    # Servidor Flask para integrar con PHP
    app = Flask(__name__)
    API_KEY = os.environ.get('CHATBOT_API_KEY', '').strip()

    def check_api_key():
        if not API_KEY:
            return True
        hdr = request.headers.get('X-API-Key','').strip()
        return hdr == API_KEY

    def db_conn():
        # Config default compatibles con XAMPP
        return pymysql.connect(host='localhost', user='root', password='', database='tienda_mascotas', cursorclass=pymysql.cursors.DictCursor, autocommit=True)

    def ultima_compra(user_id):
        try:
            con = db_conn()
            with con.cursor() as cur:
                cur.execute("SELECT id,total,estado,fecha FROM compras WHERE usuario_id=%s ORDER BY id DESC LIMIT 1", (user_id,))
                c = cur.fetchone()
                if not c:
                    return None, None
                cur.execute("SELECT p.nombre,d.cantidad FROM detalles_compra d JOIN productos p ON p.id=d.producto_id WHERE d.compra_id=%s", (c['id'],))
                items = cur.fetchall()
                return c, items
        except Exception:
            return None, None

    def productos_destacados(limit=3):
        try:
            con = db_conn()
            with con.cursor() as cur:
                cur.execute("SELECT nombre, precio FROM productos ORDER BY id DESC LIMIT %s", (limit,))
                return cur.fetchall()
        except Exception:
            return []
    SPEAKERS_DIR = os.path.join(os.path.dirname(__file__), 'speakers')
    os.makedirs(SPEAKERS_DIR, exist_ok=True)
    encoder = VoiceEncoder() if HAS_SPEAKER else None

    @app.get('/chatbot')
    @app.post('/chatbot')
    def api_chat():
        if not check_api_key():
            return jsonify({"status":"error","message":"forbidden"}), 403
        mensaje = request.args.get('mensaje') or (request.json or {}).get('mensaje') or ''
        usuario = request.args.get('usuario') or (request.json or {}).get('usuario') or 'Invitado'
        session_id = request.args.get('session_id') or (request.json or {}).get('session_id') or ''
        user_id = request.args.get('user_id') or (request.json or {}).get('user_id') or ''

        low = (mensaje or '').lower()
        resp = None
        for k,v in respuestas.items():
            if k in low:
                resp = v
                break
        if not resp:
            # Intents con BD: última compra / estado
            if user_id and any(k in low for k in ['ultima compra','última compra','historial','estado']):
                c, items = ultima_compra(user_id)
                if c:
                    lst = ", ".join([f"{it['cantidad']}x {it['nombre']}" for it in items]) if items else ''
                    resp = f"Tu última compra #{c['id']} está en estado '{c['estado']}', total S/ {c['total']}. {('Items: '+lst) if lst else ''}"
                else:
                    resp = "Aún no tienes compras registradas."
            # Productos sugeridos
            elif any(k in low for k in ['producto','collar','juguete','recom']):
                rows = productos_destacados(3)
                if rows:
                    s = "; ".join([f"{r['nombre']} (S/ {r['precio']:.2f})" for r in rows])
                    resp = f"Algunas opciones: {s}. ¿Quieres ver más detalles?"
                else:
                    resp = "Puedo mostrarte nuestros productos más recientes."
            else:
                resp = "Puedo ayudarte a registrarte, comprar o resolver dudas sobre productos."

        senti = sentimiento_simple(mensaje)
        action = None
        if 'registro' in low or 'registr' in low:
            action = 'mostrar_registro'
            resp = "Para registrarte, haz clic en Registrarse y completa tus datos."
        elif 'comprar' in low or 'carrito' in low or 'checkout' in low:
            action = 'ir_al_carrito'
            resp = "Agrega productos al carrito y procede al Checkout. Si no has iniciado sesión, inicia primero."
        elif senti == 'negativo':
            resp = "Lamento el inconveniente. ¿Deseas que te guíe para registrarte o completar una compra?"

        # Placeholder de identificación de hablante
        # Opción A: usar API externa como AssemblyAI/Microsoft (requiere API Key)
        # Opción B: Resemblyzer local para embeddings de voz (documentado en README)
        usuario_detectado = usuario if usuario and usuario.lower() != 'invitado' else None

        data = {
            "respuesta": (f"Hola {usuario_detectado}, " if usuario_detectado else "") + resp,
            "sentimiento": senti,
            "usuario_detectado": usuario_detectado,
            "action_sugerida": action,
            "timestamp": datetime.utcnow().isoformat()+"Z"
        }
        return jsonify(data)

    @app.post('/speaker/enroll')
    def speaker_enroll():
        if not HAS_SPEAKER:
            return jsonify({"status":"error","message":"Resemblyzer no instalado"}), 400
        user_id = (request.form.get('user_id') or '').strip()
        if not user_id:
            return jsonify({"status":"error","message":"user_id requerido"}), 400
        if 'audio' not in request.files:
            return jsonify({"status":"error","message":"Archivo 'audio' requerido (wav mono 16k)"}), 400
        f = request.files['audio']
        tmp_path = os.path.join(SPEAKERS_DIR, f"tmp_{user_id}.wav")
        f.save(tmp_path)
        try:
            wav = preprocess_wav(tmp_path)
            emb = encoder.embed_utterance(wav)
            np.save(os.path.join(SPEAKERS_DIR, f"{user_id}.npy"), emb)
            os.remove(tmp_path)
            return jsonify({"status":"ok"})
        except Exception as e:
            return jsonify({"status":"error","message":str(e)}), 500

    @app.post('/speaker/verify')
    def speaker_verify():
        if not HAS_SPEAKER:
            return jsonify({"status":"error","message":"Resemblyzer no instalado"}), 400
        user_id = (request.form.get('user_id') or '').strip()
        if not user_id:
            return jsonify({"status":"error","message":"user_id requerido"}), 400
        ref_file = os.path.join(SPEAKERS_DIR, f"{user_id}.npy")
        if not os.path.isfile(ref_file):
            return jsonify({"status":"error","message":"Usuario no enrolado"}), 400
        if 'audio' not in request.files:
            return jsonify({"status":"error","message":"Archivo 'audio' requerido (wav mono 16k)"}), 400
        f = request.files['audio']
        tmp_path = os.path.join(SPEAKERS_DIR, f"tmpv_{user_id}.wav")
        f.save(tmp_path)
        try:
            wav = preprocess_wav(tmp_path)
            emb = encoder.embed_utterance(wav)
            ref = np.load(ref_file)
            # similitud coseno
            sim = float(np.dot(emb, ref) / (np.linalg.norm(emb)*np.linalg.norm(ref)))
            match = sim >= 0.75
            os.remove(tmp_path)
            return jsonify({"status":"ok","similaridad":sim,"match":match})
        except Exception as e:
            return jsonify({"status":"error","message":str(e)}), 500

    app.run(host='127.0.0.1', port=5000, debug=False)
