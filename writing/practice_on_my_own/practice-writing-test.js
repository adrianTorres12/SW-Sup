// practice-writing-test.js
// Handles single-part and full test flows. Uses window.PRACTICE_PAYLOAD injected by PHP.

(() => {
  const payload = window.PRACTICE_PAYLOAD || null;
  if (!payload) {
    document.body.innerHTML = "<p style='padding:20px'>Error: missing payload.</p>";
    return;
  }

  // DOM elements
  const qCounterEl = document.getElementById('question-counter');
  const timerEl = document.getElementById('timer');
  const directionsText = document.getElementById('directions-text');
  const contentBox = document.getElementById('content-box');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');

  // modes and state
  const MODE = payload.mode; // 'single' or 'full'
  const TEST_NUMBER = payload.test_number;
  const PARTS = payload.parts; // array of parts [{part_number, title, time_allowed_seconds, questions}]
  // state tracking:
  let currentPartIndex = 0; // index in PARTS array
  let currentQuestionIndex = 0; // index within current part questions
  let timeLeft = PARTS[0].time_allowed_seconds || 0;
  let timerInterval = null;

  // Storage keys:
  function singleStorageKey(partNumber) {
    return `practice_p${partNumber}_${TEST_NUMBER}`;
  }
  const fullStorageKey = `practice_full_${TEST_NUMBER}`;

  // ADD: Track locked parts so user cannot return to previous ones
  let lockedParts = JSON.parse(localStorage.getItem(`locked_parts_${TEST_NUMBER}`) || "[]");

  // Helper: save locked parts
  function saveLockedParts() {
    localStorage.setItem(`locked_parts_${TEST_NUMBER}`, JSON.stringify(lockedParts));
  }

  // Load stored answers (structure differs by mode)
  function loadStored() {
    try {
      if (MODE === 'single') {
        const key = singleStorageKey(PARTS[currentPartIndex].part_number);
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : {};
      } else {
        const raw = localStorage.getItem(fullStorageKey);
        return raw ? JSON.parse(raw) : {};
      }
    } catch (e) {
      console.warn("Failed parse stored answers", e);
      return {};
    }
  }
  let stored = loadStored(); // object mapping [partNumber][qIndex+1] -> answer OR for single {qIdx+1: answer}

  // Ensure storage shape for full mode
  if (MODE === 'full') {
    PARTS.forEach(p => {
      if (!stored[p.part_number]) stored[p.part_number] = {};
    });
  }

  // Format a mm:ss time
  function formatTime(s) {
    const mm = Math.floor(s/60).toString().padStart(2,'0');
    const ss = (s%60).toString().padStart(2,'0');
    return `${mm}:${ss}`;
  }

  // update nav buttons (centralized)
  function updateNavButtons() {
    const partObj = PARTS[currentPartIndex];

    // Prev: only enabled if not first question of the current part
    if (currentQuestionIndex === 0) {
      prevBtn.disabled = true;
      prevBtn.classList.add("disabled");
    } else {
      prevBtn.disabled = false;
      prevBtn.classList.remove("disabled");
    }

    // Next should always be available for navigation within part (label handled in render)
    nextBtn.disabled = false;
    nextBtn.classList.remove("disabled");
  }

  // Render current part + question UI
  function render() {
    const part = PARTS[currentPartIndex];
    const questions = part.questions || [];
    const q = questions[currentQuestionIndex];

    // header counter text
    qCounterEl.textContent = `Part ${part.part_number} — Question ${currentQuestionIndex+1} of ${questions.length}`;

    // directions text per part
    if (part.part_number === 1) {
      directionsText.textContent = "Write ONE sentence based on the picture. Use the TWO words or phrases under the picture. You may change the forms of the words and you may use them in any order.";
    } else if (part.part_number === 2) {
      directionsText.textContent = "Respond to the email. Write a short email following the instructions. Use appropriate tone and content.";
    } else {
      directionsText.textContent = "Write an opinion essay. Follow the prompt and write an essay.";
    }

    // clear content box then build UI depending on part
    contentBox.innerHTML = "";

    if (part.part_number === 1) {
      // image, keywords, answer textarea
      const imgBox = document.createElement('div'); imgBox.className = 'image-box';
      const img = document.createElement('img'); img.alt = 'question image';
      if (q.image_base64 && q.image_base64.length > 10) {
        const mime = (q.image_type && q.image_type.length) ? q.image_type : 'image/png';
        img.src = `data:${mime};base64,${q.image_base64}`;
      } else {
        img.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="800" height="300"><rect width="100%" height="100%" fill="#fbfdff"/><text x="50%" y="50%" fill="#9bb0d9" font-size="18" text-anchor="middle" dominant-baseline="middle">No image</text></svg>`);
      }
      imgBox.appendChild(img);
      contentBox.appendChild(imgBox);

      // keywords
      const kwWrap = document.createElement('div'); kwWrap.className = 'keywords';
      const k1 = document.createElement('div'); k1.className = 'kw'; k1.textContent = q.keyword1 || '';
      const k2 = document.createElement('div'); k2.className = 'kw'; k2.textContent = q.keyword2 || '';
      kwWrap.appendChild(k1); kwWrap.appendChild(k2);
      contentBox.appendChild(kwWrap);

      // answer textarea
      const ansWrap = document.createElement('div'); ansWrap.className = `answer-area part${part.part_number}`;
      const ta = document.createElement('textarea'); ta.id = 'answer-input';
      ta.placeholder = 'Write your sentence here...';
      ta.value = getStoredAnswer(part.part_number, currentQuestionIndex+1) || '';
      ansWrap.appendChild(ta);
      contentBox.appendChild(ansWrap);
    } else if (part.part_number === 2) {
      // instruction text, image (email), answer textarea
      const prompt = document.createElement('div'); prompt.className = 'prompt-box';
      prompt.innerHTML = `<strong>Instructions:</strong><div style="margin-top:6px;">${(q.instructions||'')}</div>`;
      contentBox.appendChild(prompt);

      if (q.image_base64 && q.image_base64.length > 10) {
        const imgBox = document.createElement('div'); imgBox.className = 'image-box';
        const img = document.createElement('img'); img.alt = 'email image';
        const mime = (q.image_type && q.image_type.length) ? q.image_type : 'image/png';
        img.src = `data:${mime};base64,${q.image_base64}`;
        imgBox.appendChild(img);
        contentBox.appendChild(imgBox);
      }

      const ansWrap = document.createElement('div'); ansWrap.className = `answer-area part${part.part_number}`;
      const ta = document.createElement('textarea'); ta.id = 'answer-input';
      ta.placeholder = 'Write your email response here...';
      ta.value = getStoredAnswer(part.part_number, currentQuestionIndex+1) || '';
      ansWrap.appendChild(ta);
      contentBox.appendChild(ansWrap);

    } else { // part 3 essay
      // prompt (question_text)
      const prompt = document.createElement('div'); prompt.className = 'prompt-box';
      prompt.innerHTML = `<strong>Prompt:</strong><div style="margin-top:8px;">${(q.prompt||'')}</div>`;
      contentBox.appendChild(prompt);

      const ansWrap = document.createElement('div'); ansWrap.className = `answer-area part${part.part_number}`;
      const ta = document.createElement('textarea'); ta.id = 'answer-input';
      ta.placeholder = 'Write your essay here...';
      ta.style.minHeight = '220px';
      ta.value = getStoredAnswer(part.part_number, currentQuestionIndex+1) || '';
      ansWrap.appendChild(ta);
      contentBox.appendChild(ansWrap);
    }

    // Next button label logic
    const isLastQuestionInPart = (currentQuestionIndex === (part.questions.length - 1));
    const isLastPart = (currentPartIndex === (PARTS.length - 1));
    if (isLastQuestionInPart && MODE === 'full' && !isLastPart) {
      nextBtn.innerHTML = 'Next part <i class="bi bi-arrow-right-circle"></i>';
      nextBtn.title = 'Proceed to next part (you cannot go back later)';
    } else if (isLastQuestionInPart && isLastPart) {
      nextBtn.innerHTML = 'Submit <i class="bi bi-check-circle"></i>';
      nextBtn.title = 'Submit test';
    } else {
      nextBtn.innerHTML = '<i class="bi bi-arrow-right-circle"></i>';
      nextBtn.title = 'Next';
    }

    // update timer display immediately
    timerEl.textContent = formatTime(timeLeft);

    // wire up textarea save events
    const taEl = document.getElementById('answer-input');
    if (taEl) {
      taEl.addEventListener('input', () => {
        setStoredAnswer(part.part_number, currentQuestionIndex+1, taEl.value);
        scheduleAutosave();
      });
    }

    // update nav buttons now
    updateNavButtons();
  }

  // get stored answer helper
  function getStoredAnswer(partNumber, qnum) {
    try {
      if (MODE === 'single') {
        const key = singleStorageKey(partNumber);
        const raw = localStorage.getItem(key);
        if (!raw) return '';
        const obj = JSON.parse(raw);
        return obj[qnum] || '';
      } else {
        const raw = localStorage.getItem(fullStorageKey);
        if (!raw) return '';
        const obj = JSON.parse(raw);
        return (obj[partNumber] && obj[partNumber][qnum]) ? obj[partNumber][qnum] : '';
      }
    } catch (e) {
      return '';
    }
  }

  function setStoredAnswer(partNumber, qnum, text) {
    try {
      if (MODE === 'single') {
        const key = singleStorageKey(partNumber);
        const raw = localStorage.getItem(key);
        const obj = raw ? JSON.parse(raw) : {};
        obj[qnum] = text;
        localStorage.setItem(key, JSON.stringify(obj));
      } else {
        const raw = localStorage.getItem(fullStorageKey);
        const obj = raw ? JSON.parse(raw) : {};
        if (!obj[partNumber]) obj[partNumber] = {};
        obj[partNumber][qnum] = text;
        localStorage.setItem(fullStorageKey, JSON.stringify(obj));
      }
      // also update in-memory
      if (MODE === 'single') {
        stored = stored || {};
        stored[qnum] = text;
      } else {
        stored = stored || {};
        if (!stored[partNumber]) stored[partNumber] = {};
        stored[partNumber][qnum] = text;
      }
    } catch (e) { console.warn('Could not set stored answer', e); }
  }

  // Auto-save throttle
  let autosaveTimer = null;
  function scheduleAutosave() {
    if (autosaveTimer) clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => {
      // nothing to do — data already in localStorage from setStoredAnswer
    }, 400);
  }

  // navigation handlers
  prevBtn.addEventListener('click', (ev) => {
    ev.preventDefault();

    // Prev now only moves within the same part. It never goes to previous part.
    const ta = document.getElementById('answer-input');
    if (ta) setStoredAnswer(PARTS[currentPartIndex].part_number, currentQuestionIndex+1, ta.value.trim());

    if (currentQuestionIndex > 0) {
      currentQuestionIndex--;
      render();
    } else {
      // at first question of part — do nothing (optionally show a small notice)
      // small visual feedback: flash disabled or a toast
      try {
        Swal.fire({ toast: true, position: 'top', timer: 1000, showConfirmButton: false, title: 'You cannot go to previous part.' });
      } catch (e) { /* ignore if Swal not available */ }
    }
  });

  nextBtn.addEventListener('click', (ev) => {
    ev.preventDefault();
    // save current answer
    const ta = document.getElementById('answer-input');
    if (ta) setStoredAnswer(PARTS[currentPartIndex].part_number, currentQuestionIndex+1, ta.value.trim());

    const part = PARTS[currentPartIndex];
    const questions = part.questions;
    const isLastQuestionInPart = (currentQuestionIndex === (questions.length - 1));
    const isLastPart = (currentPartIndex === (PARTS.length - 1));

    if (isLastQuestionInPart) {
      if (MODE === 'full' && !isLastPart) {
        // confirm transition to next part (cannot go back after confirm)
        Swal.fire({
          title: 'Proceed to next part?',
          text: 'When you move to the next part you will not be able to return to previous part of the full test.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, proceed',
          cancelButtonText: 'Cancel'
        }).then(res => {
          if (res.isConfirmed) {
            // stop timer for current part, save timeLeft in storage, then go to next part
            saveTimeLeftForPart(part.part_number);

            // lock the part we are leaving so user cannot return
            if (!lockedParts.includes(part.part_number)) {
              lockedParts.push(part.part_number);
              saveLockedParts();
            }

            currentPartIndex++;
            currentQuestionIndex = 0;

            // set timeLeft to next part allowed or restore if exists
            restoreTimeLeft();

            render();
            startTimer(); // restart timer for new part
          }
        });
      } else {
        // last question of last part (or single mode) -> submit
        confirmSubmit('manual');
      }
    } else {
      currentQuestionIndex++;
      render();
    }
  });

  // timer functions
  function startTimer() {
    // set timeLeft from saved store if exists, else set to allowed
    if (typeof timeLeft !== 'number' || timeLeft <= 0) {
      timeLeft = PARTS[currentPartIndex].time_allowed_seconds;
    }
    timerEl.textContent = formatTime(timeLeft);
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      timeLeft--;
      if (timeLeft < 0) timeLeft = 0;
      timerEl.textContent = formatTime(timeLeft);
      // persist timeLeft occasionally to localStorage for resilience
      persistTimeLeft();
      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        onTimeout();
      }
    }, 1000);
  }

  // persist time left per part
  function persistTimeLeft() {
    try {
      if (MODE === 'single') {
        const key = singleStorageKey(PARTS[currentPartIndex].part_number) + '_time';
        localStorage.setItem(key, String(timeLeft));
      } else {
        const key = fullStorageKey + '_times';
        const raw = localStorage.getItem(key);
        const obj = raw ? JSON.parse(raw) : {};
        obj[PARTS[currentPartIndex].part_number] = timeLeft;
        localStorage.setItem(key, JSON.stringify(obj));
      }
    } catch (e) { /* ignore */ }
  }

  function saveTimeLeftForPart(partNumber) {
    try {
      const key = fullStorageKey + '_times';
      const raw = localStorage.getItem(key);
      const obj = raw ? JSON.parse(raw) : {};
      obj[partNumber] = timeLeft;
      localStorage.setItem(key, JSON.stringify(obj));
    } catch (e) {}
  }
  function restoreTimeLeft() {
    try {
      if (MODE === 'single') {
        const key = singleStorageKey(PARTS[currentPartIndex].part_number) + '_time';
        const raw = localStorage.getItem(key);
        timeLeft = raw ? parseInt(raw,10) : PARTS[currentPartIndex].time_allowed_seconds;
      } else {
        const key = fullStorageKey + '_times';
        const raw = localStorage.getItem(key);
        const obj = raw ? JSON.parse(raw) : {};
        const pnum = PARTS[currentPartIndex].part_number;
        timeLeft = (obj && obj[pnum]) ? parseInt(obj[pnum],10) : PARTS[currentPartIndex].time_allowed_seconds;
      }
    } catch (e) {
      timeLeft = PARTS[currentPartIndex].time_allowed_seconds;
    }
    timerEl.textContent = formatTime(timeLeft);
  }

  // when time expires
  function onTimeout() {
    // save current field then auto submit current part or whole test
    const ta = document.getElementById('answer-input');
    if (ta) setStoredAnswer(PARTS[currentPartIndex].part_number, currentQuestionIndex+1, ta.value.trim());

    Swal.fire({
      title: 'Stop writing',
      text: 'Time is up for this part. Your answers will be submitted automatically for the parts completed.',
      icon: 'warning',
      allowOutsideClick: false
    }).then(() => {
      // if in full mode and not last part: auto move to next part and continue (or submit if last).
      if (MODE === 'full' && currentPartIndex < PARTS.length - 1) {
        // mark this part as finished and proceed to next
        saveTimeLeftForPart(PARTS[currentPartIndex].part_number);

        // lock this part so can't go back
        if (!lockedParts.includes(PARTS[currentPartIndex].part_number)) {
          lockedParts.push(PARTS[currentPartIndex].part_number);
          saveLockedParts();
        }

        currentPartIndex++;
        currentQuestionIndex = 0;
        restoreTimeLeft();
        render();
        startTimer();
      } else {
        // submit entire attempt
        doSubmit('timeout');
      }
    });
  }

  // confirm submit
  function confirmSubmit(triggerType) {
    const title = triggerType === 'timeout' ? 'Time up' : 'Submit test?';
    const text = triggerType === 'timeout' ? 'Your answers will be submitted.' : 'Are you sure you want to submit?';
    Swal.fire({
      title,
      text,
      icon: triggerType === 'timeout' ? 'warning' : 'question',
      showCancelButton: triggerType === 'manual',
      confirmButtonText: triggerType === 'manual' ? 'Yes, submit' : 'OK'
    }).then((res) => {
      if (triggerType === 'timeout' || (res.isConfirmed && triggerType === 'manual')) {
        doSubmit(triggerType === 'timeout' ? 'timeout' : 'manual');
      }
    });
  }

  // Assemble payload and send to practice-submit.php
  async function doSubmit(trigger) {
    // stop timer
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }

    // gather answers from localStorage (final snapshot)
    let answersToSend = {};
    if (MODE === 'single') {
      const key = singleStorageKey(PARTS[currentPartIndex].part_number);
      const raw = localStorage.getItem(key);
      try { answersToSend = raw ? JSON.parse(raw) : {}; } catch { answersToSend = {}; }
    } else {
      const raw = localStorage.getItem(fullStorageKey);
      try { answersToSend = raw ? JSON.parse(raw) : {}; } catch { answersToSend = {}; }
    }

    // time lefts per part
    const times = {};
    if (MODE === 'single') {
      times[PARTS[currentPartIndex].part_number] = timeLeft;
    } else {
      const raw = localStorage.getItem(fullStorageKey + '_times');
      try {
        Object.assign(times, raw ? JSON.parse(raw) : {});
      } catch { /* ignore */ }
      // ensure current part is included
      times[PARTS[currentPartIndex].part_number] = timeLeft;
    }

    // show loading
    Swal.fire({ title: 'Submitting...', html: 'Please wait', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

    const body = {
      test_number: TEST_NUMBER,
      mode: MODE,
      trigger: trigger,
      times: times,
      answers: answersToSend,
      received_at: new Date().toISOString()
    };

    try {
      const resp = await fetch('practice-submit.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(body)
      });
      const json = await resp.json();
      if (json.success) {
        // clear appropriate localStorage keys
        if (MODE === 'single') {
          const key = singleStorageKey(PARTS[currentPartIndex].part_number);
          localStorage.removeItem(key);
          localStorage.removeItem(key + '_time');
        } else {
          localStorage.removeItem(fullStorageKey);
          localStorage.removeItem(fullStorageKey + '_times');
          // keep lockedParts? we can remove lockedParts if you want; current behavior preserves them
        }
        Swal.fire({ icon:'success', title:'Submitted', text: json.message || 'Saved locally.' }).then(() => {
          // redirect to review page (review-writing.php) for the first part (or single part)
          // review-writing.php expects ?test_number=X&part=Y (if full, show first part by default but review can switch)
          const reviewPart = MODE === 'single' ? PARTS[currentPartIndex].part_number : PARTS[0].part_number;
          window.location.href = `review-writing.php?test_number=${TEST_NUMBER}&part=${reviewPart}&mode=${MODE}`;
        });
      } else {
        Swal.fire({ icon:'error', title:'Error', text: json.message || 'Submission failed.'});
      }
    } catch (e) {
      console.error(e);
      Swal.fire({ icon:'error', title:'Network error', text: 'Could not submit your answers.' });
    }
  }

  // on start: restore timeLeft for first part and start countdown after 5..4..3
  function startWithCountdown() {
    let n = 5;
    Swal.fire({
      title: 'The test will begin in',
      html: `<div id="countdown-number" style="font-size:48px;font-weight:800;color:#1b63d8">${n}</div>`,
      allowOutsideClick:false,
      showConfirmButton:false,
      didOpen: () => {
        const interval = setInterval(() => {
          n--;
          const el = document.getElementById('countdown-number');
          if (el) el.textContent = n > 0 ? n : 'Go!';
          if (n <= 0) {
            clearInterval(interval);
            setTimeout(() => { Swal.close(); startTimer(); }, 400);
          }
        }, 1000);
      }
    });
  }

  // restore initial time left
  restoreTimeLeft();

  // autosave every 5s to localStorage (most answers already saved on input)
  setInterval(() => {
    // nothing in particular; data already in localStorage due to setStoredAnswer
  }, 5000);

  // Start UI
  render();
  startWithCountdown();

  // prevent accidental close if there are answers
  window.addEventListener('beforeunload', (e) => {
    let hasData = false;
    if (MODE === 'single') {
      const key = singleStorageKey(PARTS[currentPartIndex].part_number);
      const raw = localStorage.getItem(key);
      if (raw) {
        try {
          const obj = JSON.parse(raw);
          hasData = Object.values(obj).some(v => v && v.length > 0);
        } catch {}
      }
    } else {
      const raw = localStorage.getItem(fullStorageKey);
      if (raw) {
        try {
          const obj = JSON.parse(raw);
          hasData = Object.keys(obj).some(p => Object.values(obj[p]||{}).some(v=>v && v.length>0));
        } catch {}
      }
    }
    if (hasData) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

})();