/*! suggest.js — CDN配布用：クラス付与だけで検索サジェストを有効化
  必要なdata属性:
    data-site-key="公開サイトキー"                        // 必須
    data-api="https://api.example.com/api/suggest"        // 省略時 '/api/suggest'
    data-click="https://api.example.com/api/click"         // 省略時 API の /suggest を /click に置換
    data-api-key="公開APIキー"                             // 送信ヘッダ X-Suggest-Key に付与（推奨）
    data-input-class="my-suggest"                          // 省略時 'my-suggest'
    data-user-token="短命JWT"                              // 任意（ユーザー別出し分け）
    data-debounce="120"                                    // 任意(ms)
    data-min-chars="1"                                     // 任意
    data-open-on-focus="true"                              // 任意: フォーカス時に既存文字で開く
*/
(() => {
    const SCRIPT = document.currentScript;

    // ---- 設定値 ----
    const SITE_KEY     = SCRIPT?.dataset?.siteKey || '';
    const API          = (SCRIPT?.dataset?.api || '/api/suggest').replace(/\/+$/, '');
    const CLICK        = SCRIPT?.dataset?.click || API.replace(/\/suggest(?:\?.*)?$/i, '/click');
    const API_KEY      = SCRIPT?.dataset?.apiKey || '';
    const CLASS        = SCRIPT?.dataset?.inputClass || 'my-suggest';
    const USER_TOKEN   = SCRIPT?.dataset?.userToken || '';
    const DEBOUNCE_MS  = Number(SCRIPT?.dataset?.debounce || 120);
    const MIN_CHARS    = Math.max(1, Number(SCRIPT?.dataset?.minChars || 1));
    const OPEN_ON_FOCUS= String(SCRIPT?.dataset?.openOnFocus || 'false').toLowerCase() === 'true';

    if (!SITE_KEY) console.warn('[Suggest] data-site-key is missing');

    const COMMON_HEADERS = { 'Accept': 'application/json' };
    if (API_KEY) COMMON_HEADERS['X-Suggest-Key'] = API_KEY;

    // ---- ユーティリティ ----
    const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
    const escHtml = (s) => s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    const winX = () => ('scrollX' in window ? window.scrollX : window.pageXOffset);
    const winY = () => ('scrollY' in window ? window.scrollY : window.pageYOffset);
    const uid = (() => { let i = 0; return (p='sg') => `${p}-${Date.now().toString(36)}-${(++i).toString(36)}`; })();

    function buildUrl(base, params) {
      let u;
      try { u = new URL(base, location.href); }
      catch { const a = document.createElement('a'); a.href = base; u = new URL(a.href); }
      Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') u.searchParams.set(k, v); });
      return u.toString();
    }

    // ---- メイン：入力要素にバインド ----
    function attach(input) {
      if (input.__sgAttached) return;
      input.__sgAttached = true;

      let box = null;
      let items = [];
      let activeIndex = -1;
      const listId = uid('sglist');

      // ARIA
      input.setAttribute('autocomplete', 'off');
      input.setAttribute('role', 'combobox');
      input.setAttribute('aria-autocomplete', 'list');
      input.setAttribute('aria-expanded', 'false');
      input.setAttribute('aria-owns', listId);

      // ---- ドロップダウン ----
      function ensureBox() {
        if (box) return box;
        box = document.createElement('div');
        box.className = 'sg-list';
        box.id = listId;
        box.setAttribute('role', 'listbox');
        box.style.position = 'absolute';
        box.style.zIndex = '9999';
        document.body.appendChild(box);
        position();
        return box;
      }

      function position() {
        if (!box) return;
        const r = input.getBoundingClientRect();
        box.style.left = (winX() + r.left) + 'px';
        box.style.top  = (winY() + r.bottom + 4) + 'px';
        box.style.width= r.width + 'px';
      }

      function close() {
        if (box) { box.remove(); box = null; }
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1; items = [];
      }

      // ---- レンダリング ----
      function render() {
        if (!items.length) { close(); return; }
        ensureBox();
        box.innerHTML = '';
        items.forEach((it, idx) => {
          const opt = document.createElement('div');
          opt.className = 'sg-item';
          opt.setAttribute('role', 'option');
          opt.setAttribute('id', `${listId}-opt-${idx}`);
          opt.setAttribute('aria-selected', String(idx === activeIndex));
          opt.innerHTML = escHtml(it.label) + (it.genre ? ` <span class="sg-genre">${escHtml(it.genre)}</span>` : '');
          opt.addEventListener('mousedown', (e) => { e.preventDefault(); pick(idx); });
          box.appendChild(opt);
        });
        input.setAttribute('aria-expanded', 'true');
      }

      // ---- 確定（クリック通知→反映） ----
      function pick(idx) {
        const it = items[idx]; if (!it) return;

        // クリック通知（Beacon → fetch）
        try {
          const payload = JSON.stringify({
            site_key: SITE_KEY,
            keyword_id: it.id,
            kind: it.kind || 'shared', // APIが返す 'user' | 'shared'
            u: USER_TOKEN || ''
          });
          if (navigator.sendBeacon) {
            navigator.sendBeacon(CLICK, new Blob([payload], { type: 'application/json' }));
          } else {
            fetch(CLICK, { method:'POST', headers:{ ...COMMON_HEADERS, 'Content-Type':'application/json' }, body: payload });
          }
        } catch {}

        input.value = it.label;
        close();
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.form?.dispatchEvent(new Event('submit', { bubbles: true }));
      }

      // ---- API呼び出し ----
      const fetchSuggest = debounce(async () => {
        const q = input.value.trim();
        if (q.length < MIN_CHARS) { close(); return; }

        const url = buildUrl(API, { query: q, site_key: SITE_KEY, u: USER_TOKEN || '' });
        try {
          const res = await fetch(url, { headers: COMMON_HEADERS, credentials: 'omit', mode: 'cors' });
          if (!res.ok) throw new Error(`HTTP ${res.status}`);
          const data = await res.json();
          items = Array.isArray(data.items) ? data.items.slice(0, 10) : [];
          if (items.length) { ensureBox(); position(); render(); }
          else { close(); }
        } catch (e) {
          console.warn('[Suggest] fetch failed:', e);
          close();
        }
      }, DEBOUNCE_MS);

      // ---- イベント ----
      input.addEventListener('input', fetchSuggest);
      if (OPEN_ON_FOCUS) input.addEventListener('focus', () => { if (input.value.trim().length >= MIN_CHARS) fetchSuggest(); });
      input.addEventListener('blur', () => setTimeout(close, 100));

      input.addEventListener('keydown', (e) => {
        if (!box) return;
        if (e.key === 'ArrowDown') { activeIndex = Math.min(activeIndex + 1, items.length - 1); render(); e.preventDefault(); }
        else if (e.key === 'ArrowUp') { activeIndex = Math.max(activeIndex - 1, 0); render(); e.preventDefault(); }
        else if (e.key === 'Enter') { if (activeIndex >= 0) { pick(activeIndex); e.preventDefault(); } }
        else if (e.key === 'Escape') { close(); }
      });

      const onPos = () => box && position();
      window.addEventListener('resize', onPos);
      window.addEventListener('scroll', onPos, true);

      document.addEventListener('mousedown', (ev) => {
        if (!box) return;
        if (ev.target === input || box.contains(ev.target)) return;
        close();
      });
    }

    // ---- 初期化 ----
    function boot() {
      document.querySelectorAll('input.' + CLASS).forEach(attach);

      // 動的追加にも対応
      const mo = new MutationObserver((recs) => {
        for (const r of recs) {
          r.addedNodes && r.addedNodes.forEach((n) => {
            if (n.nodeType === 1) {
              if (n.matches?.('input.' + CLASS)) attach(n);
              n.querySelectorAll?.('input.' + CLASS).forEach(attach);
            }
          });
        }
      });
      mo.observe(document.documentElement, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
  })();
