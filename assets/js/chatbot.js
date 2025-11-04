(function(){
  const btn = document.createElement('button');
  btn.id = 'chatbot-toggle';
  btn.textContent = '💬';
  document.body.appendChild(btn);

  const box = document.createElement('div');
  box.id = 'chatbot-box';
  box.innerHTML = `
    <div class="cb-header">Asistente <span id="cb-status" style="font-weight:400;font-size:12px;opacity:.9"></span></div>
    <div class="cb-messages" id="cb-messages"></div>
    <div class="cb-input">
      <input id="cb-text" type="text" placeholder="Escribe tu mensaje..." />
      <button id="cb-voice" title="Hablar">🎤</button>
      <button id="cb-send">Enviar</button>
    </div>
  `;
  document.body.appendChild(box);

  const msgs = box.querySelector('#cb-messages');
  const input = box.querySelector('#cb-text');
  const sendBtn = box.querySelector('#cb-send');
  const voiceBtn = box.querySelector('#cb-voice');
  const statusEl = box.querySelector('#cb-status');

  function bubble(role, text){
    const row = document.createElement('div');
    row.className = 'msg ' + role;
    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    avatar.textContent = role === 'user' ? '👤' : '🐶';
    const b = document.createElement('div');
    b.className = 'bubble';
    b.textContent = text;
    if(role === 'user'){
      row.appendChild(b);
      row.appendChild(avatar);
    } else {
      row.appendChild(avatar);
      row.appendChild(b);
    }
    msgs.appendChild(row);
    msgs.scrollTop = msgs.scrollHeight;
    return { row, avatar, bubble: b };
  }
  function typing(){
    const t = bubble('bot','…');
    t.bubble.classList.add('typing');
    t.bubble.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
    return t;
  }

  btn.onclick = ()=> box.classList.toggle('open');
  sendBtn.onclick = async ()=>{
    const text = input.value.trim();
    if(!text) return;
    input.value='';
    bubble('user', text);
    try{
      const res = await fetch('chatbot.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({mensaje:text})
      });
      const wait = typing();
      const data = await res.json();
      wait.row.remove();
      bubble('bot', data.respuesta + (data.sentimiento? ` [${data.sentimiento}]` : ''));
      if('speechSynthesis' in window){
        try{ window.speechSynthesis.cancel(); }catch(_){ }
        const u = new SpeechSynthesisUtterance(data.respuesta);
        u.lang = 'es-ES';
        window.speechSynthesis.speak(u);
      }
    }catch(e){
      bubble('bot','Ocurrió un error al contactar al asistente.');
    }
  };

  let rec, recording = false, micActive = false, lastError = null;
  const hasSR = ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window);
  if(hasSR){
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    rec = new SR();
    rec.lang = 'es-ES';
    rec.continuous = true; // mantener escuchando hasta detener
    rec.interimResults = false;
    rec.onstart = ()=>{ recording = true; lastError = null; statusEl.textContent = '• Escuchando…'; voiceBtn.classList.add('active'); };
    rec.onend = ()=>{
      recording = false;
      voiceBtn.classList.remove('active');
      // Si el usuario quiere el mic activo y no hubo error crítico, reintentar
      if(micActive && !lastError){
        statusEl.textContent = '• Reanudando…';
        try{ rec.start(); }catch(e){ micActive=false; statusEl.textContent = 'No se pudo reanudar el micrófono'; }
        return;
      }
      // Fin de sesión por error o por stop manual
      if(lastError){ statusEl.textContent = lastError; }
      else { statusEl.textContent = ''; }
    };
    rec.onerror = (e)=>{
      recording = false;
      let msg = '';
      switch(e.error){
        case 'not-allowed':
        case 'service-not-allowed':
          msg = 'Permiso de micrófono denegado. Habilítalo para usar voz.'; break;
        case 'no-speech':
          msg = 'No se detectó voz. Intenta hablar más cerca del micrófono.'; break;
        case 'audio-capture':
          msg = 'No se detecta micrófono. Verifica tu dispositivo.'; break;
        case 'aborted':
          msg = 'Captura interrumpida.'; break;
        default:
          msg = 'Error del reconocimiento de voz.'; break;
      }
      lastError = msg;
      statusEl.textContent = msg;
      voiceBtn.classList.remove('active');
      // No forzar reintento automático si es not-allowed/audio-capture
      if(e.error==='not-allowed' || e.error==='service-not-allowed' || e.error==='audio-capture'){
        micActive = false;
      }
    };
    rec.onresult = (e)=>{
      const text = e.results[0][0].transcript;
      input.value = text;
      sendBtn.click();
    };
  } else {
    voiceBtn.disabled = true;
    voiceBtn.title = 'Reconocimiento de voz no soportado en este navegador';
  }
  voiceBtn.onclick = ()=>{
    if(!rec) return;
    // Comprobar contexto seguro (Chrome permite http://localhost)
    const isLocalhost = /^localhost(:\d+)?$/.test(location.host) || /^127\.0\.0\.1(?::\d+)?$/.test(location.host);
    if(!(window.isSecureContext || isLocalhost)){
      statusEl.textContent = 'Se requiere contexto seguro (HTTPS o localhost) para usar el micrófono.';
      return;
    }
    // Alternar estado deseado del micrófono
    micActive = !micActive;
    lastError = null;
    if(micActive){
      if(!recording){
        try{ rec.start(); }catch(e){ micActive=false; statusEl.textContent = 'No se pudo iniciar el micrófono'; }
      }
    } else {
      try{ rec.stop(); }catch(_){ micActive=false; }
    }
  };
})();
