document.addEventListener('DOMContentLoaded', () => {
  const claimsList = document.getElementById('claims-list');
  const API_BASE = '../Controller.php';

  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      loadClaims(btn.dataset.filter);
    });
  });

  loadClaims('reserved');

  async function loadClaims(filter){
    claimsList.innerHTML = '<div class="small-muted">Loading…</div>';
    try{
      const res = await fetch(API_BASE + '?action=get_my_claims&filter='+encodeURIComponent(filter), { method:'GET', credentials: 'same-origin' });
      if(!res.ok){ const txt = await res.text(); claimsList.innerHTML = `<div class="small-muted">Error: ${escapeHtml(txt||res.statusText)}</div>`; return; }
      let data;
      try{ data = await res.json(); } catch(e){ claimsList.innerHTML = `<div class="small-muted">Error parsing server response</div>`; return; }
      if (data && data.status === 'error') { claimsList.innerHTML = `<div class="small-muted">${escapeHtml(data.message||'Error')}</div>`; return; }
      const items = Array.isArray(data) ? data : (data.data || []);
      renderClaims(items || []);
    } catch(err){ claimsList.innerHTML = '<div class="small-muted">Network error</div>'; }
  }

  function renderClaims(items){
    if(!items.length){ claimsList.innerHTML = '<div class="small-muted">No claims</div>'; return; }
    claimsList.innerHTML = '';
    items.forEach(d => {
      const price = parseFloat(d.plate_price || d.price || 0);
      const qty = parseInt(d.quantity || 0, 10) || 0;
      const total = price * qty;

      const it = document.createElement('div'); it.className = 'item';
      it.innerHTML = `
        <div class="item-header">
          <div class="item-title">${escapeHtml(d.plate_title)} — ${qty}×</div>
          <div class="small-muted">${escapeHtml(formatStatus(d.status || 'reserved'))}</div>
        </div>
        <div class="item-body" style="display:none;">
          <div>Claim ID: ${d.id}</div>
          <div>Restaurant: ${escapeHtml(d.restaurant_name || '')}</div>
          <div>Qty: ${qty}</div>
          <div><strong>Total order value: $${total.toFixed(2)}</strong></div>
        </div>
        <div class="action-row">
          <button class="action-btn view-btn">View</button>
          <button class="action-btn contact-btn">Restaurant Details</button>
        </div>
        <div class="contact-details" style="display:none;">Address: ${escapeHtml(d.restaurant_address||'—')}<br/>Phone: ${escapeHtml(d.restaurant_phone||'—')}</div>
        ${d.status === 'claimed' ? `<div class="picked-up-note">Claimed! Thank you</div>` : ''}
      `;

      const viewBtn = it.querySelector('.view-btn');
      const body = it.querySelector('.item-body');
      viewBtn.addEventListener('click', ()=>{ body.style.display = body.style.display === 'none' ? 'block' : 'none'; });

      const contactBtn = it.querySelector('.contact-btn');
      contactBtn.addEventListener('click', ()=>{
        const cd = it.querySelector('.contact-details');
        cd.style.display = cd.style.display === 'none' ? 'block' : 'none';
      });

      claimsList.appendChild(it);
    });
  }

  function escapeHtml(str){ return String(str||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[c]); }
  function formatStatus(s){ const st = String(s||'').toLowerCase(); if (st === 'claimed') return 'claimed'; return st.replace(/_/g,' '); }

});
