(function () {
  'use strict';

  // Line item calculations
  function recalcRow(row) {
    const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
    const price = parseFloat(row.querySelector('.price')?.value) || 0;
    const amount = row.querySelector('.amount');
    if (amount && !amount.dataset.manual) {
      amount.value = (qty * price).toFixed(2);
    }
  }

  function recalcTable(table) {
    let sub = 0;
    table.querySelectorAll('tbody tr').forEach((row) => {
      recalcRow(row);
      sub += parseFloat(row.querySelector('.amount')?.value) || 0;
    });
    const type = table.dataset.type;
    if (type === 'proforma') {
      const el = document.getElementById('pi_subtotal');
      const total = document.getElementById('pi_total');
      if (el) el.value = sub.toFixed(2);
      if (total) total.value = sub.toFixed(2);
    }
  }

  document.addEventListener('input', (e) => {
    if (e.target.matches('.qty, .price')) {
      const row = e.target.closest('tr');
      if (row) recalcRow(row);
    }
    if (e.target.matches('.amount')) {
      e.target.dataset.manual = '1';
    }
  });

  document.querySelectorAll('.line-items').forEach((table) => {
    table.addEventListener('input', () => recalcTable(table));
  });

  // Add / remove rows
  const rowTemplates = {
    proforma: `<tr>
      <td><input name="lines_proforma[][description]" class="form-control form-control-sm"></td>
      <td><input name="lines_proforma[][hs_code]" class="form-control form-control-sm"></td>
      <td><input name="lines_proforma[][quantity]" type="number" step="0.001" class="form-control form-control-sm qty"></td>
      <td><input name="lines_proforma[][unit]" class="form-control form-control-sm" value="MT"></td>
      <td><input name="lines_proforma[][unit_price]" type="number" step="0.0001" class="form-control form-control-sm price"></td>
      <td><input name="lines_proforma[][amount]" type="number" step="0.01" class="form-control form-control-sm amount"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
    commercial: `<tr>
      <td><input name="lines_commercial[][description]" class="form-control form-control-sm"></td>
      <td><input name="lines_commercial[][hs_code]" class="form-control form-control-sm"></td>
      <td><input name="lines_commercial[][quantity]" type="number" step="0.001" class="form-control form-control-sm qty"></td>
      <td><input name="lines_commercial[][unit]" class="form-control form-control-sm" value="MT"></td>
      <td><input name="lines_commercial[][unit_price]" type="number" step="0.0001" class="form-control form-control-sm price"></td>
      <td><input name="lines_commercial[][amount]" type="number" step="0.01" class="form-control form-control-sm amount"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
    packing: `<tr>
      <td><input name="lines_packing[][description]" class="form-control form-control-sm"></td>
      <td><input name="lines_packing[][packages]" type="number" class="form-control form-control-sm"></td>
      <td><input name="lines_packing[][gross_kg]" type="number" step="0.001" class="form-control form-control-sm"></td>
      <td><input name="lines_packing[][net_kg]" type="number" step="0.001" class="form-control form-control-sm"></td>
      <td><input name="lines_packing[][dimensions]" class="form-control form-control-sm"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
    gate_pass: `<tr>
      <td><input name="lines_gate_pass[][description]" class="form-control form-control-sm"></td>
      <td><input name="lines_gate_pass[][quantity]" type="number" step="0.001" class="form-control form-control-sm"></td>
      <td><input name="lines_gate_pass[][unit]" class="form-control form-control-sm" value="KG"></td>
      <td><input name="lines_gate_pass[][remarks]" class="form-control form-control-sm"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
  };

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('add-line')) {
      const target = e.target.dataset.target;
      const table = document.querySelector(`.line-items[data-type="${target}"] tbody`);
      if (table && rowTemplates[target]) {
        table.insertAdjacentHTML('beforeend', rowTemplates[target]);
      }
    }
    if (e.target.classList.contains('rm-row')) {
      const row = e.target.closest('tr');
      const tbody = row?.parentElement;
      if (row && tbody && tbody.children.length > 1) row.remove();
    }
  });

  const docForm = document.getElementById('docForm');
  if (docForm) {
    const actionInput = document.getElementById('form_action');
    docForm.querySelectorAll('button[type="submit"][data-action]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (actionInput) {
          actionInput.value = btn.dataset.action || 'draft';
        }
      });
    });
    docForm.addEventListener('submit', function (e) {
      if (docForm.dataset.submitting === '1') {
        e.preventDefault();
        return;
      }
      const submitter = e.submitter;
      if (actionInput && submitter?.dataset?.action) {
        actionInput.value = submitter.dataset.action;
      }
      docForm.dataset.submitting = '1';
      docForm.querySelectorAll('button[type="submit"]').forEach((btn) => {
        btn.disabled = true;
      });
    }, { capture: false });
  }

  // Autocomplete suggestions
  if (typeof window.SUGGEST_API !== 'undefined' && window.SUGGEST_ACCOUNT_ID) {
    let debounce;
    document.querySelectorAll('.suggest').forEach((input) => {
      const wrap = document.createElement('div');
      wrap.className = 'suggest-wrap';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);
      const dropdown = document.createElement('div');
      dropdown.className = 'suggest-dropdown';
      wrap.appendChild(dropdown);

      input.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(async () => {
          const field = input.dataset.field;
          const q = input.value.trim();
          if (!field || q.length < 1) {
            dropdown.classList.remove('show');
            return;
          }
          const url = `${window.SUGGEST_API}?account_id=${window.SUGGEST_ACCOUNT_ID}&field=${encodeURIComponent(field)}&q=${encodeURIComponent(q)}`;
          try {
            const res = await fetch(url);
            const items = await res.json();
            dropdown.innerHTML = '';
            items.forEach((val) => {
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.textContent = val;
              btn.addEventListener('click', () => {
                input.value = val;
                dropdown.classList.remove('show');
              });
              dropdown.appendChild(btn);
            });
            dropdown.classList.toggle('show', items.length > 0);
          } catch (_) {}
        }, 250);
      });

      document.addEventListener('click', (ev) => {
        if (!wrap.contains(ev.target)) dropdown.classList.remove('show');
      });
    });
  }
})();
