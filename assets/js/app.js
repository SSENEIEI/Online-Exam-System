document.addEventListener('DOMContentLoaded', () => {
    // Check if we are on the teacher portal page by looking for a specific element
    if (!document.getElementById('exam-type')) {
        return; // Exit if this is not the teacher portal page
    }

    const examTypeSelect = document.getElementById('exam-type');
    const mixedOptionsDiv = document.getElementById('mixed-options');
    const sendPromptBtn = document.getElementById('send-prompt-btn');
    const userPromptTextarea = document.getElementById('user-prompt');
    const chatHistoryDiv = document.getElementById('chat-history');
    const examPreviewDiv = document.getElementById('exam-preview');
    const aiSuggestionsDiv = document.getElementById('ai-suggestions');
    const confirmExamBtn = document.getElementById('confirm-exam-btn');
    const examTimerSelect = document.getElementById('exam-timer');
    const customTimeOptionsDiv = document.getElementById('custom-time-options');

    let currentExamData = null; // Variable to store the latest exam data from AI
    
    // Simple HTML escaper
    function escapeHtml(str=''){return str.replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));}

    // --- Event Listeners ---

    if (examTypeSelect) {
        examTypeSelect.addEventListener('change', () => {
            const singleOptionsDiv = document.getElementById('single-type-options');
            if (examTypeSelect.value === 'mixed') {
                mixedOptionsDiv.classList.remove('hidden');
                if(singleOptionsDiv) singleOptionsDiv.classList.add('hidden');
            } else {
                mixedOptionsDiv.classList.add('hidden');
                if(singleOptionsDiv) singleOptionsDiv.classList.remove('hidden');
            }
        });
    }

    if (examTimerSelect) {
        examTimerSelect.addEventListener('change', () => {
            if (examTimerSelect.value === 'custom') {
                customTimeOptionsDiv.classList.remove('hidden');
            } else {
                customTimeOptionsDiv.classList.add('hidden');
            }
        });
    }
    
    if (sendPromptBtn) {
        sendPromptBtn.addEventListener('click', handleSendPrompt);
    }

    if (userPromptTextarea) {
        userPromptTextarea.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSendPrompt();
            }
        });
    }

    if (confirmExamBtn) {
        confirmExamBtn.addEventListener('click', saveAndRedirect);
    }

    // --- Functions ---

    async function handleSendPrompt() {
        const userPrompt = userPromptTextarea.value.trim();
        if (!userPrompt) return;

        addMessageToChat(userPrompt, 'user');
        userPromptTextarea.value = '';
        showLoadingState();

        try {
            const examType = examTypeSelect.value;
            let mcqCount = 0;
            let writtenCount = 0;

            if (examType === 'mixed') {
                mcqCount = document.getElementById('mcq-count').value;
                writtenCount = document.getElementById('written-count').value;
            } else {
                const total = document.getElementById('total-questions').value;
                if (examType === 'multiple_choice') {
                    mcqCount = total;
                } else if (examType === 'written') {
                    writtenCount = total;
                }
            }

            const requestBody = {
                prompt: userPrompt,
                difficulty: document.getElementById('difficulty').value,
                exam_type: examType,
                mcq_count: mcqCount,
                written_count: writtenCount,
                history: [] 
            };

            const response = await fetch('../api/generate_exam.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, details: ${errorText}`);
            }

            const result = await response.json();
            currentExamData = result; 
            
            addMessageToChat("นี่คือตัวอย่างข้อสอบที่สร้างให้ครับ โปรดตรวจสอบและแจ้งถ้าต้องการแก้ไข", 'ai');
            renderExamPreview(result);

        } catch (error) {
            console.error('Error fetching from API:', error);
            addMessageToChat('ขออภัยครับ เกิดข้อผิดพลาดในการเชื่อมต่อกับ AI', 'ai');
            examPreviewDiv.innerHTML = '<p class="placeholder">เกิดข้อผิดพลาด โปรดลองอีกครั้ง</p>';
        }
    }

    function addMessageToChat(message, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', `${sender}-message`);
        const p = document.createElement('p');
        p.textContent = message;
        messageDiv.appendChild(p);
        chatHistoryDiv.appendChild(messageDiv);
        chatHistoryDiv.scrollTop = chatHistoryDiv.scrollHeight;
    }

    function showLoadingState() {
        examPreviewDiv.innerHTML = '<p class="placeholder">AI กำลังสร้างข้อสอบ... กรุณารอสักครู่</p>';
        aiSuggestionsDiv.innerHTML = '';
        confirmExamBtn.classList.add('hidden');
    }

    function renderExamPreview(data) {
        if (!data || !data.questions) {
            examPreviewDiv.innerHTML = '<p class="placeholder">AI ไม่สามารถสร้างข้อสอบตามคำสั่งได้ โปรดลองใช้คำสั่งที่ชัดเจนกว่านี้</p>';
            return;
        }

        examPreviewDiv.innerHTML = '';

        data.questions.forEach((q, idx) => {
            const questionBlock = document.createElement('div');
            questionBlock.classList.add('question-block');
            questionBlock.dataset.index = idx;

            questionBlock.innerHTML = `
              <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <p style="flex:1;"><b>${idx+1}.</b> ${escapeHtml(q.question_text)}</p>
                <div>
                  <button type="button" class="mini-btn edit-btn" style="margin-right:6px;">แก้ไข</button>
                  <button type="button" class="mini-btn del-btn">ลบ</button>
                </div>
              </div>`;

            // choices / answer display
            const content = document.createElement('div');
            if (q.type === 'multiple_choice' && q.options) {
                const ul = document.createElement('ul');
                Object.entries(q.options).forEach(([key,value]) => {
                    const li = document.createElement('li');
                    li.textContent = `${key}. ${value}` + (key === q.correct_answer ? ' (คำตอบที่ถูก)' : '');
                    if (key === q.correct_answer) li.classList.add('correct');
                    ul.appendChild(li);
                });
                content.appendChild(ul);
            } else {
                const ans = document.createElement('p');
                ans.innerHTML = `<b>แนวคำตอบ:</b> ${escapeHtml(q.correct_answer || 'ไม่ได้ระบุ')}`;
                content.appendChild(ans);
            }
            questionBlock.appendChild(content);
            examPreviewDiv.appendChild(questionBlock);
        });

        // bind edit/delete
        examPreviewDiv.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => enterEditModeTeacher(parseInt(btn.closest('.question-block').dataset.index,10)));
        });
        examPreviewDiv.querySelectorAll('.del-btn').forEach(btn => {
            btn.addEventListener('click', () => deleteQuestionTeacher(parseInt(btn.closest('.question-block').dataset.index,10)));
        });

        if (data.suggestions) {
            aiSuggestionsDiv.innerHTML = `<strong>ข้อเสนอแนะจาก AI:</strong> ${data.suggestions}`;
        }

        confirmExamBtn.classList.remove('hidden');
    }

    async function saveAndRedirect() {
        if (!currentExamData) {
            alert('ไม่มีข้อมูลข้อสอบที่จะบันทึก');
            return;
        }

        confirmExamBtn.disabled = true;
        confirmExamBtn.textContent = 'กำลังบันทึก...';

        const timerType = document.getElementById('exam-timer').value;
        const timerValue = document.getElementById('custom-time-input').value;

        try {
            const payload = {
                exam_data: currentExamData,
                timer: { type: timerType, value: timerValue }
            };
            console.debug('[save_exam] payload', payload);
            const response = await fetch('../api/save_exam.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const text = await response.text();
            let result;
            try { result = JSON.parse(text); } catch(parseErr){
                throw new Error('Server returned invalid JSON: ' + text.substring(0,300));
            }
            if (!response.ok || !result.success) {
                console.error('Save exam failure raw result:', result);
                // Suggest permissions fix when common symptom (empty or malformed)
                if (!result || typeof result !== 'object') {
                    throw new Error('ไม่สามารถบันทึกได้ (อาจเป็นสิทธิ์โฟลเดอร์ logs). ลองปรับสิทธิ์โฟลเดอร์ logs เป็น 775 หรือ 777 แล้วลองใหม่');
                }
                throw new Error((result.error || result.details) + ' (หากเห็นปัญหาเรื่อง Permission ให้ chmod โฟลเดอร์ logs)');
            }

            if (result.success && result.exam_id) {
                window.location.href = `dashboard.php?exam_id=${result.exam_id}`;
            } else {
                throw new Error(result.error || 'Invalid response from server.');
            }
        } catch (error) {
            console.error('Error saving exam:', error);
            alert('เกิดข้อผิดพลาดในการบันทึกชุดข้อสอบ: ' + error.message);
            confirmExamBtn.disabled = false;
            confirmExamBtn.textContent = 'ยืนยันและสร้างชุดข้อสอบ';
        }
    }

    function enterEditModeTeacher(idx){
        const q = currentExamData.questions[idx];
        const block = examPreviewDiv.querySelector(`.question-block[data-index="${idx}"]`);
        if(!block) return;
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
                row.style.cssText='display:flex;align-items:center;margin-bottom:4px;';
                row.innerHTML = `
                  <input type='radio' name='correct_choice_${idx}' value='${k}' ${k===q.correct_answer?'checked':''} style='margin-right:6px;'>
                  <span style='width:28px;font-weight:bold;'>${k}.</span>
                  <input type='text' class='choice-input' data-key='${k}' value='${escapeHtml(v)}' style='flex:1;'>`;
                wrap.appendChild(row);
            });
        } else {
            wrap.innerHTML = `<label style='font-weight:bold;display:block;margin-bottom:4px;'>แนวคำตอบ (ถ้ามี)</label><textarea class='written-answer-edit' style='width:100%;min-height:60px;'>${escapeHtml(q.correct_answer||'')}</textarea>`;
        }
        form.querySelector('.cancel-edit').addEventListener('click', () => renderExamPreview(currentExamData));
        form.querySelector('.save-edit').addEventListener('click', () => saveEditTeacher(idx));
    }

    function saveEditTeacher(idx){
        const q = currentExamData.questions[idx];
        const block = examPreviewDiv.querySelector(`.question-block[data-index="${idx}"]`);
        const newText = block.querySelector('.edit-question-text').value.trim();
        if(!newText) return alert('กรุณากรอกคำถาม');
        if(q.type === 'multiple_choice'){
            const choiceInputs = block.querySelectorAll('.choice-input');
            const newOptions = {}; let empty=false;
            choiceInputs.forEach(inp=>{ const val=inp.value.trim(); if(!val) empty=true; newOptions[inp.dataset.key]=val; });
            if(empty) return alert('กรอกตัวเลือกให้ครบ');
            const correct = block.querySelector('input[type="radio"]:checked');
            if(!correct) return alert('เลือกคำตอบที่ถูก');
            q.options = newOptions; q.correct_answer = correct.value; q.question_text = newText;
        } else {
            q.correct_answer = block.querySelector('.written-answer-edit').value.trim();
            q.question_text = newText;
        }
        renderExamPreview(currentExamData);
    }

    function deleteQuestionTeacher(idx){
        if(!confirm('ลบคำถามข้อนี้?')) return;
        currentExamData.questions.splice(idx,1);
        // re-number
        currentExamData.questions.forEach((q,i)=>{ q.question_number = i+1; });
        renderExamPreview(currentExamData);
    }
});
