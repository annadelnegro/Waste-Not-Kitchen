document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('edit-plate-modal');
  if (!modal) return;

  const titleEl = document.getElementById('edit-plate-title');
  const descEl = document.getElementById('edit-plate-desc');
  const priceEl = document.getElementById('edit-plate-price');
  const qtyEl = document.getElementById('edit-plate-qty');
  const fromEl = document.getElementById('edit-plate-from');
  const toEl = document.getElementById('edit-plate-to');
  const errEl = document.getElementById('edit-plate-error');
  const saveBtn = document.getElementById('edit-plate-save');
  const cancelBtn = document.getElementById('edit-plate-cancel');

  let currentPlateId = null;
  let onCloseCallback = null;

  function showError(msg){ errEl.textContent = msg; errEl.style.display = 'block'; }
  function clearError(){ errEl.textContent = ''; errEl.style.display = 'none'; }

  function formatLocalToSQL(v){
    if (!v) return null;
    if (v.indexOf('T') !== -1) return v.replace('T',' ') + ':00';
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v + ' 00:00:00';
    return v;
  }

  // Global opener used by view-plates to launch editor
  window.openEditPlate = function(plate, onClose) {
    currentPlateId = plate.id || null;
    onCloseCallback = typeof onClose === 'function' ? onClose : null;
    titleEl.value = plate.title || '';
    descEl.value = plate.description || '';
    priceEl.value = (typeof plate.price !== 'undefined' && plate.price !== null) ? Number(plate.price).toFixed(2) : '';
    qtyEl.value = (typeof plate.quantity !== 'undefined' && plate.quantity !== null) ? String(plate.quantity) : '';
    if (plate.available_from) fromEl.value = String(plate.available_from).substr(0,10);
    else fromEl.value = '';
    if (plate.available_until) toEl.value = String(plate.available_until).substr(0,10);
    else toEl.value = '';
    clearError();
    modal.style.display = 'flex';
  };

  cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    currentPlateId = null;
    clearError();
    if (onCloseCallback) onCloseCallback();
  });

  saveBtn.addEventListener('click', async () => {
    clearError();
    const title = titleEl.value.trim();
    const desc = descEl.value.trim();
    const price = priceEl.value.trim();
    const qty = qtyEl.value.trim();
    const from = fromEl.value;
    const to = toEl.value;

    if (!title) { showError('Plate title is required'); return; }
    if (!/^\d{1,3}\.\d{2}$/.test(price)) { showError('Price must be in format ##.## (two decimals)'); return; }
    if (!/^[0-9]+$/.test(qty)) { showError('Inventory must be a number'); return; }

    const payload = {
      plate_id: parseInt(currentPlateId,10),
      title,
      description: desc,
      price: parseFloat(price).toFixed(2),
      quantity: parseInt(qty,10),
      available_from: formatLocalToSQL(from),
      available_until: formatLocalToSQL(to)
    };

    try{
      saveBtn.disabled = true;
      // Controller.php sits in modules/Auth/Controller.php; from views/profile.php the correct relative path is '../Controller.php'
      const res = await fetch('../Controller.php?action=update_plate', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload), credentials: 'same-origin' });
      const j = await res.json();
      if (j.status === 'success'){
        modal.style.display = 'none';
        currentPlateId = null;
        // notify view to refresh
        window.dispatchEvent(new Event('plates:changed'));
        if (onCloseCallback) onCloseCallback();
      } else {
        showError(j.message || 'Error updating plate');
      }
    }catch(err){ showError('Network error'); }
    finally{ saveBtn.disabled = false; }
  });
});
