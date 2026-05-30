(function () {
  'use strict';

  // Initialize Flatpickr date pickers
  if (typeof flatpickr !== 'undefined') {
    flatpickr('.date-picker', {
      dateFormat: 'd-m-Y',
      allowInput: false
    });
  }

  // Line item calculations
  function recalcRow(row) {
    const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
    const price = parseFloat(row.querySelector('.price')?.value) || 0;
    const amount = row.querySelector('.amount');
    if (amount && !amount.dataset.manual) {
      amount.value = (qty * price).toFixed(2);
    }
  }

  function recalcContract() {
    const quantityInput = document.querySelector('input[name="ec[quantity]"]');
    const unitPriceInput = document.querySelector('input[name="ec[unit_price]"]');
    const totalValueInput = document.querySelector('input[name="ec[total_value]"]');
    
    if (!quantityInput || !unitPriceInput || !totalValueInput) return;
    
    const qty = parseFloat(quantityInput.value.replace(/,/g, '')) || 0;
    const price = parseFloat(unitPriceInput.value.replace(/,/g, '')) || 0;
    const total = qty * price;
    totalValueInput.value = total.toFixed(2);
  }

  function recalcTotals() {
    const table = document.querySelector('.line-items');
    if (table) {
      const type = table.dataset.type;
      if (type === 'proforma' || type === 'commercial') {
        let subtotal = 0;
        table.querySelectorAll('tbody tr').forEach((row) => {
          recalcRow(row);
          subtotal += parseFloat(row.querySelector('.amount')?.value) || 0;
        });

        let subtotalInput, freightInput, insuranceInput, totalInput;
        if (type === 'proforma') {
          subtotalInput = document.getElementById('pi_subtotal') || document.querySelector('input[name="pi[subtotal]"]');
          freightInput = document.querySelector('input[name="pi[freight]"]');
          insuranceInput = document.querySelector('input[name="pi[insurance]"]');
          totalInput = document.getElementById('pi_total') || document.querySelector('input[name="pi[total]"]');
        } else if (type === 'commercial') {
          subtotalInput = document.querySelector('input[name="ci[subtotal]"]');
          freightInput = document.querySelector('input[name="ci[freight]"]');
          insuranceInput = document.querySelector('input[name="ci[insurance]"]');
          totalInput = document.querySelector('input[name="ci[total]"]');
        }

        if (subtotalInput) {
          subtotalInput.value = subtotal.toFixed(2);
        }

        const freight = parseFloat(freightInput?.value) || 0;
        const insurance = parseFloat(insuranceInput?.value) || 0;
        const grandTotal = subtotal + freight + insurance;

        if (totalInput) {
          totalInput.value = grandTotal.toFixed(2);
        }
      } else if (type === 'packing') {
        let totalPackages = 0;
        let totalGross = 0;
        let totalNet = 0;
        let totalCbm = 0;

        table.querySelectorAll('tbody tr').forEach((row) => {
          const pkgsInput = row.querySelector('input[name*="[packages]"]');
          const grossInput = row.querySelector('input[name*="[gross_kg]"]');
          const netInput = row.querySelector('input[name*="[net_kg]"]');
          const dimInput = row.querySelector('input[name*="[dimensions]"]');

          const pkgs = parseInt(pkgsInput?.value) || 0;
          const gross = parseFloat(grossInput?.value) || 0;
          const net = parseFloat(netInput?.value) || 0;
          const dims = dimInput?.value || '';

          totalPackages += pkgs;
          totalGross += gross;
          totalNet += net;

          if (dims) {
            const matches = dims.match(/(\d+(?:\.\d+)?)\s*[xX*×-]\s*(\d+(?:\.\d+)?)\s*[xX*×-]\s*(\d+(?:\.\d+)?)/);
            if (matches) {
              const l = parseFloat(matches[1]);
              const w = parseFloat(matches[2]);
              const h = parseFloat(matches[3]);
              let rowCbm = l * w * h;
              if (l > 5 || w > 5 || h > 5) {
                rowCbm = rowCbm / 1000000;
              }
              totalCbm += rowCbm * pkgs;
            }
          }
        });

        const totalPkgsInput = document.querySelector('input[name="pl[total_packages]"]');
        const totalGrossInput = document.querySelector('input[name="pl[total_gross_kg]"]');
        const totalNetInput = document.querySelector('input[name="pl[total_net_kg]"]');
        const totalCbmInput = document.querySelector('input[name="pl[total_cbm]"]');

        if (totalPkgsInput && !totalPkgsInput.dataset.manual) {
          totalPkgsInput.value = totalPackages || 0;
        }
        if (totalGrossInput && !totalGrossInput.dataset.manual) {
          totalGrossInput.value = totalGross.toFixed(3);
        }
        if (totalNetInput && !totalNetInput.dataset.manual) {
          totalNetInput.value = totalNet.toFixed(3);
        }
        if (totalCbmInput && !totalCbmInput.dataset.manual) {
          totalCbmInput.value = totalCbm.toFixed(3);
        }
      }
    }
    
    // Calculate contract total value
    recalcContract();
  }

  document.addEventListener('input', (e) => {
    if (e.target.matches('.qty, .price')) {
      const row = e.target.closest('tr');
      if (row) recalcRow(row);
      recalcTotals();
    }
    if (e.target.matches('.amount')) {
      e.target.dataset.manual = '1';
      recalcTotals();
    }
    if (e.target.matches('input[name$="[freight]"], input[name$="[insurance]"]')) {
      recalcTotals();
    }
    // Contract form inputs
    if (e.target.matches('input[name="ec[quantity]"], input[name="ec[unit_price]"]')) {
      recalcTotals();
    }
    // Packing form inputs (prevent overwriting manual values if edited)
    if (e.target.matches('input[name="pl[total_packages]"], input[name="pl[total_gross_kg]"], input[name="pl[total_net_kg]"], input[name="pl[total_cbm]"]')) {
      e.target.dataset.manual = '1';
      recalcTotals();
    }
  });

  document.querySelectorAll('.line-items').forEach((table) => {
    table.addEventListener('input', () => recalcTotals());
  });

  // Run on page load / ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', recalcTotals);
  } else {
    recalcTotals();
  }

  // Add / remove rows
  const rowTemplates = {
    proforma: `<tr>
      <td><input name="lines_proforma[][description]" class="form-control form-control-sm suggest" data-field="line_description"></td>
      <td><input name="lines_proforma[][hs_code]" class="form-control form-control-sm suggest" data-field="line_hs_code"></td>
      <td><input name="lines_proforma[][quantity]" type="number" step="0.001" class="form-control form-control-sm qty"></td>
      <td><select name="lines_proforma[][unit]" class="form-select form-select-sm"><option value="MT">MT</option><option value="KG">KG</option></select></td>
      <td><input name="lines_proforma[][unit_price]" type="number" step="0.0001" class="form-control form-control-sm price"></td>
      <td><input name="lines_proforma[][amount]" type="number" step="0.01" class="form-control form-control-sm amount"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
    commercial: `<tr>
      <td><input name="lines_commercial[][description]" class="form-control form-control-sm suggest" data-field="line_description"></td>
      <td><input name="lines_commercial[][hs_code]" class="form-control form-control-sm suggest" data-field="line_hs_code"></td>
      <td><input name="lines_commercial[][quantity]" type="number" step="0.001" class="form-control form-control-sm qty"></td>
      <td><select name="lines_commercial[][unit]" class="form-select form-select-sm"><option value="MT">MT</option><option value="KG">KG</option></select></td>
      <td><input name="lines_commercial[][unit_price]" type="number" step="0.0001" class="form-control form-control-sm price"></td>
      <td><input name="lines_commercial[][amount]" type="number" step="0.01" class="form-control form-control-sm amount"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
    packing: `<tr>
      <td><input name="lines_packing[][description]" class="form-control form-control-sm suggest" data-field="line_description"></td>
      <td><input name="lines_packing[][packages]" type="number" class="form-control form-control-sm"></td>
      <td><input name="lines_packing[][gross_kg]" type="number" step="0.001" class="form-control form-control-sm"></td>
      <td><input name="lines_packing[][net_kg]" type="number" step="0.001" class="form-control form-control-sm"></td>
      <td><input name="lines_packing[][dimensions]" class="form-control form-control-sm"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
    gate_pass: `<tr>
      <td><input name="lines_gate_pass[][description]" class="form-control form-control-sm suggest" data-field="line_description"></td>
      <td><input name="lines_gate_pass[][quantity]" type="number" step="0.001" class="form-control form-control-sm"></td>
      <td><select name="lines_gate_pass[][unit]" class="form-select form-select-sm"><option value="KG">KG</option><option value="MT">MT</option></select></td>
      <td><input name="lines_gate_pass[][remarks]" class="form-control form-control-sm suggest" data-field="line_remarks"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
    </tr>`,
  };

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('add-line')) {
      const target = e.target.dataset.target;
      const table = document.querySelector(`.line-items[data-type="${target}"] tbody`);
      if (table && rowTemplates[target]) {
        table.insertAdjacentHTML('beforeend', rowTemplates[target]);
        recalcTotals();
        if (typeof initSelect2 !== 'undefined') {
          initSelect2();
        }
      }
    }
    if (e.target.classList.contains('rm-row')) {
      const row = e.target.closest('tr');
      const tbody = row?.parentElement;
      if (row && tbody && tbody.children.length > 1) {
        row.remove();
        recalcTotals();
      }
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
    
    // Intercept Enter key inside inputs to trigger "Review & Download" instead of "Save Draft"
    docForm.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        if (e.target.tagName.toLowerCase() === 'textarea') {
          return;
        }
        e.preventDefault();
        const reviewBtn = docForm.querySelector('button[type="submit"][data-action="review"]');
        if (reviewBtn) {
          reviewBtn.click();
        }
      }
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

  // Initialize Select2 for suggestible fields
  if (typeof $ !== 'undefined' && typeof window.SUGGEST_API !== 'undefined' && window.SUGGEST_ACCOUNT_ID) {
    const initSelect2 = () => {
      $('.suggest:not(textarea):not(.select2-initialized)').each(function() {
        const $el = $(this);
        $el.addClass('select2-initialized');
        const fieldKey = $el.data('field');
        const currentValue = $el.val();
        
        const $select = $('<select></select>');
        $select.attr('name', $el.attr('name'));
        $select.attr('class', $el.attr('class'));
        $select.attr('data-field', fieldKey);
        $select.attr('required', $el.attr('required') ? 'required' : null);
        $select.attr('id', $el.attr('id') ? $el.attr('id') : null);

        if (currentValue) {
          const $option = $('<option></option>');
          $option.attr('value', currentValue);
          $option.text(currentValue);
          $option.prop('selected', true);
          $select.append($option);
        }

        $el.replaceWith($select);

        $select.select2({
          theme: 'bootstrap-5',
          placeholder: 'Select or type a value',
          allowClear: false,
          tags: true,
          tokenSeparators: [],
          createTag: function(params) {
            const term = $.trim(params.term);
            if (term === '') {
              return null;
            }
            return {
              id: term,
              text: term,
              newTag: true
            };
          },
          ajax: {
            url: window.SUGGEST_API,
            type: 'GET',
            dataType: 'json',
            delay: 250,
            data: function(params) {
              return {
                account_id: window.SUGGEST_ACCOUNT_ID,
                field: fieldKey,
                q: params.term || ''
              };
            },
            processResults: function(data) {
              return {
                results: data.map(function(val) {
                  return {
                    id: val,
                    text: val
                  };
                })
              };
            },
            cache: true
          },
          minimumInputLength: 0
        });

        $select.on('select2:opening', function() {
          if (!$select.data('suggestions-loaded')) {
            $.ajax({
              url: window.SUGGEST_API,
              type: 'GET',
              dataType: 'json',
              data: {
                account_id: window.SUGGEST_ACCOUNT_ID,
                field: fieldKey,
                q: ''
              }
            }).then(function(data) {
              const currentVal = $select.val();
              $select.empty();
              if (currentVal) {
                $select.append(new Option(currentVal, currentVal, true, true));
              }
              data.forEach(function(val) {
                if (val !== currentVal) {
                  $select.append(new Option(val, val, false, false));
                }
              });
              $select.data('suggestions-loaded', true);
            });
          }
        });

        $select.on('change', function() {
          recalcTotals();
        });
      });

      $('textarea.suggest:not(.select2-initialized)').each(function() {
        const $el = $(this);
        $el.addClass('select2-initialized');
        $el.attr('data-suggestible', 'true');
        $el.addClass('textarea-suggest');
      });
    };
    
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initSelect2);
    } else {
      initSelect2();
    }
    
    // For textarea fields with suggest class, add a simple autocomplete via keyup
    // This maintains the textarea appearance while providing suggestions
    if (typeof window.SUGGEST_API !== 'undefined' && window.SUGGEST_ACCOUNT_ID) {
      let debounceTimer;
      let currentDropdown = null;
      
      document.addEventListener('keyup', function(e) {
        const $el = $(e.target);
        if (!$el.hasClass('suggest') || !$el.is('textarea')) return;
        
        const fieldKey = $el.data('field');
        const value = $el.val();
        const query = value.split('\n').pop().trim(); // Get last line for searching
        
        clearTimeout(debounceTimer);
        if (!query) {
          if (currentDropdown) currentDropdown.remove();
          return;
        }
        
        debounceTimer = setTimeout(function() {
          const url = `${window.SUGGEST_API}?account_id=${window.SUGGEST_ACCOUNT_ID}&field=${encodeURIComponent(fieldKey)}&q=${encodeURIComponent(query)}`;
          
          fetch(url)
            .then(r => r.json())
            .then(data => {
              if (!data || data.length === 0) return;
              
              // Remove old dropdown
              if (currentDropdown) currentDropdown.remove();
              
              const dropdown = document.createElement('div');
              dropdown.className = 'textarea-suggestions';
              
              data.slice(0, 5).forEach(val => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = val;
                btn.addEventListener('click', function(e) {
                  e.preventDefault();
                  const lines = $el.val().split('\n');
                  lines[lines.length - 1] = val;
                  $el.val(lines.join('\n'));
                  dropdown.remove();
                  $el.trigger('change');
                });
                dropdown.appendChild(btn);
              });
              
              const rect = $el[0].getBoundingClientRect();
              dropdown.style.position = 'fixed';
              dropdown.style.top = (rect.bottom + 5) + 'px';
              dropdown.style.left = rect.left + 'px';
              dropdown.style.width = rect.width + 'px';
              
              document.body.appendChild(dropdown);
              currentDropdown = dropdown;
            });
        }, 250);
      }, true);
      
      // Close dropdown on click outside
      document.addEventListener('click', function(e) {
        if (currentDropdown && !$(e.target).hasClass('suggest')) {
          currentDropdown.remove();
          currentDropdown = null;
        }
      });
    }
  }
})();
