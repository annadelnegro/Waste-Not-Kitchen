document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('profile-edit-modal');
  if (!modal) return;

  const saveBtn = document.getElementById('edit-save');
  const cancelBtn = document.getElementById('edit-cancel');
  const labelEl = document.getElementById('edit-label');
  const inputEl = document.getElementById('edit-input');
  const errEl = document.getElementById('edit-error');

  let currentField = null;

  function showError(msg){ errEl.textContent = msg; errEl.style.display = 'block'; }
  function clearError(){ errEl.textContent = ''; errEl.style.display = 'none'; }

  // Open modal when any edit button is clicked
  document.querySelectorAll('.edit-field').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const field = btn.dataset.field;
      if (!field) return;
      currentField = field;
      // populate input from the display value
      const display = document.getElementById('profile-' + field);
      let val = '';
      if (display) {
        val = display.textContent.trim();
        if (val === '-') val = '';
      }
      // set label
      const pretty = (field === 'full_name') ? 'Full Name' : (field === 'phone' ? 'Phone Number' : 'Address');
      labelEl.textContent = pretty;
      inputEl.value = val;
      inputEl.type = (field === 'phone') ? 'tel' : 'text';
      clearError();
      modal.style.display = 'flex';
      inputEl.focus();
    });
  });

  cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    clearError();
    currentField = null;
  });

  saveBtn.addEventListener('click', async () => {
    clearError();
    if (!currentField) { showError('No field selected'); return; }
    const value = inputEl.value.trim();
    // Basic validation
    if (currentField === 'full_name' && value.length < 1) { showError('Name required'); return; }
    if (currentField === 'phone' && value.length < 4) { showError('Enter a valid phone'); return; }
    if (currentField === 'address' && value.length < 3) { showError('Enter an address'); return; }

    try{
      saveBtn.disabled = true;
      const payload = { field: currentField, value };
      const res = await fetch('../Controller.php?action=update_profile', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload), credentials: 'same-origin' });
      const j = await res.json();
      if (j.status === 'success'){
        // update UI in place
        const display = document.getElementById('profile-' + currentField);
        if (display) display.textContent = value || '-';
        modal.style.display = 'none';
        currentField = null;
      } else {
        showError(j.message || 'Error saving profile');
      }
    }catch(err){ showError('Network error'); }
    finally{ saveBtn.disabled = false; }
  });
});
