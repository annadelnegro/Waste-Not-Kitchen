document.addEventListener('DOMContentLoaded', () => {
  const ordersList = document.getElementById('orders-list');
  const API_BASE = '../Controller.php';

  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      loadOrders(btn.dataset.filter);
    });
  });

  // initial
  loadOrders('reserved');

  async function loadOrders(status){
    ordersList.innerHTML = '<div class="small-muted">Loading…</div>';
    try{
      const res = await fetch(API_BASE + '?action=get_my_orders&status='+encodeURIComponent(status), { method: 'GET', credentials: 'same-origin' });
      if(!res.ok){ const txt = await res.text(); ordersList.innerHTML = `<div class="small-muted">Error loading orders: ${escapeHtml(txt||res.statusText)}</div>`; return; }
      let data;
      try{ data = await res.json(); } catch(e){ ordersList.innerHTML = `<div class="small-muted">Error parsing server response</div>`; return; }
      if (data && data.status === 'error') { ordersList.innerHTML = `<div class="small-muted">${escapeHtml(data.message||'Error')}</div>`; return; }
      const items = Array.isArray(data) ? data : (data.data || []);
      renderOrders(items || []);
    } catch(err){ ordersList.innerHTML = '<div class="small-muted">Network error</div>'; }
  }

  function renderOrders(items){
    if(!items.length){ ordersList.innerHTML = '<div class="small-muted">No orders</div>'; return; }
    ordersList.innerHTML = '';
    items.forEach(o => {
      const price = parseFloat(o.plate_price || o.price || 0);
      const qty = parseInt(o.quantity || 0, 10) || 0;
      const total = price * qty;
      const statusLabel = formatStatus(o.status);

      const it = document.createElement('div'); it.className = 'item';
      it.innerHTML = `
        <div class="item-header">
          <div class="item-title">${escapeHtml(o.plate_title)} — ${qty}×</div>
          <div class="small-muted">${escapeHtml(statusLabel)}</div>
        </div>
        <div class="item-body" style="display:none;">
          <div>Order ID: ${o.id}</div>
          <div>Restaurant: ${escapeHtml(o.restaurant_name || '')}</div>
          <div>Qty: ${qty}</div>
          ${price ? `<div>Price: $${price.toFixed(2)} each</div>` : ''}
          <div><strong>Total: $${total.toFixed(2)}</strong></div>
        </div>
        <div class="action-row">
          <button class="action-btn view-btn">View</button>
          ${o.status === 'reserved' ? '<button class="action-btn cancel-btn">Cancel Order</button>' : ''}
          <button class="action-btn contact-btn">Contact Restaurant</button>
        </div>
        <div class="contact-details" style="margin-top:8px; display:none;">Address: ${escapeHtml(o.restaurant_address||'—')}<br/>Phone: ${escapeHtml(o.restaurant_phone||'—')}</div>
      `;

      const viewBtn = it.querySelector('.view-btn');
      const body = it.querySelector('.item-body');
      viewBtn.addEventListener('click', ()=>{ body.style.display = body.style.display === 'none' ? 'block' : 'none'; });

      const cancelBtn = it.querySelector('.cancel-btn');
      if(cancelBtn){
        cancelBtn.addEventListener('click', async ()=>{
          if(!confirm('Cancel this order and return items to inventory?')) return;
          try{
            const res = await fetch(API_BASE + '?action=cancel_my_order', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ order_id: o.id }), credentials: 'same-origin' });
            const j = await res.json();
            if (j.status === 'success') loadOrders(document.querySelector('.tab-btn.active').dataset.filter);
            else alert('Error: ' + (j.message || 'unknown'));
          }catch(err){ alert('Network error'); }
        });
      }

      const contactBtn = it.querySelector('.contact-btn');
      if(contactBtn){
        contactBtn.addEventListener('click', ()=>{
          // contact details are outside the collapsed body so toggle independently
          const cd = it.querySelector('.contact-details');
          cd.style.display = cd.style.display === 'none' ? 'block' : 'none';
        });
      }

      ordersList.appendChild(it);
    });
  }

  function escapeHtml(str){ return String(str||'').replace(/[&<>"]+/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c] || c)); }

  function formatStatus(s){ const st = String(s||'').toLowerCase(); if (st === 'paid' || st === 'picked_up') return 'paid & picked up'; return st.replace(/_/g,' '); }

});
