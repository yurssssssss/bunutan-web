(function () {
  const $ = id => document.getElementById(id);
  const app = $("app");
  const urls = { check: app.dataset.checkUrl, spin: app.dataset.spinUrl };
  const tok = v => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
  const norm = s => s.trim().replace(/\s+/g, " ").toLowerCase();
  const groupBox = g => document.querySelector('.namelist-group[data-group="' + g + '"]');
  const groupPicks = g => [...groupBox(g).querySelectorAll(".name-pick")];
  const groupLabel = g => document.querySelector('.choice[data-group="' + g + '"]').textContent;

  const PAGE_SIZE = 6;

  const S = { step: "group", name: "", group: "", page: 0, wheel: [], rotation: 0, spinning: false };

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
    S.step = step;
    $("stGroup").hidden = step !== "group";
    $("stName").hidden = step !== "name";
    $("stSpin").hidden = step !== "spin";
    $("backBtn").hidden = step === "group";
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

  // the numbered names under the wheel; same order and numbers as the slices
  function renderWheelList(entries) {
    const ol = $("wheelList"); ol.innerHTML = "";
    entries.forEach(e => {
      const li = document.createElement("li");
      const num = document.createElement("span"); num.className = "pnum"; num.textContent = e.number;
      const nm = document.createElement("span"); nm.textContent = e.name;
      li.append(num, nm); ol.appendChild(li);
    });
  }

  // ---------- daloy ----------
  // 1. Matanda o Bata: only that group's list is shown on the next step
  document.querySelectorAll(".choice").forEach(btn => btn.addEventListener("click", () => {
    S.group = btn.dataset.group;
    document.querySelectorAll(".namelist-group").forEach(g => g.hidden = g.dataset.group !== S.group);
    $("askName").textContent = "Ano ang pangalan mo? (" + groupLabel(S.group) + ")";
    $("nameInput").value = ""; $("nameMsg").textContent = "";
    S.page = 0; filterList();
    show("name");
  }));

  // 2. pangalan: typing filters the list (6 names per page); tapping a name copies its spelling
  function filterList() {
    const q = norm($("nameInput").value), box = groupBox(S.group);
    if (!box) return;
    const all = groupPicks(S.group);
    const hits = all.filter(b => !q || norm(b.dataset.name).includes(q));
    const pages = Math.max(1, Math.ceil(hits.length / PAGE_SIZE));
    S.page = Math.min(Math.max(S.page, 0), pages - 1);
    const onPage = new Set(hits.slice(S.page * PAGE_SIZE, (S.page + 1) * PAGE_SIZE));
    all.forEach(b => b.closest(".name-row").hidden = !onPage.has(b));
    box.querySelector(".no-match").hidden = !all.length || hits.length > 0;

    const pager = box.querySelector(".pager");
    if (pager) {
      pager.hidden = pages <= 1;
      pager.querySelector(".pager-info").textContent = "Pahina " + (S.page + 1) + " ng " + pages;
      pager.querySelector('[data-step="-1"]').disabled = S.page === 0;
      pager.querySelector('[data-step="1"]').disabled = S.page >= pages - 1;
    }
  }
  $("nameInput").addEventListener("input", () => { $("nameMsg").textContent = ""; S.page = 0; filterList(); });
  document.querySelectorAll(".pager-btn").forEach(b => b.addEventListener("click", () => {
    S.page += Number(b.dataset.step); filterList();
  }));
  // Kopyahin: fills the name box (and the clipboard, where the browser allows it)
  document.querySelectorAll(".name-pick").forEach(b => b.addEventListener("click", () => {
    $("nameInput").value = b.dataset.name; $("nameMsg").textContent = "";
    try { navigator.clipboard && navigator.clipboard.writeText(b.dataset.name).catch(() => {}); } catch (e) {}
    document.querySelectorAll(".name-pick.copied").forEach(o => { o.classList.remove("copied"); o.textContent = "Kopyahin"; });
    b.classList.add("copied"); b.textContent = "Nakopya ✓";
    setTimeout(() => { b.classList.remove("copied"); b.textContent = "Kopyahin"; }, 2000);
    filterList();
    $("nameInput").scrollIntoView({ block: "center", behavior: "smooth" });
  }));
  // tapping anywhere on the row does the same as its button
  document.querySelectorAll(".name-row").forEach(row => row.addEventListener("click", e => {
    if (!e.target.closest(".name-pick")) row.querySelector(".name-pick").click();
  }));

  $("nameForm").addEventListener("submit", async e => {
    e.preventDefault();
    const typed = $("nameInput").value.trim().replace(/\s+/g, " ");
    $("nameMsg").textContent = "";
    if (!typed) { $("nameMsg").textContent = "Pakisulat ang pangalan mo."; return; }
    const listed = groupPicks(S.group).find(b => norm(b.dataset.name) === norm(typed));
    const elsewhere = [...document.querySelectorAll(".name-pick")].find(b => norm(b.dataset.name) === norm(typed));
    if (!listed && elsewhere) {
      const other = groupLabel(elsewhere.closest(".namelist-group").dataset.group);
      $("nameMsg").textContent = "Nasa listahan ng " + other + " ang \"" + elsewhere.dataset.name + "\". Bumalik at piliin ang " + other + ".";
      return;
    }
    if (!listed) {
      $("nameMsg").textContent = "Wala sa listahan ng " + groupLabel(S.group) + " ang \"" + typed + "\". Pindutin ang pangalan mo sa listahan sa ibaba para makopya ang tamang spelling.";
      return;
    }
    $("nameBtn").disabled = true;
    try {
      const data = await post(urls.check, { name: listed.dataset.name, group: S.group });
      S.name = data.name; S.wheel = data.numbers; S.rotation = 0;
      renderWheelList(data.entries);
      $("hello").textContent = "Kumusta, " + data.name + "! (" + groupLabel(S.group) + ")";
      $("spinMsg").textContent = "";
      $("spinBtn").disabled = false;
      show("spin");
    } catch (err) {
      $("nameMsg").textContent = err.message;
    } finally {
      $("nameBtn").disabled = false;
    }
  });

  // 3. ikot (the pick is random, made by the server)
  $("spinBtn").addEventListener("click", async () => {
    if (S.spinning || !S.name || !S.group) return;
    S.spinning = true; $("spinBtn").disabled = true; $("backBtn").hidden = true; $("spinMsg").textContent = "";
    try {
      const result = await post(urls.spin, { name: S.name, group: S.group });
      // the server returns the wheel as it was at the moment of the spin
      S.wheel = result.numbers; draw(); renderWheelList(result.entries);
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

  // start over for the next person
  function reset() {
    $("resultPopup").hidden = true;
    S.name = ""; S.group = ""; S.wheel = [];
    $("nameInput").value = ""; $("nameMsg").textContent = "";
    show("group");
  }
  $("doneBtn").addEventListener("click", reset);

  // Bumalik: one step back (spin → name → Matanda o Bata)
  $("backBtn").addEventListener("click", () => {
    if (S.spinning) return;
    if (S.step === "spin") { $("nameMsg").textContent = ""; show("name"); }
    else reset();
  });

  new ResizeObserver(draw).observe(canvas.parentElement);
  matchMedia("(prefers-color-scheme: dark)").addEventListener("change", draw);
  if (document.fonts) document.fonts.ready.then(draw);
})();
