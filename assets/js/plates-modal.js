document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.querySelector('.open-add-plate');
  if (!openBtn) return;
  const modal = document.getElementById('add-plate-modal');
  const cancel = document.getElementById('plate-cancel');
  const save = document.getElementById('plate-save');
  const errEl = document.getElementById('plate-error');

  function open(){ modal.style.display='flex'; errEl.style.display='none'; }
  function close(){ modal.style.display='none'; }

  openBtn.addEventListener('click', open);
  cancel.addEventListener('click', close);

  function showError(msg){ errEl.textContent = msg; errEl.style.display = 'block'; }

  function formatLocalToSQL(v){ // v: 'YYYY-MM-DD' or 'YYYY-MM-DDTHH:MM'
    if (!v) return null;
    // If datetime-local value (contains T), convert to 'YYYY-MM-DD HH:MM:SS'
    if (v.indexOf('T') !== -1) {
      return v.replace('T',' ') + ':00';
    }
    // If date-only (YYYY-MM-DD), assume midnight
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) {
      return v + ' 00:00:00';
    }
    return v;
  }

  // Expose an editor opener so other scripts can pre-populate the modal for editing
  window.openPlateEditor = function(plate){
    if (!modal) return;
    // populate fields
    document.getElementById('plate-title').value = plate.title || '';
    document.getElementById('plate-desc').value = plate.description || '';
    document.getElementById('plate-price').value = (typeof plate.price !== 'undefined' && plate.price !== null) ? Number(plate.price).toFixed(2) : '';
    document.getElementById('plate-qty').value = (typeof plate.quantity !== 'undefined' && plate.quantity !== null) ? String(plate.quantity) : '';
    // strip time portion if present
    if (plate.available_from) document.getElementById('plate-from').value = plate.available_from.substr(0,10);
    if (plate.available_until) document.getElementById('plate-to').value = plate.available_until.substr(0,10);
    modal.dataset.editId = String(plate.id || '');
    // change heading
    const h = modal.querySelector('.modal-content h2');
    if (h) h.textContent = 'Edit Plate';
    modal.style.display = 'flex';
    errEl.style.display = 'none';
  };

  save.addEventListener('click', async () => {
    const title = document.getElementById('plate-title').value.trim();
    const desc = document.getElementById('plate-desc').value.trim();
    const price = document.getElementById('plate-price').value.trim();
    const qty = document.getElementById('plate-qty').value.trim();
    const from = document.getElementById('plate-from').value;
    const to = document.getElementById('plate-to').value;

    // Basic validation
    if (!title) { showError('Plate title is required'); return; }
    if (!/^\d{1,3}\.\d{2}$/.test(price)) { showError('Price must be in format ##.## (1-3 digits, two decimals) e.g. 6.00'); return; }
    if (!/^[0-9]+$/.test(qty)) { showError('Inventory must be a number'); return; }

    const payload = {
      title, description: desc, price: parseFloat(price).toFixed(2), quantity: parseInt(qty,10),
      available_from: formatLocalToSQL(from), available_until: formatLocalToSQL(to)
    };

    const editId = modal.dataset.editId || '';
    if (editId) payload.plate_id = parseInt(editId,10);

    try{
      save.disabled = true;
      const action = editId ? 'update_plate' : 'add_plate';
      const res = await fetch('../Controller.php?action=' + action,{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload), credentials: 'same-origin' });
      const j = await res.json();
      if (j.status === 'success'){
        // notify other components to refresh
        window.dispatchEvent(new Event('plates:changed'));
        if (editId) {
          // close editor and leave view-plates modal to refresh its list
          close();
          // reset heading back to "Add a Plate" for next time
          const h = modal.querySelector('.modal-content h2'); if (h) h.textContent = 'Add a Plate';
          delete modal.dataset.editId;
          alert('Plate updated');
        } else {
          close();
          delete modal.dataset.editId;
          alert('Successfully added plate!');
        }
      } else {
        showError(j.message || 'Error saving plate');
      }
    }catch(err){ showError('Network error'); }
    finally{ save.disabled = false; }
  });

  // ensure that closing via cancel clears edit state and heading
  const origClose = close;
  cancel.addEventListener('click', () => {
    if (modal) {
      delete modal.dataset.editId;
      const h = modal.querySelector('.modal-content h2'); if (h) h.textContent = 'Add a Plate';
    }
    origClose();
  });
});
