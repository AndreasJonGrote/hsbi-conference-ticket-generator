// ======= Overlay sources (local only) =======
const OVERLAY_LOCAL_FILENAME =
  (typeof hsbiTicket !== 'undefined' ? hsbiTicket.pluginUrl : '') + "views/assets/images/Post-Photografc-Images_Overlay.png";

// ======= Auto-fill configuration =======
const AUTO_FILL_ENABLED = true; // Set to false to disable auto-fill

// ======= Required fields config =======
const STEP1_FIELDS = [
  {
    id: 'nameInput',
    name: 'Name',
    validator: isValidName
  },
  {
    id: 'emailInput', 
    name: 'E-Mail',
    validator: isValidEmail
  }
];

const STEP3_FIELDS = [
  {
    id: 'confirmCheckbox',
    name: 'Datenschutzbestimmung',
    validator: (value) => value && value.checked
  }
];

// ======= Element refs =======
const board = document.getElementById("board");
const canvas = document.getElementById("canvas");
const res = document.getElementById("res");
const pen = document.getElementById("pen");
const bg = document.getElementById("bg");
const clearBtn = document.getElementById("clear");
const finalizeBtn = document.getElementById("finalize");
const previewCanvas = document.getElementById("previewCanvasInline");


// ======= Status updates =======
function updateStatus(message) {
  const statusEl = document.getElementById("status");
  if (statusEl) {
    statusEl.textContent = message;
  }
}

// ======= Auto-fill functionality =======
function saveFormData() {
  if (!AUTO_FILL_ENABLED) return;
  
  const formData = {
    name: nameInput ? nameInput.value : '',
    email: emailInput ? emailInput.value : '',
    organization: orgInput ? orgInput.value : '',
    salutation: salutationInput ? salutationInput.value : ''
  };
  
  sessionStorage.setItem('ticketFormData', JSON.stringify(formData));
}

async function loadFormData() {
  if (!AUTO_FILL_ENABLED) return;
  
  try {
    // Try to load from server session first
    const response = await fetch((typeof hsbiTicket !== 'undefined' ? hsbiTicket.ajaxUrl : 'views/assets/library/ajax.php') + '?action=getSessionData');
    const result = await response.json();
    
    if (result.success && result.data) {
      // Load from server session
      if (nameInput && result.data.name) nameInput.value = result.data.name;
      if (emailInput && result.data.email) emailInput.value = result.data.email;
      if (orgInput && result.data.organization) orgInput.value = result.data.organization;
      if (salutationInput && result.data.salutation) salutationInput.value = result.data.salutation;
      
    } else {
      // Fallback to local sessionStorage
      const savedData = sessionStorage.getItem('ticketFormData');
      if (savedData) {
        const formData = JSON.parse(savedData);
        
        if (nameInput && formData.name) nameInput.value = formData.name;
        if (emailInput && formData.email) emailInput.value = formData.email;
        if (orgInput && formData.organization) orgInput.value = formData.organization;
        if (salutationInput && formData.salutation) salutationInput.value = formData.salutation;
        
      }
    }
  } catch (error) {
    console.warn('Failed to load form data:', error);
  }
}

function clearFormData() {
  if (!AUTO_FILL_ENABLED) return;
  
  sessionStorage.removeItem('ticketFormData');
}

// ======= Validation using config =======
function validateFields(fields) {
  let hasErrors = false;
  const errors = [];

  fields.forEach(field => {
    const element = document.getElementById(field.id);
    if (!element) return;

    const value = field.id === 'confirmCheckbox' ? element : element.value;
    const isValid = field.validator(value);

    if (!isValid) {
      element.setAttribute("aria-invalid", "true");
      
      // Special handling for confirmCheckbox - also mark the control-confirm container
      if (field.id === 'confirmCheckbox') {
        const controlConfirm = document.querySelector('.control-confirm');
        if (controlConfirm) {
          controlConfirm.setAttribute("aria-invalid", "true");
        }
      }
      
      errors.push(field.name);
      hasErrors = true;
    } else {
      element.removeAttribute("aria-invalid");
      
      // Special handling for confirmCheckbox - also remove from control-confirm container
      if (field.id === 'confirmCheckbox') {
        const controlConfirm = document.querySelector('.control-confirm');
        if (controlConfirm) {
          controlConfirm.removeAttribute("aria-invalid");
        }
      }
    }
  });

  if (hasErrors) {
    const errorMessage = `Bitte füllen Sie folgende Felder korrekt aus: ${errors.join(', ')}`;
    updateStatus(errorMessage);
  }

  return !hasErrors;
}

function validateStep1() {
  return validateFields(STEP1_FIELDS);
}

function validateStep3() {
  return validateFields(STEP3_FIELDS);
}
const backToDrawBtn = document.getElementById("backToDraw");
const downloadBtn = document.getElementById("proceed");
const backToPreviewBtn = document.getElementById("backToPreview");
const submitTicketBtn = document.getElementById("submitTicket");
const step1Chip = document.getElementById("step1");
const step2Chip = document.getElementById("step2");
const step3Chip = document.getElementById("step3");
const step4Chip = document.getElementById("step4");
const undoBtn = document.getElementById("undo");
const redoBtn = document.getElementById("redo");
const nameInput = document.getElementById("nameInput");
const orgInput = document.getElementById("orgInput");
const emailInput = document.getElementById("emailInput");
const salutationInput = document.getElementById("salutationInput");
const resLabel = document.getElementById("resLabel");
const randomizeBtn = document.getElementById("randomize");

// Step toggles (preview at identical position/size)
function showStep2() {
  if (board) board.style.display = "none";
  if (previewCanvas) previewCanvas.style.display = "block";
  const step1Bar = document.querySelector(".step1-only");
  if (step1Bar) step1Bar.style.display = "none";
  const step2Bar = document.querySelector(".step2-only");
  if (step2Bar) step2Bar.style.display = "flex";
  step1Chip.classList.remove("active");
  step2Chip.classList.add("active");
  
  // Remove step1-active class for CSS targeting
  document.body.classList.remove("step1-active");
  
  // Copy values to hidden fields
  const hiddenName = document.getElementById("hiddenName");
  const hiddenEmail = document.getElementById("hiddenEmail");
  const hiddenOrg = document.getElementById("hiddenOrg");
  
  if (hiddenName && nameInput) hiddenName.value = nameInput.value;
  if (hiddenEmail && emailInput) hiddenEmail.value = emailInput.value;
  if (hiddenOrg && orgInput) hiddenOrg.value = orgInput.value;
  
  // Disable input fields
  if (nameInput) nameInput.disabled = true;
  if (emailInput) emailInput.disabled = true;
  if (orgInput) orgInput.disabled = true;
}
function showStep1() {
  if (board) board.style.display = "grid";
  if (previewCanvas) previewCanvas.style.display = "none";
  const step2Bar = document.querySelector(".step2-only");
  if (step2Bar) step2Bar.style.display = "none";
  const step1Bar = document.querySelector(".step1-only");
  if (step1Bar) step1Bar.style.display = "flex";
  step2Chip.classList.remove("active");
  step1Chip.classList.add("active");
  
  // Add step1-active class for CSS targeting
  document.body.classList.add("step1-active");
  
  // Re-enable input fields
  if (nameInput) nameInput.disabled = false;
  if (emailInput) emailInput.disabled = false;
  if (orgInput) orgInput.disabled = false;
  
  updateStatus("Back to drawing.");
}
function showStep3() {
  const step2Bar = document.querySelector(".step2-only");
  if (step2Bar) step2Bar.style.display = "none";
  const step3Bar = document.querySelector(".step3-only");
  if (step3Bar) step3Bar.style.display = "flex";
  step2Chip.classList.remove("active");
  step3Chip.classList.add("active");
  
  // Remove step1-active class for CSS targeting
  document.body.classList.remove("step1-active");
  
  const confirmControl = document.querySelector(".control-confirm");
  if (confirmControl) confirmControl.style.display = "flex";
  updateStatus("Bitte bestätigen Sie die Datenschutzbestimmungen.");
}
function showStep4() {
  // Hide canvas area and controls
  const canvasArea = document.querySelector(".canvas-area");
  if (canvasArea) canvasArea.style.display = "none";
  
  const controls = document.querySelector(".controls");
  if (controls) controls.style.display = "none";
  
  // Remove step1-active class for CSS targeting
  document.body.classList.remove("step1-active");
  
  // Hide all step bars
  const step1Bar = document.querySelector(".step1-only");
  const step2Bar = document.querySelector(".step2-only");
  const step3Bar = document.querySelector(".step3-only");
  if (step1Bar) step1Bar.style.display = "none";
  if (step2Bar) step2Bar.style.display = "none";
  if (step3Bar) step3Bar.style.display = "none";
  
  // Show step 4
  const step4Bar = document.querySelector(".step4-only");
  if (step4Bar) step4Bar.style.display = "flex";
  
  // Update chips
  step1Chip.classList.remove("active");
  step2Chip.classList.remove("active");
  step3Chip.classList.remove("active");
  step4Chip.classList.add("active");
  
  updateStatus("Ticket successfully created!");
}

if (backToDrawBtn) {
  backToDrawBtn.addEventListener("click", (e) => {
    e.preventDefault();
    showStep1();
  });
}

// ======= State =======
let drawing = false;
let gridN = parseInt(res.value, 10);
const undoStack = [];
const redoStack = [];

// Non-repetition memory (persisted in localStorage)
const SEEN_KEY = "ticket_random_seen_v1";
const seenDesigns = new Set(JSON.parse(localStorage.getItem(SEEN_KEY) || "[]"));

// ======= Helpers =======
function isValidEmail(v) {
  if (!v) return false;
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}
function isValidName(v) {
  if (!v) return false;
  const trimmed = v.trim();
  return trimmed.length >= 2 && /^[a-zA-ZäöüÄÖÜß\s-]+$/.test(trimmed);
}
function getState() {
  const cells = [...board.children];
  return {
    n: gridN,
    bg: bg.value,
    pen: pen.value,
    cells: cells.map((c) => c.style.backgroundColor || ""),
  };
}
function applyState(state) {
  if (!state) return;
  buildGrid(state.n);
  bg.value = state.bg;
  pen.value = state.pen || pen.value;
  board.style.backgroundColor = state.bg;
  const cells = [...board.children];
  state.cells.forEach((col, i) => {
    if (cells[i]) cells[i].style.backgroundColor = col;
  });
}
function setState(state) {
  applyState(state);
}
function pushUndo() {
  undoStack.push(getState());
  if (undoStack.length > 200) undoStack.shift();
  redoStack.length = 0;
}

function buildGrid(n) {
  gridN = n;
  resLabel.textContent = `${n} × ${n}`;
  board.innerHTML = "";
  board.style.gridTemplateColumns = `repeat(${n},1fr)`;
  board.style.backgroundColor = bg.value;
  const total = n * n;
  const frag = document.createDocumentFragment();
  for (let i = 0; i < total; i++) {
    const cell = document.createElement("div");
    cell.className = "cell";
    cell.dataset.filled = "0";
    frag.appendChild(cell);
  }
  board.appendChild(frag);
}

function paintCell(el) {
  if (!el || !el.classList || !el.classList.contains("cell")) return;
  el.style.backgroundColor = pen.value;
  el.dataset.filled = "1";
}

function drawBoardToCanvas(ctx, size) {
  const n = gridN,
    cellSize = size / n;
  ctx.fillStyle = bg.value;
  ctx.fillRect(0, 0, size, size);
  const cells = [...board.children];
  cells.forEach((cell, idx) => {
    const col = idx % n;
    const row = Math.floor(idx / n);
    const color = cell.style.backgroundColor;
    if (color && color !== "transparent" && color !== "") {
      ctx.fillStyle = color;
      ctx.fillRect(
        Math.round(col * cellSize),
        Math.round(row * cellSize),
        Math.ceil(cellSize),
        Math.ceil(cellSize)
      );
    }
  });
}

function snapshotImageData() {
  const size = 800;
  const c = document.createElement("canvas");
  c.width = size;
  c.height = size;
  const cx = c.getContext("2d");
  drawBoardToCanvas(cx, size);
  return cx.getImageData(0, 0, size, size);
}

function avgColorFromRegion(imgData, x0, y0, w, h) {
  const { data, width } = imgData;
  let r = 0,
    g = 0,
    b = 0,
    count = 0;
  const xi0 = Math.max(0, Math.floor(x0)),
    yi0 = Math.max(0, Math.floor(y0));
  const xi1 = Math.min(width, Math.ceil(x0 + w)),
    yi1 = Math.min(imgData.height, Math.ceil(y0 + h));
  for (let y = yi0; y < yi1; y++) {
    let off = (y * width + xi0) * 4;
    for (let x = xi0; x < xi1; x++) {
      r += data[off];
      g += data[off + 1];
      b += data[off + 2];
      count++;
      off += 4;
    }
  }
  if (count === 0) return null;
  return {
    r: Math.round(r / count),
    g: Math.round(g / count),
    b: Math.round(b / count),
  };
}

function hexToRgb(hex) {
  if (!hex) return null;
  const m = hex.replace("#", "");
  const bigint = parseInt(
    m.length === 3
      ? m
          .split("")
          .map((c) => c + c)
          .join("")
      : m,
    16
  );
  return { r: (bigint >> 16) & 255, g: (bigint >> 8) & 255, b: bigint & 255 };
}
function colorDist2(a, b) {
  const dr = a.r - b.r,
    dg = a.g - b.g,
    db = a.b - b.b;
  return dr * dr + dg * dg + db * db;
}

function rebuildFromSnapshot(newN, imgData) {
  buildGrid(newN);
  const size = 800;
  const cellSize = size / newN;
  const bgRgb = hexToRgb(bg.value) || { r: 255, g: 255, b: 255 };
  const cells = [...board.children];
  for (let row = 0; row < newN; row++) {
    for (let col = 0; col < newN; col++) {
      const region = avgColorFromRegion(
        imgData,
        col * cellSize,
        row * cellSize,
        cellSize,
        cellSize
      );
      if (!region) continue;
      if (colorDist2(region, bgRgb) < 20 * 20) continue;
      const idx = row * newN + col;
      const cell = cells[idx];
      if (!cell) continue;
      cell.style.backgroundColor = `rgb(${region.r}, ${region.g}, ${region.b})`;
      cell.dataset.filled = "1";
    }
  }
}

// ======= Overlay loading =======
function loadImage(src, useCrossOrigin = false) {
  return new Promise((res, rej) => {
    const img = new Image();
    if (useCrossOrigin) img.crossOrigin = "anonymous";
    img.onload = () => {
      res(img);
    };
    img.onerror = (e) => {
      console.error("Image load failed:", src, e);
      rej(new Error("not-found: " + src));
    };
    img.src = src;
  });
}

async function tryLoadOverlay() {
  try {
    const img = await loadImage(OVERLAY_LOCAL_FILENAME, false);
    return img;
  } catch (e) {
    console.error("Overlay local not found:", e);
    try {
      const altImg = await loadImage(
        "./images/Post-Photografc-Images_Overlay.png",
        false
      );
      return altImg;
    } catch (e2) {
      console.error("Alternative path also failed:", e2);
      return null;
    }
  }
}

function downloadCanvas(canvas, name) {
  // Robust: prefer toBlob + ObjectURL, fallback to toDataURL
  if (canvas.toBlob) {
    canvas.toBlob((blob) => {
      if (!blob) {
        try {
          const url = canvas.toDataURL("image/png");
          const a = document.createElement("a");
          a.href = url;
          a.download = name;
          document.body.appendChild(a);
          a.click();
          a.remove();
          return;
        } catch (e) {
          console.warn("toDataURL failed:", e);
          return;
        }
      }
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = name;
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    }, "image/png");
  } else {
    try {
      const url = canvas.toDataURL("image/png");
      const a = document.createElement("a");
      a.href = url;
      a.download = name;
      document.body.appendChild(a);
      a.click();
      a.remove();
    } catch (e) {
      console.warn("dataURL failed:", e);
    }
  }
}

// ======= Name/Organization box (Canvas) =======
function drawNameOrgBox(ctx, W, H, name, org) {
  const SCALE = 1.25;
  const nameText = (name || "").trim();
  const orgTextRaw = (org || "").trim();
  const orgText = orgTextRaw ? `( ${orgTextRaw})` : "";

  const BASE_NAME_SIZE = 32;
  const nameSize = Math.round(BASE_NAME_SIZE * SCALE);
  const padX = 2;
  const padY = 8;
  const gap = 0;

  const baseNameBoxH = Math.round(BASE_NAME_SIZE + padY);
  const fixedSquare = Math.round(baseNameBoxH / 2);

  ctx.save();

  ctx.font = `${nameSize}px ReplicaMono, system-ui, sans-serif`;
  const nameW = ctx.measureText(nameText).width;
  const nameBoxH = Math.round(nameSize + padY);

  const orgBoxH = Math.round(nameBoxH / 2);
  const sqSize = fixedSquare;

  const nameBoxW = Math.round(nameW + 2 * padX + 2 * sqSize);

  const orgPadY = Math.max(4, Math.round(padY / 2));
  const orgSize = Math.max(10, Math.round(orgBoxH - 2 * orgPadY));
  ctx.font = `${orgSize}px HSBILausanneLocal, system-ui, sans-serif`;
  const orgPadX = Math.max(8, Math.round(padX * 0.6));
  const orgW = orgText ? ctx.measureText(orgText).width : 0;
  const orgBoxW = Math.round((orgText ? orgW : 0) + 2 * orgPadX);

  const groupW = nameBoxW + (orgText ? gap + orgBoxW : 0);
  const groupH = Math.max(nameBoxH, orgBoxH);
  const groupX = Math.round((W - groupW) / 2);
  const groupY = Math.round((H - groupH) / 2);

  const nameX = groupX,
    nameY = groupY;
  const orgX = nameX + nameBoxW + (orgText ? gap : 0);
  const orgY = groupY;

  ctx.fillStyle = "black";
  ctx.fillRect(nameX, nameY, nameBoxW, nameBoxH);
  if (orgText) ctx.fillRect(orgX, orgY, orgBoxW, orgBoxH);

  ctx.font = `${nameSize}px ReplicaMono, system-ui, sans-serif`;
  ctx.fillStyle = "white";
  const nameBaseline = nameY + padY + nameSize * 0.75;
  const nameTextX = nameX + padX + sqSize;
  ctx.fillText(nameText, nameTextX, nameBaseline);

  if (orgText) {
    ctx.font = `${orgSize}px HSBILausanneLocal, system-ui, sans-serif`;
    ctx.fillStyle = "white";
    const orgBaseline = orgY + orgPadY + orgSize * 0.82;
    ctx.fillText(orgText, orgX + orgPadX, orgBaseline);
  }

  const squareX = nameX - sqSize;
  const squareY = nameY + nameBoxH;
  ctx.fillStyle = "black";
  ctx.fillRect(squareX, squareY, sqSize, sqSize);

  ctx.restore();
}

// ======= Randomization =======
function randInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}
function randomHexColor() {
  const r = randInt(0, 255),
    g = randInt(0, 255),
    b = randInt(0, 255);
  return "#" + [r, g, b].map((v) => v.toString(16).padStart(2, "0")).join("");
}
function hexToRgb(hex) {
  if (!hex) return null;
  const m = hex.replace("#", "");
  const bigint = parseInt(
    m.length === 3
      ? m
          .split("")
          .map((c) => c + c)
          .join("")
      : m,
    16
  );
  return { r: (bigint >> 16) & 255, g: (bigint >> 8) & 255, b: bigint & 255 };
}
function colorDist2(a, b) {
  const dr = a.r - b.r,
    dg = a.g - b.g,
    db = a.b - b.b;
  return dr * dr + dg * dg + db * db;
}
function ensureContrast(bgHex) {
  const minDist = 80;
  const bgRGB = hexToRgb(bgHex);
  for (let i = 0; i < 64; i++) {
    const p = randomHexColor();
    const pr = hexToRgb(p);
    if (Math.sqrt(colorDist2(bgRGB, pr)) >= minDist) return p;
  }
  const inv =
    "#" +
    [255 - bgRGB.r, 255 - bgRGB.g, 255 - bgRGB.b]
      .map((v) => v.toString(16).padStart(2, "0"))
      .join("");
  return inv;
}
function designHash(n, bgHex, penHex, filledIdx) {
  const base = `${n}|${bgHex}|${penHex}|${filledIdx.join(",")}`;
  let h = 5381;
  for (let i = 0; i < base.length; i++) {
    h = ((h << 5) + h) ^ base.charCodeAt(i);
  }
  return (h >>> 0).toString(36);
}
function randomizeDesign() {
  pushUndo();
  const nMin = 2,
    nMax = 16;
  const densityMin = 0.15,
    densityMax = 0.5;
  let attempts = 0,
    maxAttempts = 100;
  while (attempts++ < maxAttempts) {
    const n = randInt(nMin, nMax);
    const newBg = randomHexColor();
    const newPen = ensureContrast(newBg);
    res.value = n;
    buildGrid(n);
    bg.value = newBg;
    pen.value = newPen;
    board.style.backgroundColor = newBg;
    const total = n * n;
    const density = Math.random() * (densityMax - densityMin) + densityMin;
    const fillCount = Math.max(1, Math.round(total * density));
    const indices = Array.from({ length: total }, (_, i) => i);
    for (let i = indices.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [indices[i], indices[j]] = [indices[j], indices[i]];
    }
    const chosen = indices.slice(0, fillCount).sort((a, b) => a - b);
    const cells = [...board.children];
    chosen.forEach((idx) => {
      const cell = cells[idx];
      if (cell) {
        cell.style.backgroundColor = newPen;
        cell.dataset.filled = "1";
      }
    });
    const h = designHash(n, newBg, newPen, chosen);
    if (!seenDesigns.has(h)) {
      seenDesigns.add(h);
      localStorage.setItem(
        SEEN_KEY,
        JSON.stringify(Array.from(seenDesigns).slice(-500))
      );
      return;
    }
  }
  seenDesigns.clear();
  localStorage.removeItem(SEEN_KEY);
}

// ======= Events =======
board.addEventListener("mousedown", (e) => {
  if (e.button !== 0) return;
  pushUndo();
  drawing = true;
  paintCell(e.target);
  e.preventDefault();
});
board.addEventListener("mousemove", (e) => {
  if (!drawing) return;
  paintCell(e.target);
});
window.addEventListener("mouseup", () => {
  drawing = false;
});
board.addEventListener(
  "touchstart",
  (e) => {
    pushUndo();
    drawing = true;
    const t = e.touches[0];
    const el = document.elementFromPoint(t.clientX, t.clientY);
    paintCell(el);
    e.preventDefault();
  },
  { passive: false }
);
board.addEventListener(
  "touchmove",
  (e) => {
    if (!drawing) return;
    const t = e.touches[0];
    const el = document.elementFromPoint(t.clientX, t.clientY);
    paintCell(el);
    e.preventDefault();
  },
  { passive: false }
);
window.addEventListener("touchend", () => {
  drawing = false;
});

res.addEventListener("input", () => {
  const snap = snapshotImageData();
  pushUndo();
  const newN = parseInt(res.value, 10);
  rebuildFromSnapshot(newN, snap);
});
bg.addEventListener("input", () => {
  board.style.backgroundColor = bg.value;
});
clearBtn.addEventListener("click", () => {
  pushUndo();
  board.querySelectorAll(".cell").forEach((c) => {
    c.style.backgroundColor = "transparent";
    c.dataset.filled = "0";
  });
});

randomizeBtn.addEventListener("click", randomizeDesign);

// Undo/Redo buttons
if (undoBtn) {
  undoBtn.addEventListener("click", () => {
    if (undoStack.length > 1) {
      redoStack.push(undoStack.pop());
      setState(undoStack[undoStack.length - 1]);
    }
  });
}

if (redoBtn) {
  redoBtn.addEventListener("click", () => {
    if (redoStack.length > 0) {
      undoStack.push(redoStack.pop());
      setState(undoStack[undoStack.length - 1]);
    }
  });
}

// ======= Download-Helfer (NEU) =======
function sanitizeToken(s) {
  if (!s) return "";
  return s
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^A-Za-z0-9]+/g, " ")
    .trim()
    .replace(/\s+/g, "_");
}
function compactNameNoSpaces(s) {
  if (!s) return "";
  return s
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^A-Za-z0-9]+/g, "");
}
function timestampStr() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}-${p(
    d.getHours()
  )}-${p(d.getMinutes())}-${p(d.getSeconds())}`;
}
function makeFileName() {
  const org = sanitizeToken(orgInput.value);
  const full = compactNameNoSpaces(nameInput.value);
  const ts = timestampStr();
  // organisation_ + FirstNameLastName_ + PPI_2025 + Timestamp
  const base = `${org ? org + "_" : ""}${full ? full + "_" : ""}PPI_2025${ts}`;
  return (base || `PPI_2025${ts}`) + ".png";
}

async function buildCanvasWithoutOverlay() {
  const overlay = await tryLoadOverlay(); // nur um Größe zu bestimmen
  let W = overlay ? overlay.width || overlay.naturalWidth : 800;
  let H = overlay ? overlay.height || overlay.naturalHeight : 800;

  const canvas = document.createElement("canvas");
  canvas.width = W;
  canvas.height = H;
  const ctx = canvas.getContext("2d");

  // Inhalt ohne Overlay und ohne Text zeichnen
  const tmp = document.createElement("canvas");
  tmp.width = 800;
  tmp.height = 800;
  const tctx = tmp.getContext("2d");
  drawBoardToCanvas(tctx, 800);
  ctx.drawImage(tmp, 0, 0, W, H);
  // Kein drawNameOrgBox() - nur reiner Hintergrund
  return canvas;
}

function buildPurePatternCanvas() {
  // Reines Canvas-Muster ohne Overlay, ohne Text, ohne Skalierung
  const canvas = document.createElement("canvas");
  canvas.width = 800;
  canvas.height = 800;
  const ctx = canvas.getContext("2d");
  
  // Nur das Board-Muster zeichnen
  drawBoardToCanvas(ctx, 800);
  
  return canvas;
}

// Block Step 2 unless email and name valid
finalizeBtn.addEventListener("click", async () => {
  if (!validateStep1()) {
    return;
  }

  const canvas = await finalizeTicket(true);
  // Tie to container size (same spot & size)
  previewCanvas.width = canvas.width;
  previewCanvas.height = canvas.height;
  const pctx = previewCanvas.getContext("2d");
  pctx.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
  pctx.drawImage(canvas, 0, 0);
  showStep2();
});

if (downloadBtn) {
  downloadBtn.addEventListener("click", async () => {
    showStep3();
  });
}

if (backToPreviewBtn) {
  backToPreviewBtn.addEventListener("click", (e) => {
    e.preventDefault();
    showStep2();
  });
}

if (submitTicketBtn) {
  submitTicketBtn.addEventListener("click", async () => {
    if (!validateStep3()) {
      return;
    }

    try {
      // Show loading state
      updateStatus("Creating ticket...");
      submitTicketBtn.disabled = true;
      submitTicketBtn.textContent = "Creating...";

      // Generate pure pattern canvas (only what was drawn)
      const patternCanvas = buildPurePatternCanvas();
      const patternBase64 = patternCanvas.toDataURL('image/png');
      

      // Generate full ticket canvas (with overlay)
      const ticketCanvas = await finalizeTicket(true);
      const ticketBase64 = ticketCanvas.toDataURL('image/png');
      

      // Prepare data for backend
      const ticketData = {
        name: nameInput.value.trim(),
        email: emailInput.value.trim(),
        organization: orgInput.value.trim(),
        salutation: salutationInput ? salutationInput.value.trim() : '',
        patternBase64: patternBase64,
        ticketBase64: ticketBase64,
        ticketPageUrl: typeof hsbiTicket !== 'undefined' ? hsbiTicket.ticketPageUrl : window.location.href
      };
      

      // Send to backend
      const response = await fetch(typeof hsbiTicket !== 'undefined' ? hsbiTicket.ajaxUrl : 'views/assets/library/ajax.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(ticketData)
      });

      const result = await response.json();

      if (result.success) {
        // Clear saved form data after successful creation
        clearFormData();
        
        // Show Step 4 after successful creation
        showStep4();
      } else {
        throw new Error(result.error || 'Failed to create ticket');
      }

    } catch (error) {
      console.error('Error creating ticket:', error);
      updateStatus('Error creating ticket: ' + error.message);
    } finally {
      // Reset button state
      submitTicketBtn.disabled = false;
      submitTicketBtn.textContent = "Get ticket now";
    }
  });
}

async function finalizeTicket(testMode = false) {
  const overlay = await tryLoadOverlay();
  let W = overlay ? overlay.width || overlay.naturalWidth : 800;
  let H = overlay ? overlay.height || overlay.naturalHeight : 800;

  const canvas = document.createElement("canvas");
  canvas.width = W;
  canvas.height = H;
  const ctx = canvas.getContext("2d");

  // Draw board scaled
  const tmp = document.createElement("canvas");
  tmp.width = 800;
  tmp.height = 800;
  const tctx = tmp.getContext("2d");
  drawBoardToCanvas(tctx, 800);
  ctx.drawImage(tmp, 0, 0, W, H);

  if (overlay) {
    ctx.save();
    ctx.globalCompositeOperation = "multiply";
    ctx.drawImage(overlay, 0, 0, W, H);
    ctx.restore();
  }

  drawNameOrgBox(ctx, W, H, nameInput.value, orgInput.value);
  return canvas;
}

// ======= Init & tests =======
(async function init() {
  buildGrid(gridN);
  pushUndo();
  
  // Load saved form data
  loadFormData();
  
  // Set initial step1-active class
  document.body.classList.add("step1-active");
  
  // Add auto-save event listeners
  if (AUTO_FILL_ENABLED) {
    if (nameInput) {
      nameInput.addEventListener('input', saveFormData);
      nameInput.addEventListener('blur', saveFormData);
    }
    if (emailInput) {
      emailInput.addEventListener('input', saveFormData);
      emailInput.addEventListener('blur', saveFormData);
    }
    if (orgInput) {
      orgInput.addEventListener('input', saveFormData);
      orgInput.addEventListener('blur', saveFormData);
    }
    if (salutationInput) {
      salutationInput.addEventListener('input', saveFormData);
      salutationInput.addEventListener('blur', saveFormData);
    }
  }

  (function selfTests() {
    const ok = (m) => console.log("%c✔ TEST OK", "color:#22c55e;font-weight:700", m);
    const fail = (m, e) => console.error("✖ TEST FAIL:", m, e || "");
    const prev = getState();
    try {
      buildGrid(2);
      console.assert(
        board.children.length === 4,
        "Grid 2×2 should have 4 cells"
      );
      ok("Grid 2×2");
      buildGrid(5);
      console.assert(
        board.children.length === 25,
        "Grid 5×5 should have 25 cells"
      );
      ok("Grid 5×5");
      const c = document.createElement("canvas");
      c.width = 800;
      c.height = 800;
      const cx = c.getContext("2d");
      drawNameOrgBox(cx, 800, 800, "Darius Bange", "HSBI");
      ok("drawNameOrgBox");
      buildGrid(4);
      const first = board.children[0];
      first.style.backgroundColor = "#ff0000";
      const snap = snapshotImageData();
      rebuildFromSnapshot(8, snap);
      const colored = [...board.children].some(
        (cell) => (cell.style.backgroundColor || "") !== ""
      );
      console.assert(colored, "After resampling, there should be filled cells");
      ok("Resampling fills cells");
      (async () => {
        const can = await finalizeTicket(true);
        console.assert(
          can && can.width >= 1 && can.height >= 1,
          "finalizeTicket returns a valid canvas"
        );
        ok("finalizeTicket canvas");
      })();
    } catch (e) {
      fail("Self tests", e);
    } finally {
      applyState(prev);
    }
  })();
})();
