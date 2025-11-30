document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.querySelector('.open-view-plates');
  if (!openBtn) return;

  // create modal HTML if not present
  let modal = document.getElementById('view-plates-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'view-plates-modal';
    modal.className = 'modal';
    modal.style.display = 'none';
    modal.innerHTML = `
      <div class="modal-content" role="dialog" aria-modal="true">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <h2 style="margin:0;color:#05339C;font-family:'Simply Olive DEMO',serif;">Available Plates</h2>
          <button id="view-plates-exit" class="btn" style="background:#41A67E;color:#fff;">←</button>
        </div>
        <div class="plates-list" id="plates-list">
          <div style="color:#666;padding:12px">Loading…</div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }

  const listEl = modal.querySelector('#plates-list');
  const exitBtn = modal.querySelector('#view-plates-exit');

  function open() { modal.style.display = 'flex'; fetchPlates(); }
  function close() { modal.style.display = 'none'; }

  openBtn.addEventListener('click', open);
  if (exitBtn) exitBtn.addEventListener('click', close);

  async function fetchPlates(){
    listEl.innerHTML = '<div style="padding:12px;color:#666">Loading…</div>';
    try{
      // Use a path relative to the current page (profile.php is at modules/Auth/views/)
      const res = await fetch('../../Offers/get_plates.php', { credentials: 'same-origin' });
      const j = await res.json();
      if (j.status !== 'success') {
        listEl.innerHTML = '<div style="padding:12px;color:#d14b4b">Unable to load plates.</div>';
        return;
      }

      const plates = Array.isArray(j.plates) ? j.plates : [];
      if (plates.length === 0) {
        listEl.innerHTML = '<div style="padding:12px;color:#666">No plates found.</div>';
        return;
      }

      listEl.innerHTML = '';
      plates.forEach(p => {
        const item = document.createElement('div');
        item.className = 'plate-item';

        const left = document.createElement('div');
        const title = document.createElement('div');
        title.className = 'plate-title';
        title.textContent = p.title || '-';
        left.appendChild(title);

        const desc = document.createElement('div');
        desc.className = 'plate-desc';
        desc.textContent = p.description || '-';
        left.appendChild(desc);

        const qty = document.createElement('div');
        qty.className = 'plate-meta';
        qty.innerHTML = '<div style="color:#666;font-weight:600;font-size:12px">In Inventory</div><div>' + (p.quantity ?? 0) + '</div>';

        const price = document.createElement('div');
        price.className = 'plate-meta';
        const priceStr = (typeof p.price === 'string' || typeof p.price === 'number') ? Number(p.price).toFixed(2) : '-';
        price.innerHTML = '<div style="color:#666;font-weight:600;font-size:12px">Unit Price</div><div>$' + priceStr + '</div>';

        item.appendChild(left);
        item.appendChild(qty);
        item.appendChild(price);

        // Edit button
        const editWrap = document.createElement('div');
        editWrap.style.gridColumn = '1 / -1';
        editWrap.style.display = 'flex';
        editWrap.style.justifyContent = 'flex-end';
        const editBtn = document.createElement('button');
        editBtn.textContent = 'Edit';
        editBtn.className = 'btn';
        editBtn.style.padding = '6px 10px';
        editBtn.style.fontSize = '14px';
        editBtn.style.marginTop = '6px';
        editBtn.addEventListener('click', () => {
          // Hide the view-plates modal while editing to avoid overlap
          const vp = document.getElementById('view-plates-modal');
          if (vp) vp.style.display = 'none';
          // open the dedicated Edit Plate modal (separate file handles it)
          if (window.openEditPlate) {
            window.openEditPlate(p, () => {
              // callback when edit modal closes (re-show view list and refresh)
              if (vp) vp.style.display = 'flex';
              fetchPlates();
            });
          } else {
            alert('Editor not available');
            if (vp) vp.style.display = 'flex';
          }
        });
        editWrap.appendChild(editBtn);
        item.appendChild(editWrap);

        listEl.appendChild(item);
      });

    } catch (err) {
      listEl.innerHTML = '<div style="padding:12px;color:#d14b4b">Network error</div>';
    }
  }
  // Refresh when plates are changed elsewhere (add/update)
  document.addEventListener('plates:changed', fetchPlates);
});
