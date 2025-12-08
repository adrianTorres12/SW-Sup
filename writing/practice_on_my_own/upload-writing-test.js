// upload-writing-test.js
document.addEventListener('DOMContentLoaded', function () {
  // Elements
  const partSelect = document.getElementById('part');
  const part1Box = document.getElementById('part1');
  const part2Box = document.getElementById('part2');
  const part3Box = document.getElementById('part3');
  const testNumberInput = document.getElementById('test_number');
  const existingTests = window.EXISTING_TESTS || [];
  const suggestBtn = document.getElementById('suggestNext');

  // --- NEW: remove any required attributes initially to avoid "not focusable" errors
  (function removeInitialRequireds(){
    const els = document.querySelectorAll('#uploadForm [required]');
    els.forEach(el => {
      el.removeAttribute('required');
    });
  })();

  // Show/hide part groups
  function showPart(n) {
    // hide all
    part1Box.style.display = 'none';
    part2Box.style.display = 'none';
    part3Box.style.display = 'none';

    // remove any dynamic requireds from hidden sections
    function removeReqIn(containerId){
      document.querySelectorAll(`#${containerId} input, #${containerId} textarea, #${containerId} select`).forEach(el => {
        el.removeAttribute('required');
      });
    }
    removeReqIn('part1');
    removeReqIn('part2');
    removeReqIn('part3');

    if (n === '1') {
      part1Box.style.display = 'block';
      // set required on the fields needed for part1
      document.querySelectorAll('#part1 input, #part1 textarea')
              .forEach(el => {
                // keywords and answers should be required (images optional)
                if (el.name && (el.name.indexOf('p1_keyword') !== -1 || el.name.indexOf('p1_answer') !== -1)) {
                  el.setAttribute('required','true');
                } else {
                  el.removeAttribute('required');
                }
              });
    }
    if (n === '2') {
      part2Box.style.display = 'block';
      // set required for p2: images, instructions, suggested answer
      document.querySelectorAll('#part2 input, #part2 textarea')
              .forEach(el => {
                if (el.type === 'file') {
                  el.setAttribute('required','true');
                } else {
                  // instructions and suggested answer required
                  el.setAttribute('required','true');
                }
              });
    }
    if (n === '3') {
      part3Box.style.display = 'block';
      // set required for p3 fields
      document.querySelectorAll('#part3 textarea').forEach(el => {
        el.setAttribute('required','true');
      });
    }
  }
  partSelect.addEventListener('change', e => showPart(e.target.value));

  // Suggest next test number
  suggestBtn?.addEventListener('click', (ev) => {
    ev.preventDefault();
    let next = 1;
    if (existingTests.length > 0) {
      next = Math.max(...existingTests) + 1;
    }
    testNumberInput.value = next;
  });

  // Preview images for part1 and part2 (uses FileReader)
  function setupPreview(inputId, previewId) {
    const inp = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!inp || !preview) return;
    inp.addEventListener('change', () => {
      const file = inp.files[0];
      if (!file) { preview.style.display = 'none'; return; }
      const reader = new FileReader();
      reader.onload = (ev) => {
        preview.style.display = 'block';
        preview.querySelector('img').src = ev.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  // For multiple file inputs in part1 and part2, attach previews dynamically
  for (let i=1;i<=5;i++){
    setupPreview('p1_image_'+i, 'p1_preview_'+i);
  }
  for (let i=1;i<=2;i++){
    setupPreview('p2_image_'+i, 'p2_preview_'+i);
  }

  // Simple client validation before submit
  const form = document.getElementById('uploadForm');
  form.addEventListener('submit', function(e) {
    // prevent default native validation — we'll do custom
    e.preventDefault();

    // validate test number
    const testNum = parseInt(testNumberInput.value,10);
    if (!testNum || testNum <= 0) {
      Swal.fire({icon:'warning', title:'Invalid test number', text:'Please enter a test number greater than 0.'});
      return;
    }

    const part = partSelect.value;
    if (!part) {
      Swal.fire({icon:'warning', title:'Select part', text:'Please choose which part to upload.'});
      return;
    }

    // Part-specific required fields
    if (part === '1') {
      // 5 questions: keywords and suggested required; images optional but recommended
      for (let i=1;i<=5;i++){
        const k1 = document.getElementById('p1_keyword1_'+i).value.trim();
        const k2 = document.getElementById('p1_keyword2_'+i).value.trim();
        const ans = document.getElementById('p1_answer_'+i).value.trim();
        if (!k1 || !k2 || !ans) {
          Swal.fire({icon:'warning', title:'Missing fields', text:`Please complete keywords and suggested answer for question ${i}.`});
          return;
        }
      }
    } else if (part === '2') {
      for (let i=1;i<=2;i++){
        const instr = document.getElementById('p2_instructions_'+i).value.trim();
        const ans = document.getElementById('p2_answer_'+i).value.trim();
        const fileInp = document.getElementById('p2_image_'+i);
        if (!instr || !ans || !(fileInp && fileInp.files && fileInp.files.length>0)) {
          Swal.fire({icon:'warning', title:'Missing fields', text:`Please complete instructions, upload email image and suggested answer for email ${i}.`});
          return;
        }
      }
    } else if (part === '3') {
      const q = document.getElementById('p3_question').value.trim();
      const ans = document.getElementById('p3_answer').value.trim();
      if (!q || !ans) {
        Swal.fire({icon:'warning', title:'Missing fields', text:'Please provide the essay question and suggested answer.'});
        return;
      }
    }

    // pass: show "Uploading" modal and then submit the form
    Swal.fire({title:'Uploading...', text:'Please wait while we save the test.', allowOutsideClick:false, didOpen: () => { Swal.showLoading(); }});

    // Actually submit the form (after our custom checks)
    form.submit();
  });

});
