(function(){
  const POLL = 200;
  const FULL_INTERVAL = 12000;
  const RECHECK_DEBOUNCE = 600;
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
    o.preventCheckingIfPresent = '';
    return o;
  }

  function ensureIframeScript(iframeWin, done){
    if(iframeWin.Ed11y) return done();
    const parentScript = document.querySelector('script[src*="editoria11y.min.js"]');
    if(!parentScript){ return done(); }
    if(iframeWin.document.querySelector('script[src*="editoria11y.min.js"]')){
      let loops = 0; (function waitCtor(){
        try {
          if(!iframeWin.Ed11y && !iframeWin.__pbEd11yExportTried){
            iframeWin.__pbEd11yExportTried = true;
            const exp = iframeWin.document.createElement('script');
            exp.text = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y;}}catch(e){}';
            iframeWin.document.head.appendChild(exp);
          } else if(!iframeWin.Ed11y && iframeWin.__pbEd11yExportTried){
            const exp2 = iframeWin.document.createElement('script');
            exp2.text = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y;}}catch(e){}';
            iframeWin.document.head.appendChild(exp2);
          }
        } catch(e){/* noop */}
        if(iframeWin.Ed11y){ return done(); }
        if(window.Ed11y && !iframeWin.Ed11y){ iframeWin.Ed11y = window.Ed11y; return done(); }
        if(loops++ > 40){ log('Timeout waiting for Ed11y constructor'); return done(); }
        setTimeout(waitCtor, POLL);
      })();
      return;
    }
    const s = iframeWin.document.createElement('script');
    s.src = parentScript.src;
    s.onload = ()=>{ 
      let loops = 0; (function confirmCtor(){
        try {
          if(!iframeWin.Ed11y){
            const exp = iframeWin.document.createElement('script');
            exp.text = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y; console.log("[PB Ed11y] (iframe post-load) Ed11y exported");}}catch(e){}';
            iframeWin.document.head.appendChild(exp);
          }
        } catch(e){ /* ignore */ }
        if(iframeWin.Ed11y){ return done(); }
        if(window.Ed11y && !iframeWin.Ed11y){ iframeWin.Ed11y = window.Ed11y; log('Constructor Ed11y adopted post-load'); return done(); }
        if(loops++ > 25){ log('Ed11y not available after script load (fallback aborted)'); return done(); }
        setTimeout(confirmCtor, 120);
      })();
    };
    s.onerror = ()=>{ log('Error loading library inside iframe'); done(); };
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
  if(!w.Ed11y){ log('Ed11y not available after script load (instantiate)'); return; }
      if(w.__pbEd11yInstance){ return; }
      const opts = baseOptions();
      try {
        w.__pbEd11yInstance = new w.Ed11y(opts);
        w.__pbEd11yLastFull = Date.now();
        w.__pbEd11yVisible = true;
        log('Ed11y instance created in iframe', editor.id, opts);
      } catch(e){ log('Error creating Ed11y instance', e); }
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
    return false;
  }

  function toolbarEnsureConfig(cfg){
  // We could add the button here if we want
  }

  function domFallbackButton(editor){
  // Not used (for potential future use)
  }

  function hookEditor(editor){
    if(editorsInit.has(editor)) return; editorsInit.add(editor);
    editor.on('init', ()=>{
  instantiate(editor);
      const debounced = debounce(()=>recheck(editor,false), RECHECK_DEBOUNCE);
      ['Change','SetContent','KeyUp','Paste','Undo','Redo','NodeChange'].forEach(ev=>editor.on(ev, debounced));
    });
  }

  window.__pbEd11yForce = function(){
    const ed = window.tinymce?.get?.('content'); if(!ed) return log('Force: no editor');
    instantiate(ed);
  };

  domReady(()=>{
    waitMCE(()=>{
      window.tinymce.editors.forEach(hookEditor);
      new MutationObserver(()=>{ window.tinymce.editors.forEach(hookEditor); }).observe(document.body,{childList:true,subtree:true});
      window.tinymce.editors.forEach(ed=>{
        if(ed.initialized && !ed.__pbEd11ySetupDone){
          ed.__pbEd11ySetupDone = true;
          log('Editor ya inicializado detectado, aplicando fallback', ed.id);
          instantiate(ed);
        }
      });
    });
  });
  (function earlySetup(){
    if(!window.tinyMCEPreInit?.mceInit?.content) return setTimeout(earlySetup,50);
    const cfg = window.tinyMCEPreInit.mceInit.content;
    const orig = cfg.setup;
    cfg.setup = function(editor){
      if(typeof orig === 'function'){ try { orig(editor); } catch(e){ log('Error en setup original', e); } }
      hookEditor(editor);
    };
    toolbarEnsureConfig(cfg);
  })();
})();

try { if(typeof Ed11y !== 'undefined' && !window.Ed11y){ window.Ed11y = Ed11y; } } catch(e){}

function tryTogglePanel(w){
  try {
    const inst = w.__pbEd11yInstance;
    if(!inst) return;
    if(typeof inst.togglePanel === 'function') { inst.togglePanel(); return; }
    if(typeof inst.toggle === 'function') { inst.toggle(); return; }
    if(typeof inst.showPanel === 'function' && typeof inst.hidePanel === 'function') {
      const panel = w.document.querySelector('#ed11y-panel, .ed11y-panel');
      if(panel && panel.style.display === 'none'){ inst.showPanel(); return; }
      if(panel){ inst.hidePanel(); return; }
      inst.showPanel(); return;
    }
    
    const uiNodes = w.document.querySelectorAll('[id^="ed11y"], .ed11y-tip, .ed11y-panel');
    if(!uiNodes.length) return;
    const hidden = Array.from(uiNodes).every(n=> n.style.display === 'none');
    uiNodes.forEach(n=> n.style.display = hidden ? '' : 'none');
  } catch(e){ if(window.console) console.warn('[PB Ed11y] toggle fallback error', e); }
}
