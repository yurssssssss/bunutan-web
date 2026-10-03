(function () {
  const $ = id => document.getElementById(id);
  const app = $("app");
  const urls = { check: app.dataset.checkUrl, spin: app.dataset.spinUrl };
  const tok = v => getComputedStyle(document.documentElement).getPropertyValue(v).trim();

  const S = { name: "", group: "", wheel: [], rotation: 0, spinning: false };

  async function post(url, body) {
    let res;
    try {
      res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify(body),
      });
    } catch (e) {
      throw new Error("Hindi maabot ang bunutan. Tingnan ang koneksyon at subukan ulit.");
    }
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (res.status === 429) throw new Error("Masyadong maraming subok. Maghintay ng isang minuto at subukan ulit.");
      throw new Error(data.message || "May nagkaproblema. Pakisubukan ulit.");
    }
    return data;
  }

  function show(step) {
    $("stName").hidden = step !== "name";
    $("stGroup").hidden = step !== "group";
    $("stSpin").hidden = step !== "spin";
    $("backBtn").hidden = step === "name";
    if (step === "spin") requestAnimationFrame(draw);
  }

  // ---------- gulong ----------
  const canvas = $("wheel"), ctx = canvas.getContext("2d");

  function draw() {
    const size = Math.round(canvas.parentElement.getBoundingClientRect().width);
    if (!size) return;
    const dpr = window.devicePixelRatio || 1;
    if (canvas.width !== size * dpr) { canvas.width = size * dpr; canvas.height = size * dpr; }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, size, size);
    const c = size / 2, R = c - 4, list = S.wheel, n = list.length;
    const seg = (Math.PI * 2) / Math.max(n, 1);

    ctx.save(); ctx.translate(c, c); ctx.rotate(S.rotation);
    if (!n) { ctx.beginPath(); ctx.arc(0, 0, R, 0, Math.PI * 2); ctx.fillStyle = tok("--surface"); ctx.fill(); }
    list.forEach((num, i) => {
      const a0 = -Math.PI / 2 + i * seg;
      ctx.beginPath(); ctx.moveTo(0, 0); ctx.arc(0, 0, R, a0, a0 + seg); ctx.closePath();
      // alternate two tints; with an odd count the last slice gets a third so neighbours never match
      ctx.fillStyle = (n % 2 === 1 && i === n - 1 && n > 1) ? tok("--seg-c") : i % 2 === 0 ? tok("--seg-a") : tok("--seg-b");
      ctx.fill();
      ctx.lineWidth = 1.5; ctx.strokeStyle = tok("--line"); ctx.stroke();
      ctx.save();
      ctx.rotate(a0 + seg / 2); ctx.translate(R * 0.72, 0); ctx.rotate(Math.PI / 2);
      const fs = Math.max(14, Math.min(40, R * seg * 0.5, R * 0.16));
      ctx.font = `700 ${fs}px "Atkinson Hyperlegible", system-ui, sans-serif`;
      ctx.fillStyle = tok("--fg"); ctx.textAlign = "center"; ctx.textBaseline = "middle";
      ctx.fillText(String(num), 0, 0);
      ctx.restore();
    });
    ctx.restore();
    ctx.beginPath(); ctx.arc(c, c, R, 0, Math.PI * 2); ctx.lineWidth = 3; ctx.strokeStyle = tok("--fg"); ctx.stroke();
    ctx.beginPath(); ctx.arc(c, c, R * 0.08, 0, Math.PI * 2); ctx.fillStyle = tok("--fg"); ctx.fill();
  }

  function animateTo(index, n) {
    return new Promise(resolve => {
      const tau = Math.PI * 2, seg = tau / n;
      const want = index * seg + (0.2 + Math.random() * 0.6) * seg;   // land inside the slice, not on a line
      let delta = (((-S.rotation % tau) + tau) % tau) - want; if (delta < 0) delta += tau;
      const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
      const start = S.rotation, end = start + (reduce ? 1 : 5) * tau + delta;
      const dur = reduce ? 800 : 4500, t0 = performance.now();
      (function frame(now) {
        const t = Math.min(1, (now - t0) / dur);
        S.rotation = start + (end - start) * (1 - Math.pow(1 - t, 4));
        draw();
        t < 1 ? requestAnimationFrame(frame) : resolve();
      })(t0);
    });
  }

  // ---------- daloy ----------
  // 1. pangalan
  $("nameForm").addEventListener("submit", e => {
    e.preventDefault();
    const typed = $("nameInput").value.trim().replace(/\s+/g, " ");
    $("nameMsg").textContent = "";
    if (!typed) { $("nameMsg").textContent = "Pakisulat ang pangalan mo."; return; }
    S.name = typed;
    $("askGroup").textContent = typed + ", ikaw ba ay Matanda o Bata?";
    $("groupMsg").textContent = "";
    show("group");
  });

  // 2. Matanda o Bata
  document.querySelectorAll(".choice").forEach(btn => btn.addEventListener("click", async () => {
    const buttons = document.querySelectorAll(".choice");
    buttons.forEach(b => b.disabled = true);
    $("groupMsg").textContent = "";
    try {
      const data = await post(urls.check, { name: S.name, group: btn.dataset.group });
      S.group = btn.dataset.group; S.name = data.name; S.wheel = data.numbers; S.rotation = 0;
      $("hello").textContent = "Kumusta, " + data.name + "! (" + btn.textContent + ")";
      $("spinMsg").textContent = "";
      $("spinBtn").disabled = false;
      show("spin");
    } catch (err) {
      $("groupMsg").textContent = err.message;
    } finally {
      buttons.forEach(b => b.disabled = false);
    }
  }));

  // 3. ikot
  $("spinBtn").addEventListener("click", async () => {
    if (S.spinning || !S.name || !S.group) return;
    S.spinning = true; $("spinBtn").disabled = true; $("backBtn").hidden = true; $("spinMsg").textContent = "";
    try {
      const result = await post(urls.spin, { name: S.name, group: S.group });
      // the server returns the wheel as it was at the moment of the spin
      S.wheel = result.numbers; draw();
      await animateTo(result.numbers.indexOf(result.number), result.numbers.length);
      $("resSmall").textContent = "Numero " + result.number;
      $("resBig").textContent = result.name;
      $("resultPopup").hidden = false;
      $("doneBtn").focus();
    } catch (err) {
      $("spinMsg").textContent = err.message;
      $("spinBtn").disabled = false;
    } finally {
      S.spinning = false; $("backBtn").hidden = false;
    }
  });

  function reset() {
    $("resultPopup").hidden = true;
    S.name = ""; S.group = ""; S.wheel = [];
    $("nameInput").value = ""; $("nameMsg").textContent = "";
    show("name"); $("nameInput").focus();
  }
  $("doneBtn").addEventListener("click", reset);
  document.querySelectorAll("[data-back]").forEach(b => b.addEventListener("click", () => { if (!S.spinning) reset(); }));

  new ResizeObserver(draw).observe(canvas.parentElement);
  matchMedia("(prefers-color-scheme: dark)").addEventListener("change", draw);
  if (document.fonts) document.fonts.ready.then(draw);
})();
