/**
 * Adaptador simplificado para ejecutar Editoria11y dentro del iframe de TinyMCE.
 * Estrategia:
 *  1. Espera a TinyMCE.
 *  2. Inyecta (si hace falta) la librería original dentro del iframe.
 *  3. Instancia Ed11y con checkRoots=body#tinymce.
 *  4. Añade botón A11y para mostrar/ocultar y re‐chequear.
 *  5. Re‐ejecuta chequeos en eventos clave del editor.
 * No tocamos (ni neutralizamos) el DOM del admin ni el plugin original.
 */
(function(){
  const POLL = 200;
  const FULL_INTERVAL = 12000; // ms entre chequeos completos automáticos
  const RECHECK_DEBOUNCE = 600; // ms para reagrupar eventos de edición
  const editorsInit = new WeakSet();
  function log(...a){ if(window.console) console.log('[PB Ed11y]', ...a); }
  function domReady(cb){ if(document.readyState !== 'loading') cb(); else document.addEventListener('DOMContentLoaded', cb); }
  function waitMCE(cb){ if(window.tinymce && window.tinymce.editors.length) cb(); else setTimeout(()=>waitMCE(cb), POLL); }
  function debounce(fn, ms){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); }; }

  function baseOptions(){
    let o = (window.ed11yVars && window.ed11yVars.options) ? JSON.parse(JSON.stringify(window.ed11yVars.options)) : {};
    o.checkRoots = 'body#tinymce';
    o.ignoreElements = (o.ignoreElements || '') + ', #wpadminbar *';
    o.liveCheck = o.liveCheck || 'all';
    o.showResults = true;
    o.buttonZIndex = 99999;
    o.inlineAlerts = false;
    o.customTests = o.customTests || 0;
    // Evitar que no corra por presencia en admin
    o.preventCheckingIfPresent = '';
    return o;
  }

  function ensureIframeScript(iframeWin, done){
    if(iframeWin.Ed11y) return done();
    const parentScript = document.querySelector('script[src*="editoria11y.min.js"]');
    if(!parentScript){ log('No se encontró script editoria11y.min.js en parent'); return done(); }
    // Evitar doble inserción
    if(iframeWin.document.querySelector('script[src*="editoria11y.min.js"]')){
      let loops = 0; (function waitCtor(){
        // Intentar exportar binding léxico dentro del iframe si aún no está en window
        try {
          if(!iframeWin.Ed11y && !iframeWin.__pbEd11yExportTried){
            iframeWin.__pbEd11yExportTried = true;
            const exp = iframeWin.document.createElement('script');
            exp.text = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y; console.log("[PB Ed11y] (iframe) Ed11y exportado a window");}}catch(e){}';
            iframeWin.document.head.appendChild(exp);
          } else if(!iframeWin.Ed11y && iframeWin.__pbEd11yExportTried){
            // Reintentos adicionales por si el script principal aún no creó el binding
            const exp2 = iframeWin.document.createElement('script');
            exp2.text = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y; console.log("[PB Ed11y] (iframe retry) Ed11y exportado a window");}}catch(e){}';
            iframeWin.document.head.appendChild(exp2);
          }
        } catch(e){/* noop */}
        if(iframeWin.Ed11y){ return done(); }
        // Copiar desde parent si el parent ya lo exportó
        if(window.Ed11y && !iframeWin.Ed11y){ iframeWin.Ed11y = window.Ed11y; log('Constructor Ed11y adoptado desde parent (reuse)'); return done(); }
        if(loops++ > 40){ log('Timeout esperando constructor dentro iframe'); return done(); }
        setTimeout(waitCtor, POLL);
      })();
      return;
    }
    const s = iframeWin.document.createElement('script');
    s.src = parentScript.src;
    s.onload = ()=>{ 
      log('Librería Ed11y cargada dentro iframe');
      let loops = 0; (function confirmCtor(){
        // Exportar binding léxico tras carga (la clase usa "class Ed11y" que no añade window.Ed11y automáticamente)
        try {
          if(!iframeWin.Ed11y){
            const exp = iframeWin.document.createElement('script');
            exp.text = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y; console.log("[PB Ed11y] (iframe post-load) Ed11y exportado a window");}}catch(e){}';
            iframeWin.document.head.appendChild(exp);
          }
        } catch(e){ /* ignore */ }
        if(iframeWin.Ed11y){ return done(); }
        if(window.Ed11y && !iframeWin.Ed11y){ iframeWin.Ed11y = window.Ed11y; log('Constructor Ed11y adoptado post-carga'); return done(); }
        if(loops++ > 25){ log('Ed11y aún no disponible tras carga de script (fallback desistido)'); return done(); }
        setTimeout(confirmCtor, 120);
      })();
    };
    s.onerror = ()=>{ log('Fallo cargando librería dentro iframe'); done(); };
    iframeWin.document.head.appendChild(s);
  }

  function ensureIframeCss(iframeDoc){
    const parentCss = document.querySelector('link[href*="editoria11y"][href$=".css"], link[href*="editoria11y.min.css"]');
    if(parentCss && !iframeDoc.querySelector('link[href="'+parentCss.href+'"]')){
      const l = iframeDoc.createElement('link'); l.rel='stylesheet'; l.href=parentCss.href; iframeDoc.head.appendChild(l);
    }
  }

  function instantiate(editor){
    const iframe = editor.iframeElement || document.getElementById(editor.id + '_ifr');
    if(!iframe || !iframe.contentWindow || !iframe.contentDocument) return;
    const w = iframe.contentWindow;
    const d = w.document;
    if(!d.body) return setTimeout(()=>instantiate(editor), POLL);
    if(!/\btinymce\b/.test(d.body.id)) d.body.id = 'tinymce';
    ensureIframeCss(d);
    ensureIframeScript(w, ()=>{
  if(!w.Ed11y){ log('Ed11y aún no disponible tras carga de script (instantiate)'); return; }
      if(w.__pbEd11yInstance){ return; }
      const opts = baseOptions();
      try {
        w.__pbEd11yInstance = new w.Ed11y(opts);
        w.__pbEd11yLastFull = Date.now();
        log('Instancia Ed11y creada en iframe', editor.id, opts);
      } catch(e){ log('Error creando instancia Ed11y', e); }
    });
  }

  function recheck(editor, forceFull=false){
    const iframe = editor.iframeElement || document.getElementById(editor.id + '_ifr');
    if(!iframe || !iframe.contentWindow) return;
    const w = iframe.contentWindow; const inst = w.__pbEd11yInstance; const C = w.Ed11y;
    if(!inst || !C) return;
    try {
      if(forceFull || Date.now() - (w.__pbEd11yLastFull || 0) > FULL_INTERVAL){
        if(!C.running && typeof C.checkAll === 'function'){ C.checkAll(); w.__pbEd11yLastFull = Date.now(); }
      } else if(!C.running && typeof C.incrementalCheck === 'function') {
        C.incrementalCheck();
      } else if(!C.running && typeof C.checkAll === 'function') {
        C.checkAll(); w.__pbEd11yLastFull = Date.now();
      }
    } catch(e){ log('Error recheck', e); }
  }

  function registerButton(editor){
    try {
      if(editor.__pbEd11yButtonReg) return true;
      const toggleHandler = ()=>{
        const iframe = editor.iframeElement || document.getElementById(editor.id + '_ifr');
        if(!iframe) return; const w = iframe.contentWindow; const doc = iframe.contentDocument; if(!doc) return;
        // Crear instancia si aún no existe
        if(!w.__pbEd11yInstance){
          instantiate(editor);
          // retraso breve para permitir construcción interna antes de forzar primer check
          setTimeout(()=>{ recheck(editor,true); tryTogglePanel(w); }, 600);
          return;
        }
        // Asegurar script cargado
        if(!w.Ed11y){ return; }
        // Reutilizar API interna si existe
        tryTogglePanel(w);
        recheck(editor,true);
      };
      if(editor.ui && editor.ui.registry){ // TinyMCE 5/6
        if(!editor.ui.registry.getAll().buttons.ed11yToggle){
          editor.ui.registry.addButton('ed11yToggle', { text:'A11y', tooltip:'Accessibility checker', onAction: toggleHandler });
          log('Botón registrado via ui.registry');
        }
        editor.__pbEd11yButtonReg = true;
        return true;
      } else if(typeof editor.addButton === 'function'){ // TinyMCE 4
        editor.addButton('ed11yToggle', { text:'A11y', tooltip:'Accessibility checker', onclick: toggleHandler });
        editor.__pbEd11yButtonReg = true;
        log('Botón registrado via addButton');
        return true;
      }
    } catch(e){ log('Fallo registerButton', e); }
    return false;
  }

  function toolbarEnsureConfig(cfg){
    const tb = cfg.toolbar1 || cfg.toolbar || '';
    if(!/\bed11yToggle\b/.test(tb)){
      if(typeof cfg.toolbar1 !== 'undefined'){
        cfg.toolbar1 = tb ? tb + ',ed11yToggle' : 'ed11yToggle';
      } else if(cfg.toolbar){
        cfg.toolbar += ' ed11yToggle';
      } else {
        cfg.toolbar1 = 'ed11yToggle';
      }
    }
  }

  function domFallbackButton(editor){
    try {
      if(document.querySelector('.mce-btn.ed11y-fallback')) return;
      const toolbars = document.querySelectorAll('.mce-toolbar-grp .mce-toolbar:last-child .mce-btn-group');
      const targetGroup = toolbars[toolbars.length -1];
      if(!targetGroup) return;
      const wrapper = document.createElement('div');
      wrapper.className = 'mce-widget mce-btn ed11y-fallback';
      wrapper.setAttribute('tabindex','-1'); wrapper.setAttribute('role','button');
      const btn = document.createElement('button');
      btn.type='button'; btn.textContent='A11y';
  btn.onclick=()=>{ registerButton(editor); editor.execCommand && editor.focus(); const iframe = document.getElementById(editor.id + '_ifr'); if(!iframe) return; const w = iframe.contentWindow; if(!w.__pbEd11yInstance){ instantiate(editor); setTimeout(()=>{ recheck(editor,true); tryTogglePanel(w); },600); } else { tryTogglePanel(w); recheck(editor,true); } };
      wrapper.appendChild(btn);
      targetGroup.appendChild(wrapper);
      log('Botón fallback DOM insertado');
    } catch(e){ log('Fallo domFallbackButton', e); }
  }

  function hookEditor(editor){
    if(editorsInit.has(editor)) return; editorsInit.add(editor);
    // Intentar registrar botón inmediatamente (setup se ejecuta antes de init)
    registerButton(editor);
    editor.on('init', ()=>{
      instantiate(editor);
      // Asegurar nuevamente botón; si falla usar fallback DOM tras un pequeño delay.
      if(!registerButton(editor)) setTimeout(()=>domFallbackButton(editor), 800);
      // Reintentos adicionales si todavía no aparece (problemas de timing TinyMCE 4 WordPress)
      [1500, 3000].forEach(delay=>{
        setTimeout(()=>{
          try {
            let found = !!document.querySelector('.mce-btn.ed11y-fallback');
            if(!found){
              const btns = document.querySelectorAll('.mce-btn button, .mce-widget button');
              found = Array.from(btns).some(b=> b.textContent.trim() === 'A11y');
            }
            if(!found) domFallbackButton(editor);
          } catch(e){ log('Error en detección reintento botón', e); }
        }, delay);
      });
      const debounced = debounce(()=>recheck(editor,false), RECHECK_DEBOUNCE);
      ['Change','SetContent','KeyUp','Paste','Undo','Redo','NodeChange'].forEach(ev=>editor.on(ev, debounced));
    });
  }

  // Ya no forzamos añadir un plugin TinyMCE (evitamos petición 404 a plugin.js). Solo añadimos el botón en init.

  // Exponer helper de fuerza manual (debug).
  window.__pbEd11yForce = function(){
    const ed = window.tinymce?.get?.('content'); if(!ed) return log('Force: sin editor');
    instantiate(ed);
  };

  domReady(()=>{
    waitMCE(()=>{
      window.tinymce.editors.forEach(hookEditor);
      new MutationObserver(()=>{ window.tinymce.editors.forEach(hookEditor); }).observe(document.body,{childList:true,subtree:true});
      // Fallback: si algún editor ya estaba inicializado antes de que engancháramos hookEditor.
      window.tinymce.editors.forEach(ed=>{
        if(ed.initialized && !ed.__pbEd11ySetupDone){
          ed.__pbEd11ySetupDone = true;
          log('Editor ya inicializado detectado, aplicando fallback', ed.id);
          // Intentar registrar botón (si TinyMCE 4 addButton aún disponible post-init)
          if(!registerButton(ed)){
            // Si no fue posible (porque el toolbar ya se construyó y addButton no genera DOM) usar fallback manual tras breve espera
            setTimeout(()=>domFallbackButton(ed), 400);
          }
          instantiate(ed);
        }
      });
    });
  });
  // Ajustar config temprano para futuros editores (asegura botón si el script carga antes de crear el editor)
  (function earlySetup(){
    if(!window.tinyMCEPreInit?.mceInit?.content) return setTimeout(earlySetup,50);
    const cfg = window.tinyMCEPreInit.mceInit.content;
    const orig = cfg.setup;
    cfg.setup = function(editor){
      if(typeof orig === 'function'){ try { orig(editor); } catch(e){ log('Error en setup original', e); } }
      hookEditor(editor); // registrará eventos y añadirá botón en init
    };
    toolbarEnsureConfig(cfg);
  })();
  log('Adaptador TinyMCE (simple) cargado');
})();

// Intentar exportar Ed11y (binding global léxico) al objeto window del parent si ya está definido pero no accesible como propiedad.
try { if(typeof Ed11y !== 'undefined' && !window.Ed11y){ window.Ed11y = Ed11y; console.log('[PB Ed11y] (parent) Ed11y exportado a window'); } } catch(e){}

// Intenta alternar el panel / UI de Ed11y usando su API si existe; si no, hace fallback ocultando contenedores.
function tryTogglePanel(w){
  try {
    const inst = w.__pbEd11yInstance;
    if(!inst) return;
    // Métodos potenciales según versiones (heurística)
    if(typeof inst.togglePanel === 'function') { inst.togglePanel(); return; }
    if(typeof inst.toggle === 'function') { inst.toggle(); return; }
    if(typeof inst.showPanel === 'function' && typeof inst.hidePanel === 'function') {
      const panel = w.document.querySelector('#ed11y-panel, .ed11y-panel');
      if(panel && panel.style.display === 'none'){ inst.showPanel(); return; }
      if(panel){ inst.hidePanel(); return; }
      inst.showPanel(); return;
    }
    // Fallback genérico: alternar display de nodos UI conocidos
    const uiNodes = w.document.querySelectorAll('[id^="ed11y"], .ed11y-tip, .ed11y-panel');
    if(!uiNodes.length) return;
    const hidden = Array.from(uiNodes).every(n=> n.style.display === 'none');
    uiNodes.forEach(n=> n.style.display = hidden ? '' : 'none');
  } catch(e){ if(window.console) console.warn('[PB Ed11y] toggle fallback error', e); }
}
