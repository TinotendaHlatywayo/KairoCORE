@if(session('show_kairo_splash'))
@php
    $splashData = session('kairo_splash_data', []);
    $userName = $splashData['name'] ?? auth()->user()?->name ?? '';
    $schoolName = $splashData['school'] ?? 'Kairo CORE';
    $logoUrl = $splashData['logo'] ?? platform_logo_url();
@endphp
<style>
/* ============ KAIROCORE SPLASH v5 ============ */
.ks{
  --navy:#1f2d45; --coral:#ef6351; --grey:#eaeef4;
  --w:min(640px,90vw);
  --step:calc(var(--w) / 7);
  --skip:0s;
  position:fixed;inset:0;z-index:99999;display:none;overflow:hidden;color:var(--navy);
  font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
  background:#f3f5f9;
}
.ks.on{display:block}
.ks.quick{--skip:1.2s}

.ks-door{position:absolute;top:0;bottom:0;width:50.2%;
  background:radial-gradient(120% 90% at 50% 42%,#fff 0%,#fff 38%,var(--grey) 100%);background-size:200% 100%}
.ks-door.l{left:0;background-position:left}
.ks-door.r{right:0;background-position:right}
.ks.exit .ks-door.l{box-shadow:8px 0 40px rgba(31,45,69,.08)}
.ks.exit .ks-door.r{box-shadow:-8px 0 40px rgba(31,45,69,.08)}
.ks-seam{position:absolute;left:50%;top:0;bottom:0;width:2px;margin-left:-1px;background:var(--coral);transform:scaleY(0);z-index:2}

.ks-content{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;isolation:isolate}

.ks-logo-wrap{position:relative;width:min(400px,68vw);aspect-ratio:1709/755;margin-bottom:38px}
.ks-logo{display:block;width:100%;height:100%;object-fit:contain}
.ks-glint{position:absolute;inset:0;overflow:hidden;pointer-events:none;
  -webkit-mask:var(--logo-url) center/100% 100% no-repeat;mask:var(--logo-url) center/100% 100% no-repeat}
.ks-glint::before{content:"";position:absolute;top:0;bottom:0;left:0;width:45%;opacity:0;transform:translateX(-120%);
  background:linear-gradient(100deg,transparent,rgba(255,255,255,.9),transparent)}

.ks.has-school .ks-logo-wrap{margin-bottom:12px}
.ks-school{position:relative;max-width:min(600px,90vw);margin:0 0 38px;padding:0 0 15px;text-align:center;
  font-size:clamp(24px,5.4vw,38px);font-weight:700;line-height:1.15;letter-spacing:.01em;text-wrap:balance;color:var(--navy)}
.ks-school[hidden]{display:none}
.ks-school .w{display:inline-block;white-space:nowrap}
.ks-school .c{display:inline-block}
.ks-school::after{content:"";position:absolute;left:50%;bottom:0;width:68px;height:3px;margin-left:-34px;border-radius:3px;
  background:linear-gradient(90deg,transparent,var(--coral) 25%,var(--coral) 75%,transparent)}

.ks-row{position:relative;width:var(--w);height:52px}
.ks-orbit,.ks-ring{position:absolute;left:50%;top:26px;border-radius:50%;z-index:-1}
.ks-orbit{border:1px solid rgba(31,45,69,.07)}
.ks-orbit.a{width:300px;height:300px;margin:-150px 0 0 -150px}
.ks-orbit.b{width:520px;height:520px;margin:-260px 0 0 -260px}
.ks-ring{width:440px;height:440px;margin:-220px 0 0 -220px;border:1.5px solid var(--coral);opacity:0}
.ks-line{position:absolute;left:0;right:0;top:25px;height:2px;opacity:.55;
  background:linear-gradient(90deg,transparent,var(--coral) 12%,var(--coral) 88%,transparent)}
.ks-node{position:absolute;left:50%;top:26px;width:14px;height:14px;margin:-7px 0 0 -7px;border-radius:50%;background:var(--coral)}
.ks-mod{position:absolute;left:50%;top:0;width:52px;margin-left:-26px;transform:translateX(var(--dx))}
.ks-disc{width:52px;height:52px;border-radius:50%;background:#fff;color:var(--navy);display:grid;place-items:center;
  border:1.5px solid rgba(239,99,81,.55);box-shadow:0 6px 18px rgba(31,45,69,.08)}
.ks-disc svg{width:24px;height:24px;fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}
.ks-lab{position:absolute;top:60px;left:50%;transform:translateX(-50%);font-size:11px;font-weight:500;white-space:nowrap;opacity:.6;letter-spacing:-.01em}

.ks-msg{position:relative;width:min(420px,86vw);height:46px;margin-top:44px;text-align:center}
.ks-tag,.ks-hello{position:absolute;left:0;right:0;top:0}
.ks-tag{font-size:15px;font-weight:500;opacity:0}
.ks-hello b{display:block;font-size:clamp(16px,2.6vmin,19px);font-weight:600}
.ks-hello span{display:block;margin-top:4px;font-size:13px;opacity:.5}

.ks-skip{position:absolute;right:20px;bottom:20px;padding:8px 14px;border:1px solid rgba(31,45,69,.18);border-radius:999px;
  background:rgba(255,255,255,.7);color:var(--navy);font-size:13px;font-weight:500;line-height:1;font-family:inherit;cursor:pointer}
.ks-skip:hover{background:#fff}
.ks-skip:focus-visible{outline:2px solid var(--coral);outline-offset:3px}

.ks.run .ks-orbit{animation:ks-orbit .8s ease calc(.2s - var(--skip)) both}
.ks.run .ks-line{animation:ks-line .5s cubic-bezier(.3,.7,.2,1) calc(.1s - var(--skip)) both, ks-dimline .5s ease calc(1.6s - var(--skip)) forwards}
.ks.run .ks-node{animation:ks-nodein .35s cubic-bezier(.2,1.5,.4,1) calc(0s - var(--skip)) both, ks-pulse .6s ease calc(1.6s - var(--skip))}
.ks.run .ks-ring{animation:ks-ring 1s cubic-bezier(.2,.7,.2,1) calc(1.6s - var(--skip)) both}
.ks.run .ks-mod{animation:ks-emerge .5s cubic-bezier(.2,.9,.2,1) calc(.6s + var(--k) * .1s - var(--skip)) both}
.ks.run .ks-disc{animation:ks-lit .5s ease calc(.98s + var(--k) * .1s - var(--skip)) both}
.ks.run .ks-lab{animation:ks-lab .3s ease calc(.95s + var(--k) * .1s - var(--skip)) both}
.ks.run .ks-logo{animation:ks-logo .75s cubic-bezier(.2,.8,.2,1) calc(1.65s - var(--skip)) both}
.ks.run .ks-glint::before{animation:ks-glint .75s ease-in-out calc(2.2s - var(--skip)) both}
.ks.run .ks-school .c{animation:ks-char .55s cubic-bezier(.2,.8,.2,1) calc(var(--d) * 1s - var(--skip)) both}
.ks.run .ks-school::after{animation:ks-rule .6s cubic-bezier(.3,.7,.2,1) calc(2.3s - var(--skip)) both}
.ks.run .ks-tag{animation:ks-tag .75s ease calc(1.9s - var(--skip)) both}
.ks.run .ks-hello{animation:ks-up .45s ease calc(2.6s - var(--skip)) both}
.ks.run .ks-skip{animation:ks-fade .4s ease calc(.6s - var(--skip)) both}
.ks.exit .ks-content{animation:ks-fadeout .22s ease forwards}
.ks.exit .ks-seam{animation:ks-seam .65s ease forwards}
.ks.exit .ks-door.l{animation:ks-door-l .5s cubic-bezier(.76,0,.24,1) .2s forwards}
.ks.exit .ks-door.r{animation:ks-door-r .5s cubic-bezier(.76,0,.24,1) .2s forwards}

@keyframes ks-orbit{from{opacity:0;transform:scale(.8)}to{opacity:1;transform:none}}
@keyframes ks-line{from{transform:scaleX(0)}to{transform:scaleX(1)}}
@keyframes ks-dimline{to{opacity:.3}}
@keyframes ks-nodein{from{opacity:0;transform:scale(.2)}to{opacity:1;transform:none}}
@keyframes ks-pulse{50%{transform:scale(1.7)}}
@keyframes ks-ring{0%{opacity:.7;transform:scale(.05)}100%{opacity:0;transform:scale(1)}}
@keyframes ks-emerge{from{opacity:0;transform:translateX(0) scale(.3)}to{opacity:1;transform:translateX(var(--dx)) scale(1)}}
@keyframes ks-lit{
  0%{border-color:rgba(31,45,69,.14);color:var(--navy);box-shadow:0 6px 18px rgba(31,45,69,.08)}
  45%{border-color:var(--coral);color:var(--coral);box-shadow:0 0 0 7px rgba(239,99,81,.16),0 6px 18px rgba(31,45,69,.08)}
  100%{border-color:rgba(239,99,81,.55);color:var(--navy);box-shadow:0 6px 18px rgba(31,45,69,.08)}}
@keyframes ks-lab{from{opacity:0}to{opacity:.6}}
@keyframes ks-logo{from{opacity:0;transform:translateY(34px) scale(.96)}to{opacity:1;transform:none}}
@keyframes ks-glint{0%{opacity:0;transform:translateX(-120%)}15%,85%{opacity:1}100%{opacity:0;transform:translateX(320%)}}
@keyframes ks-char{from{opacity:0;transform:translateY(14px) scale(.92)}to{opacity:1;transform:none}}
@keyframes ks-rule{from{opacity:0;transform:scaleX(0)}to{opacity:1;transform:scaleX(1)}}
@keyframes ks-tag{0%{opacity:0;transform:translateY(6px)}27%,73%{opacity:.8;transform:none}100%{opacity:0}}
@keyframes ks-up{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
@keyframes ks-fade{from{opacity:0}to{opacity:1}}
@keyframes ks-fadeout{to{opacity:0}}
@keyframes ks-seam{0%{transform:scaleY(0);opacity:1}35%{transform:scaleY(1);opacity:1}100%{transform:scaleY(1);opacity:0}}
@keyframes ks-door-l{to{transform:translateX(-101%)}}
@keyframes ks-door-r{to{transform:translateX(101%)}}
</style>

<div class="ks" id="kairo-splash" role="status" aria-live="polite" aria-label="Signing you in to Kairo CORE">
  <div class="ks-door l"></div><div class="ks-door r"></div>
  <div class="ks-content">
    <div class="ks-logo-wrap">
      <img class="ks-logo" id="ks-logo" alt="Kairo CORE" decoding="async">
      <div class="ks-glint" aria-hidden="true"></div>
    </div>
    <h2 class="ks-school" id="ks-school" hidden></h2>

    <div class="ks-row" aria-hidden="true">
      <i class="ks-orbit a"></i><i class="ks-orbit b"></i><i class="ks-ring"></i>
      <i class="ks-line"></i><i class="ks-node"></i>

      <div class="ks-mod" style="--k:2;--dx:calc(var(--step) * -2)"><div class="ks-disc">
        <svg viewBox="0 0 24 24"><path d="M2.5 9.5 12 5l9.5 4.5L12 14z"/><path d="M6.5 11.8V16c0 1.4 2.5 2.8 5.5 2.8s5.5-1.4 5.5-2.8v-4.2"/><path d="M21.5 9.5V14"/></svg></div>
        <span class="ks-lab">Academics</span></div>
      <div class="ks-mod" style="--k:4;--dx:calc(var(--step) * -3)"><div class="ks-disc">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M18.5 7.5v6M15.5 10.5h6"/></svg></div>
        <span class="ks-lab">Admissions</span></div>
      <div class="ks-mod" style="--k:0;--dx:calc(var(--step) * -1)"><div class="ks-disc">
        <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2.2"/><path d="M3 10.5h18"/><circle cx="16.8" cy="14.8" r="1.2"/></svg></div>
        <span class="ks-lab">Finance</span></div>
      <div class="ks-mod" style="--k:1;--dx:var(--step)"><div class="ks-disc">
        <svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15" rx="2.2"/><path d="M3.5 10h17M8 3v4M16 3v4"/><path d="M9 14.8l2 2 4-4"/></svg></div>
        <span class="ks-lab">Attendance</span></div>
      <div class="ks-mod" style="--k:3;--dx:calc(var(--step) * 2)"><div class="ks-disc">
        <svg viewBox="0 0 24 24"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v9a1.5 1.5 0 0 1-1.5 1.5H9l-5 4z"/><path d="M8 8.5h8M8 12h5"/></svg></div>
        <span class="ks-lab">Communication</span></div>
      <div class="ks-mod" style="--k:5;--dx:calc(var(--step) * 3)"><div class="ks-disc">
        <svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2.2"/><path d="M9 4V3h6v1"/><path d="M8.5 11h7M8.5 14.5h7M8.5 18h4"/></svg></div>
        <span class="ks-lab">Exams</span></div>
    </div>

    <div class="ks-msg">
      <div class="ks-tag">Your school. In sync.</div>
      <div class="ks-hello"><b id="ks-hello"></b><span id="ks-date"></span></div>
    </div>
    <button class="ks-skip" id="ks-skip" type="button">Skip</button>
  </div>
  <div class="ks-seam"></div>
</div>

<script>
const KairoSplash = (() => {
  const DEFAULT_LOGO = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA0gAAAFzCAYAAAAExSmcAAEAAElEQVR42uz9eZxlZ1Uujj9rvbuq53RCIIEwBFBEvV4BQ0AQ6ARkSEgYAhUQHEAZFFBkBkEqLTOCoIgCFwecTTsBKur1K7R61atGQAQhQMjYmYCMPVTVftfz+2Ot9b67At6fKFQ3yV58mu50V506Z5+991nPWs9QMNdcc8011xFRy8vLetxxx+knPvEJzkdjrrnmmmuuuQ5vyfzrZvmrNV1L555b/h/v9VxfR0VAjshfjF//wb/jv/HYt6T75NLSUpmvy7nmmmuuueaaa66vbUOn8KarzIfk6x8czUfhsJT+N67Br+XXzzXXXHPNNddcX4MGGsMwACIAb/IvRP4fCEBEWpeWX7O2uto+0BcWFydf7P+2sLjI+Gasra5I+5qbdH0Ev/Tx1z0X/j/7B5Lwb///119w+uIAyOR7py/8K+lTbvrcvtxz/fKP6X+b/0/ITZ7bl33u6x6WyCe/trpaAPDvgU33A9beC2y5AZDjgLoSD3omsAoACwuLYz6EWYWZzVfD1wtI+tlHbMKFO7YBADYdIHYCkK39DdyySBzs1yW23EAc3CE4uCrYskhsWhVcC2BlFGwaiC153S4ocB2weSCunfzAzaPg0EBs3tYf8+LrgOMGYmUUHLXg4CEfb2UUrErB4toCPr9o2FxGLA7EoQOLWFgUHLVKXL5wCDu2GlbWBJvWBJtYsWWRkBXDsZOfXa1iH3AOTjq0e/duO4z3SQ7DAkiuv8zlS6/6fq/CTe41aN8g0+vb75XD+4HFqz6PctytUTddAXnYbXFQROYLc6655pprrrk2+oP/IY964r32XXbRm6mLAyhUAFDAIAqCStLMTACw6CAOKBiIRgUQepfuqwoCFSREJYeuAsBQ/T+N3haIQpU0ClENqkJTU7BQWGkUgQJiBECIgYQJOAiVxiIiVVEAEJUwJQSgUkWU/kwogQBgCihIEwoBSCUUgKCAJAUmgIBFGUBNSEduhKgQZFFBNRCkKiAUiW+miIkZBCgQjmYCERGFCklSCFEMACriQECg0UvR0aGREAuMREJVjaRQSZioAJUEpCg0fiDJfHUApcIfWgCaUFRAVhORAqJCoFQtBpACMVYrm7Yu/Pu//cOHftisfrmObq4jaJzB1z7ymL8/f3i+UO63lTYORVHFYBATgSilQggDigmgpMFEi6CC0EohFIT5u1wEMKVWgZTaenaS0tr8BZBmFCqKnx1KVhOKiBqqFVkUgUClCCikUCtAq4TI4E9dK2BFi6gYxurzFgoxjmSlYBSVkQAhrAbQBKufryhXV17wgFvJ6+/ys++9ll86yvnqgE6/Atvj7lpeHj50zjlV7v3MQT7yy2sPePjjXnTllftOE5Q1gRaqSREhYCBFWfzGKBCqKEUhasJKpQhE4nDSqARhlWKE+oBmVYxSJO8lFBkEw85jjnnjP/zV+/4QWFZg9wyU5pprrrnmmmuDaqgrctzKKk+lVG/Y4YAE4vBCoIAJhISNawDUoRCzsWDbLimyeyEq1/x74X9pZpPtCCFSoOLdvG9wFBUVwhpLEYnn4o+nUP9OrgEiEAgMaw6DQCjVMYZMNlHoCxYjIZqPlEBNQKy1lktB1LUKxM9W9scRAyjx/IXRr4p3VgyYqLGNor9GE3/t4k8cKhavSYL7ZohmFhJfR/HH877W3wexuq4jJMwfhQTi+BvXQGM+NRBAlXguIlAZweo/T8oAEX8naQAKb1frKCIyA6MjFBrx3CXF58djP/ovdXkB8iO3GSBiYgLaCFGBGIxUIX0GIQJQhMoYacSggkYK1EGSQCgUUT9xoX5WMWcllAoWAayI0CCgqAgIVdEYj4gCqgoRQEza/QPiAwwfoVQ/F8dYvKjETACoqjABKoFK0i81OfgFkgeJ8+94rLztrm9977X4GoIjYF...',
    el = document.getElementById('kairo-splash'),
    logoImg = document.getElementById('ks-logo'),
    skipBtn = document.getElementById('ks-skip'),
    hello = document.getElementById('ks-hello'),
    dateEl = document.getElementById('ks-date'),
    schoolEl = document.getElementById('ks-school'),
    KEY = 'kairoSplashSeen',
    reduce = matchMedia('(prefers-reduced-motion: reduce)').matches,
    EXIT_MS = reduce ? 280 : 600;
  let timers = [], exiting = false, teardown = null;

  const later = (fn, ms) => timers.push(setTimeout(fn, ms));
  const setLogo = src => { logoImg.src = src; el.style.setProperty('--logo-url', `url("${src}")`); };
  setLogo(@json($logoUrl));

  function greeting(name) {
    const h = new Date().getHours();
    const p = h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
    return name ? `${p}, ${name}` : p;
  }
  function setSchool(name) {
    name = (name || '').trim();
    schoolEl.textContent = '';
    schoolEl.hidden = !name;
    el.classList.toggle('has-school', !!name);
    if (!name) { schoolEl.removeAttribute('aria-label'); return; }
    const total = [...name].filter(ch => ch.trim()).length;
    const gap = Math.min(0.045, 0.5 / total);
    let i = 0;
    name.split(/\s+/).forEach((word, wi, words) => {
      const w = document.createElement('span');
      w.className = 'w'; w.setAttribute('aria-hidden', 'true');
      [...word].forEach(ch => {
        const c = document.createElement('span');
        c.className = 'c'; c.textContent = ch;
        c.style.setProperty('--d', (1.85 + i++ * gap).toFixed(3));
        w.appendChild(c);
      });
      schoolEl.appendChild(w);
      if (wi < words.length - 1) schoolEl.appendChild(document.createTextNode(' '));
    });
    schoolEl.setAttribute('aria-label', name);
  }
  function hide() {
    teardown && teardown(); teardown = null;
    el.classList.remove('on', 'run', 'exit', 'quick');
  }
  function reset() {
    timers.forEach(clearTimeout); timers = []; exiting = false; hide();
  }

  function play({ name = '', school = '', logo, ready = null, repeat = 'full', cap = 8000, onDone, onError } = {}) {
    return new Promise(resolve => {
      reset();
      if (logo) setLogo(logo);

      const readyP = Promise.resolve(typeof ready === 'function' ? ready() : ready)
        .then(() => ({ ok: true }), error => ({ ok: false, error }));
      const capP = new Promise(r => later(() => r({ ok: false, error: new Error('Timed out waiting for the app') }), cap));
      const outcomeP = Promise.race([readyP, capP]);
      const complete = r => { onDone && onDone(r); if (!r.ok && onError) onError(r.error); resolve(r); };

      let seen = false;
      try { seen = sessionStorage.getItem(KEY) === '1'; } catch (e) {}
      if (seen && repeat === 'skip') { outcomeP.then(complete); return; }
      const quick = seen && repeat === 'quick';

      el.classList.toggle('quick', quick);
      hello.textContent = greeting(name);
      const d = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
      dateEl.textContent = d;
      setSchool(school);

      el.classList.add('on'); void el.offsetWidth;
      el.classList.add('run');

      const finish = () => {
        hide();
        try { if (repeat !== 'full') sessionStorage.setItem(KEY, '1'); } catch (e) {}
        outcomeP.then(complete);
      };
      const exit = () => {
        if (exiting) return; exiting = true;
        el.classList.add('exit'); later(finish, EXIT_MS);
      };
      const onKey = e => { if (e.key === 'Escape') exit(); };
      skipBtn.onclick = exit; document.addEventListener('keydown', onKey);
      teardown = () => { skipBtn.onclick = null; document.removeEventListener('keydown', onKey); };

      const minMs = reduce ? 1600 : quick ? 2200 : 3400;
      readyP.then(r => { if (!r.ok) exit(); });
      later(() => readyP.then(exit), minMs);
      later(exit, cap);
    });
  }
  return { play };
})();

document.addEventListener('DOMContentLoaded', () => {
  KairoSplash.play({
    name: @json($userName),
    school: @json($schoolName),
    logo: @json($logoUrl),
    ready: Promise.resolve(),
    repeat: 'full',
    onDone: () => {
      // Clear session flag via fetch so it only plays once per login
      fetch('{{ route("clear.login.splash") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).catch(() => {});
    }
  });
});
</script>
@endif
