document.addEventListener('DOMContentLoaded', () => {
  const ordersList = document.getElementById('orders-list');
  const donationsList = document.getElementById('donations-list');
  // Use a relative path so cookies/session are sent correctly from the same origin
  const API_BASE = '../Controller.php';

  // Orders tab handling
  document.querySelectorAll('.orders-box .tab-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      document.querySelectorAll('.orders-box .tab-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      loadOrders(btn.dataset.filter);
    });
  });

  // Donations tab handling
  document.querySelectorAll('.donations-box .tab-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      document.querySelectorAll('.donations-box .tab-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      loadDonations(btn.dataset.filter);
    });
  });

  // initial load
  loadOrders('reserved');
  loadDonations('available');

  async function loadOrders(status){
    ordersList.innerHTML = '<div class="small-muted">Loading…</div>';
    try{
      const res = await fetch(API_BASE + '?action=get_orders&status='+encodeURIComponent(status),{ method:'GET', credentials: 'same-origin' });
      if (!res.ok) {
        const txt = await res.text();
        console.error('get_orders non-OK response', res.status, txt);
        ordersList.innerHTML = `<div class="small-muted">Error loading orders: ${escapeHtml(txt || res.statusText)}</div>`;
        return;
      }
      let data;
      try { data = await res.json(); }
      catch(e){ const txt = await res.text(); console.error('get_orders JSON parse error', txt); ordersList.innerHTML = `<div class="small-muted">Error parsing server response</div>`; return; }

      // Support either a raw array or an envelope like { status: 'success', data: [...] }
      if (data && data.status === 'error') {
        ordersList.innerHTML = `<div class="small-muted">${escapeHtml(data.message || 'Error')}</div>`;
        return;
      }
      const items = Array.isArray(data) ? data : (data.data || []);
      renderOrders(items || []);
    }catch(err){ ordersList.innerHTML = '<div class="small-muted">Error loading orders</div>'; }
  }

  function renderOrders(items){
    if(!items.length){ ordersList.innerHTML = '<div class="small-muted">No orders</div>'; return; }
    ordersList.innerHTML = '';
    items.forEach(o => {
      const it = document.createElement('div'); it.className='item';
      // compute total paid when picked up (plate_price * qty)
      const price = parseFloat(o.plate_price || o.price || 0);
      const totalPaid = (price * (parseFloat(o.quantity) || 0));

      it.innerHTML = `
        <div class="item-header">
          <div class="item-title">${escapeHtml(o.plate_title)} — ${o.quantity}×</div>
          <div class="small-muted">${escapeHtml(formatStatus(o.status))}</div>
        </div>
        <div class="item-body" style="display:none;">
          <div>Order ID: ${o.id}</div>
          <div>Buyer: ${escapeHtml(o.buyer_username)}</div>
          <div>Qty: ${o.quantity}</div>
          <div>Status: ${escapeHtml(formatStatus(o.status))}</div>
          ${price ? `<div>Price: $${price.toFixed(2)} each</div>` : ''}
          ${o.status === 'picked_up' ? `<div>Total paid: $${totalPaid.toFixed(2)}</div>` : ''}
        </div>
        <div class="action-row">
          <button class="action-btn view-btn">View</button>
          ${o.status !== 'picked_up' ? '<button class="action-btn pickup-btn">Confirm Picked Up</button>' : ''}
        </div>
      `;
      const viewBtn = it.querySelector('.view-btn');
      const body = it.querySelector('.item-body');
      viewBtn.addEventListener('click', ()=>{ body.style.display = body.style.display==='none' ? 'block':'none'; });

      const pickupBtn = it.querySelector('.pickup-btn');
      if(pickupBtn){
        pickupBtn.addEventListener('click', async ()=>{
          if(!confirm('Confirm marking this order as picked up?')) return;
          try{
            const res = await fetch(API_BASE + '?action=confirm_order_pickup', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ order_id: o.id }), credentials: 'same-origin' });
            const j = await res.json();
            if(j.status === 'success') loadOrders(document.querySelector('.orders-box .tab-btn.active').dataset.filter);
            else alert('Error: '+(j.message||'unknown'));
          }catch(err){ alert('Network error'); }
        });
      }

      ordersList.appendChild(it);
    });
  }

  async function loadDonations(filter){
    donationsList.innerHTML = '<div class="small-muted">Loading…</div>';
    try{
      const res = await fetch(API_BASE + '?action=get_donations&filter='+encodeURIComponent(filter),{ method:'GET', credentials: 'same-origin' });
      if (!res.ok) {
        const txt = await res.text();
        console.error('get_donations non-OK response', res.status, txt);
        donationsList.innerHTML = `<div class="small-muted">Error loading donations: ${escapeHtml(txt || res.statusText)}</div>`;
        return;
      }
      let data;
      try{ data = await res.json(); } catch(e){ const txt = await res.text(); console.error('get_donations JSON parse error', txt); donationsList.innerHTML = `<div class="small-muted">Error parsing server response</div>`; return; }

      if (data && data.status === 'error') {
        donationsList.innerHTML = `<div class="small-muted">${escapeHtml(data.message || 'Error')}</div>`;
        return;
      }
      const items = Array.isArray(data) ? data : (data.data || []);
      renderDonations(items || []);
    }catch(err){ donationsList.innerHTML = '<div class="small-muted">Error loading donations</div>'; }
  }

  function renderDonations(items){
    if(!items.length){ donationsList.innerHTML = '<div class="small-muted">No donations</div>'; return; }
    donationsList.innerHTML = '';
    items.forEach(d => {
      const it = document.createElement('div'); it.className='item';
      const price = parseFloat(d.plate_price || d.price || 0);
      const totalPaid = (price * (parseFloat(d.quantity) || 0));
      const totalLabel = d.status === 'claimed' ? 'Total order value' : 'Total paid';
      it.innerHTML = `
        <div class="item-header">
          <div class="item-title">${escapeHtml(d.plate_title)} — ${d.quantity}×</div>
          <div class="small-muted">${escapeHtml(formatStatus(d.status || 'available'))}</div>
        </div>
        <div class="item-body" style="display:none;">
          <div>Donation ID: ${d.id}</div>
          <div>Donor: ${escapeHtml(d.donor_username)}</div>
          <div>Qty: ${d.quantity}</div>
          <div>Status: ${escapeHtml(formatStatus(d.status || 'available'))}</div>
          ${d.status === 'reserved' ? `<div>Reserved by: ${escapeHtml(d.needy_username || '—')}</div>` : ''}
          ${d.status === 'claimed' ? `<div>Picked up by: ${escapeHtml(d.needy_username || '—')}</div>` : ''}
          ${price ? `<div>Price: $${price.toFixed(2)} each</div>` : ''}
          <div>${totalLabel}: $${totalPaid.toFixed(2)}</div>
        </div>
        <div class="action-row">
          <button class="action-btn view-btn">View</button>
              ${d.status === 'reserved' ? '<button class="action-btn confirm-donation-btn">Confirm Pickup</button>' : ''}
              ${d.status === 'reserved' ? '<button class="action-btn return-btn">Add back to Pool</button>' : ''}
        </div>
      `;
      const viewBtn = it.querySelector('.view-btn');
      const body = it.querySelector('.item-body');
      viewBtn.addEventListener('click', ()=>{ body.style.display = body.style.display==='none' ? 'block':'none'; });

      const confirmBtn = it.querySelector('.confirm-donation-btn');
      if(confirmBtn){
        confirmBtn.addEventListener('click', async ()=>{
          if(!confirm('Confirm pickup for this donation?')) return;
          try{
            const res = await fetch(API_BASE + '?action=confirm_donation_pickup', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ donation_id: d.id }), credentials: 'same-origin' });
            const j = await res.json();
            if(j.status === 'success') loadDonations(document.querySelector('.donations-box .tab-btn.active').dataset.filter);
            else alert('Error: '+(j.message||'unknown'));
          }catch(err){ alert('Network error'); }
        });
      }

      const returnBtn = it.querySelector('.return-btn');
      if(returnBtn){
        returnBtn.addEventListener('click', async ()=>{
          if(!confirm('Return this donation to the pool?')) return;
          try{
            const res = await fetch(API_BASE + '?action=return_donation', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ donation_id: d.id }), credentials: 'same-origin' });
            const j = await res.json();
            if(j.status === 'success') loadDonations(document.querySelector('.donations-box .tab-btn.active').dataset.filter);
            else alert('Error: '+(j.message||'unknown'));
          }catch(err){ alert('Network error'); }
        });
      }

      donationsList.appendChild(it);
    });
  }

  function escapeHtml(str){ return String(str||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[c]); }
});

// Format status values for display (replace underscores with spaces and lowercase)
function formatStatus(s){
  const st = String(s||'').toLowerCase();
    if (st === 'picked_up') return 'picked up and paid';
    if (st === 'claimed') return 'picked up';
  return st.replace(/_/g,' ');
}
