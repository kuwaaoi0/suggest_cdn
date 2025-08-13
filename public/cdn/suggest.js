/*! suggest.js — CDN配布用：属性 or クラス付与だけで検索サジェストを有効化
  必要な data 属性（script または input）:
    data-site-key="公開サイトキー"                        // 必須（script 側 or 各 input 側）
    data-api="https://api.example.com/api/suggest"        // 省略時 '/api/suggest'
    data-click="https://api.example.com/api/click"         // 省略時 API の /suggest を /click に置換
    data-api-key="公開APIキー"                             // 送信ヘッダ X-Suggest-Key に付与（推奨）
    data-min-chars="1"                                     // 何文字から発火
    data-debounce="120"                                    // 入力デバウンス(ms)
    data-open-on-focus="true|false"                        // フォーカス時に既存文字で開く
    data-token="短命JWT" / data-token-url="/path/to/token" // 任意（ユーザー別最適化）
  バインド対象:
    - <input data-suggest>                                // 推奨（属性）
    - <input class="my-suggest">                          // 互換（クラス）
    - script に data-suggest=".selector"                  // セレクタ指定で自動バインド
*/
(() => {
    const SCRIPT = document.currentScript;
    // ------- グローバル既定（script の data-*） -------
    const G = {
      api:       (SCRIPT?.dataset?.api || '/api/suggest').replace(/\/+$/, ''),
      click:      SCRIPT?.dataset?.click || null,
      siteKey:    SCRIPT?.dataset?.siteKey || '',
      apiKey:     SCRIPT?.dataset?.apiKey || '',
      minChars:  +(SCRIPT?.dataset?.minChars || 1),
      debounce:  +(SCRIPT?.dataset?.debounce || 120),
      openOnFocus: String(SCRIPT?.dataset?.openOnFocus || 'false').toLowerCase() === 'true',
      token:      SCRIPT?.dataset?.token || '',
      tokenUrl:   SCRIPT?.dataset?.tokenUrl || '',
      // 互換用（クラスでの自動バインド）
      inputClass: SCRIPT?.dataset?.inputClass || 'my-suggest',
      // セレクタでまとめてバインド（例: data-suggest=".header input[type=search]"）
      selector:   SCRIPT?.dataset?.suggest || null,
    };
    if (!G.click) G.click = G.api.replace(/\/suggest(?:\?.*)?$/i, '/click');

    if (!G.siteKey) {
      // input 側 data-site-key での上書きを許容するので警告のみ
      console.warn('[Suggest] data-site-key is missing on <script>; will look on each <input>.');
    }

    // ------- スタイル注入（最低限の見た目） -------
    const css = `
    .sg-wrap{position:relative}
    .sg-panel{position:absolute;left:0;right:0;top:100%;z-index:9999;margin-top:6px;
      background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.08);overflow:hidden}
    .sg-item{padding:10px 12px;display:flex;gap:10px;align-items:center;cursor:pointer}
    .sg-item:hover,.sg-item.active{background:#f1f5f9}
    .sg-label{flex:1}
    .sg-genre,.sg-badge{font-size:11px;padding:2px 6px;background:#e2e8f0;border-radius:999px;margin-left:6px}
    .sg-footer{padding:8px 12px;border-top:1px solid #e2e8f0;color:#94a3b8;font-size:12px;display:flex;justify-content:space-between}
    `;
    const st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

    // ------- ユーティリティ -------
    const esc = s => (s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const debounce = (fn, ms=150)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

    // 短命トークン（必要時のみ）
    let cachedToken = G.token;
    async function ensureToken(tokenUrl){
      if (cachedToken) return cachedToken;
      if (!tokenUrl) return '';
      try {
        const r = await fetch(tokenUrl, {credentials:'include'});
        if (!r.ok) return '';
        const j = await r.json().catch(()=>({}));
        cachedToken = j.token || '';
        return cachedToken;
      } catch { return ''; }
    }

    // ------- 1入力にバインド -------
    function bindInput(input, baseCfg){
      if (input.__sgAttached) return;
      input.__sgAttached = true;

      // 入力側 data-* で上書き
      const cfg = {
        api:        input.dataset.api        || baseCfg.api,
        click:      input.dataset.click      || baseCfg.click,
        siteKey:    input.dataset.siteKey    || baseCfg.siteKey,
        apiKey:     input.dataset.apiKey     || baseCfg.apiKey,
        minChars:  +(input.dataset.minChars  || baseCfg.minChars),
        debounce:  +(input.dataset.debounce  || baseCfg.debounce),
        openOnFocus: String(input.dataset.openOnFocus || String(baseCfg.openOnFocus)).toLowerCase()==='true',
        token:      input.dataset.token      || baseCfg.token,
        tokenUrl:   input.dataset.tokenUrl   || baseCfg.tokenUrl,
      };
      if (!cfg.click) cfg.click = cfg.api.replace(/\/suggest(?:\?.*)?$/i, '/click');
      if (!cfg.siteKey) { console.warn('[Suggest] site-key missing on input', input); }

      // ARIA
      input.setAttribute('autocomplete', 'off');
      input.setAttribute('role', 'combobox');
      input.setAttribute('aria-autocomplete', 'list');
      input.setAttribute('aria-expanded', 'false');

      // ラッパー & パネル
      if (!input.parentElement.classList.contains('sg-wrap')) {
        const wrap = document.createElement('div');
        wrap.className='sg-wrap';
        input.parentElement.insertBefore(wrap, input);
        wrap.appendChild(input);
      }
      const wrap = input.parentElement;
      const panel = document.createElement('div');
      panel.className='sg-panel';
      panel.hidden = true;
      wrap.appendChild(panel);

      let items=[], idx=-1, last='';

      function render(list){
        items = list.map(x => (typeof x==='string') ? {label:x} : x);
        if (!items.length){ panel.hidden=true; panel.innerHTML=''; input.setAttribute('aria-expanded','false'); return; }
        panel.innerHTML = items.map((it,i)=>(
          `<div class="sg-item" role="option" aria-selected="${i===idx}" data-i="${i}">
            <span class="sg-label">${esc(it.label || it.name || '')}</span>
            ${it.genre?`<span class="sg-genre">${esc(it.genre)}</span>`:''}
            ${it.source?`<span class="sg-badge">${esc(it.source)}</span>`:''}
          </div>`
        )).join('') + `<div class="sg-footer"><span>↑↓選択・Enter決定・Esc閉じる</span></div>`;
        panel.hidden = false;
        input.setAttribute('aria-expanded','true');
        panel.querySelectorAll('.sg-item').forEach(el=>{
          el.addEventListener('mouseenter', ()=>highlight(+el.dataset.i));
          el.addEventListener('mousedown',  (e)=>{ e.preventDefault(); choose(+el.dataset.i); });
        });
      }

      function highlight(i){
        idx = i;
        panel.querySelectorAll('.sg-item').forEach((el,j)=>el.classList.toggle('active', j===i));
      }

      function choose(i){
        const it = items[i]; if(!it) return;
        // クリック通知（Beacon→fetch）
        try{
          const payload = JSON.stringify({
            site_key: cfg.siteKey,
            keyword_id: it.id,
            kind: it.kind || 'shared',
            u: cfg.token || ''
          });
          if (navigator.sendBeacon) {
            navigator.sendBeacon(cfg.click, new Blob([payload], { type:'application/json' }));
          } else {
            fetch(cfg.click, { method:'POST', headers: headers(true), body: payload });
          }
        }catch{}
        input.value = it.label || it.name || '';
        close();
        input.dispatchEvent(new Event('change', {bubbles:true}));
        input.form?.dispatchEvent(new Event('submit', {bubbles:true}));
      }

      function close(){
        panel.hidden = true; panel.innerHTML = '';
        input.setAttribute('aria-expanded','false');
        idx = -1; items = [];
      }

      function headers(json=false){
        const h = {'Accept':'application/json'};
        if (cfg.apiKey) h['X-Suggest-Key'] = cfg.apiKey;
        if (json) h['Content-Type'] = 'application/json';
        return h;
      }

      const onInput = debounce(async ()=>{
        const q = input.value.trim();
        if (q.length < cfg.minChars || q===last){ close(); return; }
        last = q;
        try{
          const tk = cfg.token || await ensureToken(cfg.tokenUrl);
          const u = new URL(cfg.api, location.origin);
          u.searchParams.set('query', q);                 // ← バックエンド仕様に合わせる
          u.searchParams.set('site_key', cfg.siteKey);
          if (tk) u.searchParams.set('u', tk);
          const res = await fetch(u.toString(), { headers: headers(), mode:'cors', credentials:'omit' });
          if (!res.ok) throw new Error('HTTP '+res.status);
          const data = await res.json();
          // どちらの形でも対応：[] か { items: [] }
          const list = Array.isArray(data) ? data : (Array.isArray(data.items) ? data.items : []);
          render(list);
        }catch(e){
          console.warn('[Suggest] fetch failed:', e);
          close();
        }
      }, cfg.debounce);

      input.addEventListener('input', onInput);
      if (cfg.openOnFocus) input.addEventListener('focus', ()=>{ if (input.value.trim().length >= cfg.minChars) onInput(); });
      input.addEventListener('keydown', (e)=>{
        if (panel.hidden) return;
        if (e.key==='ArrowDown'){ e.preventDefault(); highlight(Math.min(idx+1, items.length-1)); }
        else if (e.key==='ArrowUp'){ e.preventDefault(); highlight(Math.max(idx-1, 0)); }
        else if (e.key==='Enter'){ if (idx>=0){ e.preventDefault(); choose(idx); } }
        else if (e.key==='Escape'){ close(); }
      });
      document.addEventListener('click', (e)=>{ if (!panel.contains(e.target) && e.target!==input) close(); });
    }

    // ------- 初期化（属性/クラス/セレクタすべて対応） -------
    function boot(){
      const inputs = new Set();
      // data-suggest 属性の input
      document.querySelectorAll('input[data-suggest]').forEach(el => inputs.add(el));
      // クラス（互換）
      document.querySelectorAll('input.'+G.inputClass).forEach(el => inputs.add(el));
      // セレクタ明示（script の data-suggest=".xxx"）
      if (G.selector) document.querySelectorAll(G.selector).forEach(el => inputs.add(el));

      inputs.forEach(el => bindInput(el, G));

      // 動的追加にも対応
      const mo = new MutationObserver((recs)=>{
        for (const r of recs) {
          r.addedNodes && r.addedNodes.forEach(n=>{
            if (n.nodeType!==1) return;
            if (n.matches?.('input[data-suggest], input.'+G.inputClass)) bindInput(n, G);
            n.querySelectorAll?.('input[data-suggest], input.'+G.inputClass).forEach(el=>bindInput(el, G));
            if (G.selector) n.querySelectorAll?.(G.selector).forEach(el=>bindInput(el, G));
          });
        }
      });
      mo.observe(document.documentElement, {childList:true, subtree:true});
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
  })();
