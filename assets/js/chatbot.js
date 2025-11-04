(function(){
  const btn = document.createElement('button');
  btn.id = 'chatbot-toggle';
  btn.textContent = '💬';
  document.body.appendChild(btn);

  const box = document.createElement('div');
  box.id = 'chatbot-box';
  box.innerHTML = `
    <div class="cb-header">Asistente</div>
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

  function append(role, text){
    const p = document.createElement('div');
    p.className = 'msg ' + role;
    p.textContent = text;
    msgs.appendChild(p);
    msgs.scrollTop = msgs.scrollHeight;
  }

  btn.onclick = ()=> box.classList.toggle('open');
  sendBtn.onclick = async ()=>{
    const text = input.value.trim();
    if(!text) return;
    input.value='';
    append('user', text);
    const res = await fetch('chatbot.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({mensaje:text})
    });
    const data = await res.json();
    append('bot', data.respuesta + (data.sentimiento? ` [${data.sentimiento}]` : ''));
    if('speechSynthesis' in window){
      const u = new SpeechSynthesisUtterance(data.respuesta);
      u.lang = 'es-ES';
      window.speechSynthesis.speak(u);
    }
  };

  let rec;
  if('webkitSpeechRecognition' in window || 'SpeechRecognition' in window){
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    rec = new SR();
    rec.lang = 'es-ES';
    rec.continuous = false;
    rec.interimResults = false;
    rec.onresult = (e)=>{
      const text = e.results[0][0].transcript;
      input.value = text;
      sendBtn.click();
    };
  }
  voiceBtn.onclick = ()=>{ if(rec){ rec.start(); } };
})();
