// student.js v1.4 (clean rebuild with practice edit/delete)
// Normalize duplicated /pages/pages/ early
if (window.location.pathname.includes('/pages/pages/')) {
  const fixed = window.location.pathname.replace('/pages/pages/', '/pages/');
  console.warn('[pages-dup-fix] redirecting to', fixed);
  window.location.replace(fixed + window.location.search);
}

// -------- Utility --------
function escapeHtml(str = '') {
  return str.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function getRootPath() {
  const path = window.location.pathname;
  if (path.includes('/pages/')) return path.split('/pages/')[0];
  return path.substring(0, path.lastIndexOf('/'));
}

// -------- Practice (state) --------
let practiceQuestions = [];            // Raw questions (API format)
let practiceExamMeta = { title: '', timer_minutes: 0 };
let generatedPracticeExam = null;      // Built only when starting

// -------- Init --------
document.addEventListener('DOMContentLoaded', () => {
  console.debug('[student.js] loaded v1.4');

  if (document.getElementById('exam-form')) initAntiCheatSystem();

  initEnterExamForm();
  initPracticePageWiring();
  initExamTakingPage();
});

// ===== Student Portal: Enter exam code =====
function initEnterExamForm() {
  const form = document.getElementById('enter-exam-form');
  if (!form) return;
  const codeInputs = form.querySelectorAll('.code-input');
  codeInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      input.value = input.value.toUpperCase();
      if (input.value.length === 1 && index < codeInputs.length - 1) codeInputs[index + 1].focus();
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !input.value && index > 0) codeInputs[index - 1].focus();
    });
    input.addEventListener('paste', e => {
      e.preventDefault();
      const txt = e.clipboardData.getData('text').trim().toUpperCase();
      if (txt.length === 6) {
        codeInputs.forEach((box, i) => { box.value = txt[i] || ''; });
        codeInputs[5].focus();
      }
    });
  });
  form.addEventListener('submit', e => {
    e.preventDefault();
    let code = '';
    codeInputs.forEach(i => code += i.value);
    if (code.length !== 6) return alert('กรุณากรอกรหัสข้อสอบ 6 หลักให้ครบ');
    const root = getRootPath();
    const target = `${root}/pages/take_exam.php?code=${code}`;
    console.debug('[redirect exam]', { root, target });
    window.location.assign(target);
  });

  const practiceBtn = document.getElementById('create-practice-btn');
  if (practiceBtn) practiceBtn.addEventListener('click', () => {
    const root = getRootPath();
    window.location.href = `${root}/pages/student_practice.php`;
  });
}

// ===== Practice Page Wiring =====
function initPracticePageWiring() {
  if (!document.querySelector('.exam-generator')) return;
  // identify student practice by heading text containing 'ฝึก'
  if (!document.querySelector('h1') || !document.querySelector('h1').textContent.includes('ฝึก')) return;

  document.getElementById('send-prompt-btn')?.addEventListener('click', () => handlePracticePrompt(false));
  const userPrompt = document.getElementById('user-prompt');
  userPrompt?.addEventListener('keypress', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handlePracticePrompt(false); }
  });
  document.getElementById('confirm-practice-exam-btn')?.addEventListener('click', startPracticeExam);
  document.getElementById('exam-type')?.addEventListener('change', toggleMixedOptions);
  document.getElementById('exam-timer')?.addEventListener('change', toggleCustomTimeInput);
  toggleMixedOptions();
  toggleCustomTimeInput();
}

function toggleMixedOptions() {
  const examTypeEl = document.getElementById('exam-type');
  if (!examTypeEl) return;
  const mixed = document.getElementById('mixed-options');
  const single = document.getElementById('single-type-options');
  if (examTypeEl.value === 'mixed') {
      mixed.classList.remove('hidden');
      if(single) single.classList.add('hidden');
  } else {
      mixed.classList.add('hidden');
      if(single) single.classList.remove('hidden');
  }
}
function toggleCustomTimeInput() {
  const timerEl = document.getElementById('exam-timer');
  if (!timerEl) return;
  const box = document.getElementById('custom-time-options');
  if (timerEl.value === 'custom') box.classList.remove('hidden'); else box.classList.add('hidden');
}

// ===== Practice Generation =====
async function handlePracticePrompt(isCorrection = false) {
  const userPromptTextarea = document.getElementById('user-prompt');
  const sendBtn = document.getElementById('send-prompt-btn');
  const chatHistory = document.getElementById('chat-history');
  const preview = document.getElementById('exam-preview');
  const confirmBtn = document.getElementById('confirm-practice-exam-btn');
  let prompt = userPromptTextarea.value.trim();
  if (!prompt) return alert('กรุณาป้อนคำสั่ง');

  const userMsg = document.createElement('div');
  userMsg.className = 'message user-message';
  userMsg.innerHTML = `<p>${escapeHtml(prompt)}</p>`;
  chatHistory.appendChild(userMsg);
  chatHistory.scrollTop = chatHistory.scrollHeight;

  userPromptTextarea.disabled = true; sendBtn.disabled = true; sendBtn.textContent = 'AI กำลังคิด...';
  preview.innerHTML = '<div class="loader"></div><p>กำลังสร้างข้อสอบ...</p>';
  confirmBtn.classList.add('hidden');

  try {
    const examType = document.getElementById('exam-type').value;
    const difficulty = document.getElementById('difficulty').value;
    const timerSel = document.getElementById('exam-timer').value;
    const customTime = document.getElementById('custom-time-input')?.value || '';
    
    let mcqCount = 0;
    let writtenCount = 0;

    if (examType === 'mixed') {
        mcqCount = document.getElementById('mcq-count')?.value || '';
        writtenCount = document.getElementById('written-count')?.value || '';
    } else {
        const total = document.getElementById('total-questions')?.value || '';
        if (examType === 'multiple_choice') {
            mcqCount = total;
        } else if (examType === 'written') {
            writtenCount = total;
        }
    }

    let timerMinutes = 0;
    if (timerSel === 'custom' && parseInt(customTime,10) > 0) timerMinutes = parseInt(customTime,10);

    const resp = await fetch('../api/generate_exam.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt, exam_type: examType, difficulty, is_correction: isCorrection, mcq_count: mcqCount, written_count: writtenCount })
    });
    if (!resp.ok) throw new Error(await resp.text());
    const result = await resp.json();
    if (!result.questions) throw new Error('API ไม่ส่ง questions กลับ');

    // store metadata & raw questions for editing
    practiceExamMeta = { title: result.exam_title || `ข้อสอบฝึกฝน: ${prompt.slice(0,50)}...`, timer_minutes: timerMinutes };
    practiceQuestions = result.questions.map(q => ({ ...q }));
    if (result.full_response) userPromptTextarea.dataset.rawJson = result.full_response;

    renderPracticePreview();
    confirmBtn.classList.remove('hidden');

    const aiMsg = document.createElement('div');
    aiMsg.className = 'message ai-message';
    aiMsg.innerHTML = `<p>สร้างข้อสอบแล้ว ตรวจสอบ/แก้ไขได้ หากเสร็จให้กด "เริ่มทำข้อสอบทันที" <button type=button class='show-raw-json' style='font-size:12px;margin-left:8px;'>ดู JSON</button></p>`;
    chatHistory.appendChild(aiMsg);
    aiMsg.querySelector('.show-raw-json').addEventListener('click', () => {
      if (!userPromptTextarea.dataset.rawJson) return alert('ไม่มี JSON');
      const showing = userPromptTextarea.classList.toggle('showing-json');
      userPromptTextarea.value = showing ? userPromptTextarea.dataset.rawJson : prompt;
    });

    const suggestionBox = document.getElementById('ai-suggestions');
    if (suggestionBox) suggestionBox.innerHTML = `<div class='tip-box'><h3>ไอเดียปรับปรุง</h3><ul>
      <li>เพิ่มหรือลดจำนวนข้อ: พิมพ์ "ปรับให้เหลือ 5 ข้อ"</li>
      <li>เปลี่ยนรูปแบบ: "เปลี่ยนเป็นแบบผสม ปรนัย 3 อัตนัย 2"</li>
      <li>ขออธิบายละเอียด: "เพิ่มเฉลยอัตนัยละเอียด"</li>
      <li>โฟกัสหัวข้อย่อย: "โฟกัสเรื่องสมการเชิงเส้น"</li>
    </ul></div>`;
  } catch (err) {
    preview.innerHTML = `<p class='error'>${escapeHtml(err.message)}</p>`;
    const aiMsg = document.createElement('div'); aiMsg.className='message ai-message'; aiMsg.innerHTML = '<p class="error">เกิดข้อผิดพลาด ลองใหม่</p>'; chatHistory.appendChild(aiMsg);
  } finally {
    userPromptTextarea.disabled = false; sendBtn.disabled = false; sendBtn.textContent = 'ส่งคำสั่ง'; chatHistory.scrollTop = chatHistory.scrollHeight;
  }
}

function renderPracticePreview() {
  const preview = document.getElementById('exam-preview');
  if (!preview) return;
  preview.innerHTML = '';
  practiceQuestions.forEach((q, idx) => {
    const block = document.createElement('div');
    block.className = 'question-block';
    block.dataset.index = idx;
    block.innerHTML = `
      <div style='display:flex;justify-content:space-between;align-items:flex-start;'>
        <p style='flex:1;'><b>${idx+1}.</b> ${escapeHtml(q.question_text)}</p>
        <div>
          <button type='button' class='mini-btn edit-btn' style='margin-right:6px;'>แก้ไข</button>
          <button type='button' class='mini-btn del-btn'>ลบ</button>
        </div>
      </div>`;
    const content = document.createElement('div');
    if (q.type === 'multiple_choice') {
      const ul = document.createElement('ul');
      Object.entries(q.options).forEach(([k,v]) => {
        const li = document.createElement('li');
        li.textContent = `${k}. ${v}` + (k === q.correct_answer ? ' (คำตอบที่ถูก)' : '');
        if (k === q.correct_answer) li.classList.add('correct');
        ul.appendChild(li);
      });
      content.appendChild(ul);
    } else {
      const ans = document.createElement('p');
      ans.innerHTML = `<b>แนวคำตอบ:</b> ${escapeHtml(q.correct_answer || 'ไม่ได้ระบุ')}`;
      content.appendChild(ans);
    }
    block.appendChild(content);
    preview.appendChild(block);
  });
  preview.querySelectorAll('.edit-btn').forEach(btn => btn.addEventListener('click', () => enterEditMode(btn.closest('.question-block'))));
  preview.querySelectorAll('.del-btn').forEach(btn => btn.addEventListener('click', () => deleteQuestion(btn.closest('.question-block'))));
}

function enterEditMode(block) {
  const idx = parseInt(block.dataset.index,10);
  const q = practiceQuestions[idx];
  block.innerHTML = '';
  const form = document.createElement('div');
  form.className = 'edit-area';
  form.innerHTML = `
    <label style='display:block;font-weight:bold;margin-bottom:4px;'>แก้ไขคำถามข้อ ${idx+1}</label>
    <textarea class='edit-question-text' style='width:100%;min-height:70px;margin-bottom:8px;'>${escapeHtml(q.question_text)}</textarea>
    <div class='choices-wrapper'></div>
    <div style='margin-top:8px;display:flex;gap:8px;'>
      <button type='button' class='mini-btn save-edit' style='background:#0d6efd;color:#fff;'>บันทึก</button>
      <button type='button' class='mini-btn cancel-edit'>ยกเลิก</button>
    </div>`;
  block.appendChild(form);
  const wrap = form.querySelector('.choices-wrapper');
  if (q.type === 'multiple_choice') {
    Object.entries(q.options).forEach(([k,v]) => {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:center;margin-bottom:4px;';
      row.innerHTML = `
        <input type='radio' name='correct_choice_${idx}' value='${k}' ${k===q.correct_answer?'checked':''} style='margin-right:6px;'>
        <span style='width:28px;font-weight:bold;'>${k}.</span>
        <input type='text' class='choice-input' data-key='${k}' value='${escapeHtml(v)}' style='flex:1;'>`;
      wrap.appendChild(row);
    });
  } else {
    wrap.innerHTML = `<label style='font-weight:bold;display:block;margin-bottom:4px;'>แนวคำตอบ (ถ้ามี)</label><textarea class='written-answer-edit' style='width:100%;min-height:60px;'>${escapeHtml(q.correct_answer||'')}</textarea>`;
  }
  form.querySelector('.cancel-edit').addEventListener('click', renderPracticePreview);
  form.querySelector('.save-edit').addEventListener('click', () => saveEdit(block, idx));
}

function saveEdit(block, idx) {
  const q = practiceQuestions[idx];
  const newText = block.querySelector('.edit-question-text').value.trim();
  if (!newText) return alert('กรุณากรอกคำถาม');
  if (q.type === 'multiple_choice') {
    const choiceInputs = block.querySelectorAll('.choice-input');
    const newOptions = {}; let empty = false;
    choiceInputs.forEach(inp => { const val = inp.value.trim(); if (!val) empty = true; newOptions[inp.dataset.key] = val; });
    if (empty) return alert('กรอกตัวเลือกให้ครบ');
    const correct = block.querySelector('input[type="radio"]:checked');
    if (!correct) return alert('เลือกคำตอบที่ถูก');
    q.options = newOptions; q.correct_answer = correct.value; q.question_text = newText;
  } else {
    q.correct_answer = block.querySelector('.written-answer-edit').value.trim();
    q.question_text = newText;
  }
  renderPracticePreview();
}

function deleteQuestion(block) {
  const idx = parseInt(block.dataset.index,10);
  if (!confirm('ลบคำถามข้อนี้?')) return;
  practiceQuestions.splice(idx,1);
  renderPracticePreview();
}

function startPracticeExam() {
  if (!practiceQuestions.length) return alert('ยังไม่มีข้อสอบ');
  generatedPracticeExam = {
    id: 'practice',
    title: practiceExamMeta.title,
    timer_minutes: practiceExamMeta.timer_minutes,
    server_time: Math.floor(Date.now()/1000),
    questions: practiceQuestions.map((q,i) => {
      if (q.type === 'multiple_choice') {
        const choices = Object.entries(q.options || {}).map(([key,text]) => ({ key, text, id: key }));
        return { id: 'p_'+(i+1), question_text: q.question_text, type: 'multiple_choice', choices, correct_answer: q.correct_answer || '' };
      }
      return { id: 'p_'+(i+1), question_text: q.question_text, type: 'written', choices: [], correct_answer: q.correct_answer || '' };
    })
  };
  sessionStorage.setItem('practiceExam', JSON.stringify(generatedPracticeExam));
  const path = window.location.pathname.replace(/(\/pages)+\//g, '/pages/');
  const parts = path.split('/').filter(p => p);
  const root = parts.length ? `/${parts[0]}` : '';
  window.location.href = `${root}/pages/take_exam.php`;
}

// ===== Exam taking (shared for real/practice) =====
function initExamTakingPage() {
  if (!document.getElementById('exam-form')) return;
  const params = new URLSearchParams(window.location.search);
  const code = params.get('code');
  const practiceData = sessionStorage.getItem('practiceExam');
  // If a real exam code is present, always load real exam and discard any leftover practice exam
  if (code) {
    if (practiceData) sessionStorage.removeItem('practiceExam');
    loadExam(code);
  } else if (practiceData) {
    displayExam(JSON.parse(practiceData));
  }
  document.getElementById('exam-form').addEventListener('submit', submitExam);
}

async function loadExam(code) {
  try {
    const resp = await fetch(`../api/get_exam.php?code=${code}`);
    if (!resp.ok) throw new Error('ไม่พบข้อสอบ');
    const exam = await resp.json();
    displayExam(exam);
  } catch (e) {
    document.getElementById('exam-header').innerHTML = `<h1>เกิดข้อผิดพลาด</h1><p>${escapeHtml(e.message)}</p>`;
  }
}

function displayExam(exam) {
  document.getElementById('exam-header').innerHTML = `<h1>${escapeHtml(exam.title)}</h1>`;
  const form = document.getElementById('exam-form');
  form.dataset.examId = exam.id;
  exam.questions.forEach((q,i) => {
    const block = document.createElement('div'); block.className='question-block';
    block.innerHTML = `<p><b>ข้อ ${i+1}:</b> ${escapeHtml(q.question_text)}</p>`;
    if (q.type === 'multiple_choice') {
      const ul = document.createElement('ul');
      q.choices.forEach(c => {
        const li = document.createElement('li');
        li.innerHTML = `<label><input type='radio' name='question_${q.id}' value='${c.id}' required> ${c.key}. ${escapeHtml(c.text)}</label>`;
        ul.appendChild(li);
      });
      block.appendChild(ul);
    } else {
      block.innerHTML += `<textarea name='question_${q.id}' class='written-answer' placeholder='พิมพ์คำตอบ...' required></textarea>`;
    }
    form.appendChild(block);
  });
  if (exam.timer_minutes > 0) setupTimer(exam.timer_minutes, exam.server_time); else document.getElementById('timer-container').classList.add('hidden');
}

// ===== Timer =====
let timerInterval; let timeExpired = false;
function setupTimer(minutes, serverTime) {
  const timerContainer = document.getElementById('timer-container');
  timerContainer.classList.remove('hidden');
  const total = minutes * 60; const progressBar = document.getElementById('timer-progress-bar');
  const display = document.getElementById('timer-countdown'); const circ = 2 * Math.PI * 45; progressBar.style.strokeDasharray = circ;
  let start = localStorage.getItem('examStartTime');
  if (!start) { start = serverTime; localStorage.setItem('examStartTime', start); }
  function tick() {
    const now = Math.floor(Date.now()/1000); const elapsed = now - start; const remain = Math.max(0, total - elapsed);
    const m = Math.floor(remain/60), s = remain % 60; display.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    const ratio = remain / total; progressBar.style.strokeDashoffset = circ * (1 - ratio);
    if (remain <= 0) {
      clearInterval(timerInterval); localStorage.removeItem('examStartTime'); timeExpired = true;
      alert('หมดเวลา! กรุณากรอกชื่อเพื่อบันทึกคะแนน');
      document.querySelectorAll('#exam-form [required]').forEach(el => el.removeAttribute('required'));
      document.getElementById('exam-form').requestSubmit();
    }
  }
  tick(); timerInterval = setInterval(tick, 1000);
}

// ===== Submit Exam =====
async function submitExam(e) {
  e.preventDefault();
  const form = e.target; const examId = form.dataset.examId;
  if (!examId || examId === 'practice') return submitPracticeExam(form);

  let studentName = prompt('กรุณาใส่ชื่อ-นามสกุลของคุณเพื่อบันทึกคะแนน:', 'นักเรียนนิรนาม');
  if (!studentName || !studentName.trim()) {
    if (timeExpired) studentName = 'นักเรียนนิรนาม'; else return;
  }
  clearInterval(timerInterval); localStorage.removeItem('examStartTime');

  const answers = []; const blocks = form.querySelectorAll('.question-block');
  blocks.forEach(b => {
    const input = b.querySelector('input[type="radio"], textarea');
    const qid = input.name.split('_')[1]; const isMCQ = input.type === 'radio';
    const checked = b.querySelector('input[type="radio"]:checked'); const textarea = b.querySelector('textarea');
    let val = null; if (isMCQ && checked) val = checked.value; else if (!isMCQ && textarea) val = textarea.value;
    answers.push({ question_id: qid, type: isMCQ ? 'multiple_choice' : 'written', answer: val });
  });
  try {
    const resp = await fetch('../api/submit_exam.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ exam_id: examId, student_name: studentName, answers, anti_cheat_report: getAntiCheatReport() }) });
    const result = await resp.json();
    if (!result.success) throw new Error(result.error || 'ส่งไม่สำเร็จ');
    sessionStorage.removeItem('antiCheatLogs'); antiCheatData.startTime = null;
    window.location.href = `exam_result.php?submission_id=${result.submission_id}`;
  } catch (err) { alert('เกิดข้อผิดพลาด: ' + err.message); }
}

function submitPracticeExam(form) {
  const practiceData = sessionStorage.getItem('practiceExam');
  if (!practiceData) { alert('ไม่พบข้อมูลข้อสอบฝึกฝน'); window.location.href='student_practice.php'; return; }
  const exam = JSON.parse(practiceData);
  const answers = []; form.querySelectorAll('.question-block').forEach(b => {
    const input = b.querySelector('input[type="radio"], textarea');
    const qid = input.name.split('_')[1]; const isMCQ = input.type === 'radio';
    const checked = b.querySelector('input[type="radio"]:checked'); const textarea = b.querySelector('textarea');
    let val = null; if (isMCQ && checked) val = checked.value; else if (!isMCQ && textarea) val = textarea.value;
    answers.push({ question_id: qid, answer: val });
  });
  const resultData = { title: exam.title, questions: [], score:0, total:0 };
  exam.questions.forEach(q => {
    const ansObj = answers.find(a => a.question_id === q.id); const val = ansObj ? ansObj.answer : null; let correct=false;
    if (q.type === 'multiple_choice') { resultData.total++; const selected = q.choices.find(c => c.id === val); const selKey = selected? selected.key: null; if (selKey && selKey === q.correct_answer) { correct=true; resultData.score++; }
      resultData.questions.push({ question_text: q.question_text, type: q.type, is_correct: correct, all_choices: q.choices, selected_key: selKey, correct_key: q.correct_answer });
    } else {
      resultData.questions.push({ question_text: q.question_text, type: q.type, is_correct: null, written_answer: val, correct_answer_text: q.correct_answer }); }
  });
  sessionStorage.setItem('practiceResult', JSON.stringify(resultData)); sessionStorage.removeItem('practiceExam'); window.location.href='practice_result.php';
}

// ===== Anti-Cheat (Enhanced) =====
let antiCheatData = { tabSwitches:0, rightClicks:0, keyboardShortcuts:0, fullscreenExits:0, startTime:null, suspicious:false };

function initAntiCheatSystem() {
  antiCheatData.startTime = Date.now();
  sessionStorage.removeItem('antiCheatLogs'); // Clear old logs

  // 1. Right Click
  document.addEventListener('contextmenu', e => { 
    e.preventDefault(); 
    antiCheatData.rightClicks++; 
    logViolation('Right Click');
    showWarning('ห้ามคลิกขวา!'); 
  });

  // 2. Tab Switching & Visibility
  document.addEventListener('visibilitychange', () => { 
    if (document.hidden) { 
      antiCheatData.tabSwitches++; 
      logViolation('Tab Switch (Hidden)');
      showWarning('ห้ามเปลี่ยนแท็บ!'); 
    }
  });

  // 3. Window Blur (Focus Lost) - Debounced to avoid double counting with visibilitychange
  window.addEventListener('blur', () => {
    // Give a small delay to check if it became hidden (which is handled above)
    setTimeout(() => {
      if (!document.hidden) {
        antiCheatData.tabSwitches++;
        logViolation('Window Focus Lost');
        // Optional: Don't show full warning for blur to be less annoying, or show it:
        // showWarning('ห้ามออกจากหน้าจอสอบ!'); 
      }
    }, 100);
  });

  // 4. Keyboard Shortcuts (Windows + Mac Support)
  document.addEventListener('keydown', e => {
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const cmd = isMac ? e.metaKey : e.ctrlKey; // Command on Mac, Ctrl on Windows
    const key = e.key.toUpperCase();

    if (
      e.key === 'F12' || 
      (cmd && e.shiftKey && ['I','C','J'].includes(key)) || // DevTools
      (cmd && ['U','S','A','P','F','R'].includes(key)) || // View Source, Save, Select All, Print, Find, Refresh
      e.key === 'F5' || 
      (e.altKey && e.key === 'Tab') || // Alt+Tab
      (isMac && e.metaKey && key === 'R') // Mac Refresh
    ) { 
      e.preventDefault(); 
      antiCheatData.keyboardShortcuts++; 
      logViolation(`Shortcut Blocked: ${e.key}`);
      showWarning('บล็อกคีย์ลัด!'); 
    }
  });
}

function logViolation(type) {
  const logs = JSON.parse(sessionStorage.getItem('antiCheatLogs') || '[]');
  logs.push({ type, time: new Date().toISOString() });
  sessionStorage.setItem('antiCheatLogs', JSON.stringify(logs));
  
  // Auto-flag as suspicious if thresholds exceeded
  if (antiCheatData.tabSwitches > 2 || antiCheatData.rightClicks > 4 || antiCheatData.keyboardShortcuts > 2) {
    antiCheatData.suspicious = true;
  }
}

function showWarning(msg){ 
  // Remove existing warning if any
  const existing = document.getElementById('ac-warning');
  if(existing) existing.remove();

  const o=document.createElement('div'); 
  o.id = 'ac-warning';
  o.style.cssText='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(220,53,69,0.95);z-index:10000;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:bold;text-align:center;padding:20px;backdrop-filter:blur(5px);'; 
  o.innerHTML=`<div><div style='font-size:64px;margin-bottom:20px;'>⚠️</div><div style='font-size:32px;margin-bottom:10px;'>${msg}</div><div style='font-size:18px;opacity:0.9;'>ระบบได้บันทึกพฤติกรรมนี้ไว้แล้ว</div></div>`; 
  document.body.appendChild(o); 
  setTimeout(()=>o.remove(), 3000);
} 

function getAntiCheatReport(){ 
  return { 
    tabSwitches: antiCheatData.tabSwitches,
    rightClicks: antiCheatData.rightClicks,
    keyboardShortcuts: antiCheatData.keyboardShortcuts,
    fullscreenExits: antiCheatData.fullscreenExits,
    examDuration: antiCheatData.startTime ? Date.now() - antiCheatData.startTime : 0,
    suspicious: antiCheatData.suspicious,
    logs: JSON.parse(sessionStorage.getItem('antiCheatLogs') || '[]') 
  }; 
}
