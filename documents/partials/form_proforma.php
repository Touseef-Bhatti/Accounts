    <?php /** @var array $p @var array $prefill @var array $linesP @var string $refNo */ ?>

<div class="card doc-section shadow-sm mb-4">

    <div class="card-header">Proforma Invoice</div>

    <div class="card-body">

        <div class="row g-3">

            <div class="col-md-4">

                <label class="form-label">Invoice No.</label>

                <input name="pi[invoice_no]" class="form-control suggest" data-field="pi_invoice_no" value="<?= e(v($p,'invoice_no', $refNo)) ?>" required>

            </div>

            <div class="col-md-4">

                <label class="form-label">Date</label>

                <input type="text" name="pi[invoice_date]" class="form-control date-picker" placeholder="DD-MM-YYYY" value="<?= e(format_date(v($p,'invoice_date', v($prefill,'invoice_date')))) ?>" required>

            </div>

            <div class="col-md-4">

                <label class="form-label">Validity</label>

                <input type="text" name="pi[validity_date]" class="form-control date-picker" placeholder="DD-MM-YYYY" value="<?= e(format_date(v($p,'validity_date'))) ?>">

            </div>

            <div class="col-md-6">

                <label class="form-label">Exporter</label>

                <input name="pi[exporter_name]" class="form-control suggest" data-field="exporter_name" value="<?= e(v($p,'exporter_name', v($prefill,'exporter_name'))) ?>" required>

            </div>

            <div class="col-md-6">

                <label class="form-label">Currency</label>

                <select name="pi[currency]" class="form-select">

                    <?php foreach (['USD','EUR','GBP','AED','SAR','PKR'] as $cur): ?>

                    <option <?= v($p,'currency', v($prefill,'currency')) === $cur ? 'selected' : '' ?>><?= $cur ?></option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-12">

                <label class="form-label">Exporter Address</label>

                <textarea name="pi[exporter_address]" class="form-control suggest" data-field="exporter_address" rows="2"><?= e(v($p,'exporter_address', v($prefill,'exporter_address'))) ?></textarea>

            </div>

            <div class="col-md-6"><label class="form-label">Buyer</label>
                <input name="pi[buyer_name]" class="form-control suggest" data-field="buyer_name" value="<?= e(v($p,'buyer_name')) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Consignee</label>
                <input name="pi[consignee_name]" class="form-control suggest" data-field="consignee_name" value="<?= e(v($p,'consignee_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Buyer Address</label>
                <textarea name="pi[buyer_address]" class="form-control suggest" data-field="buyer_address" rows="2"><?= e(v($p,'buyer_address')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Consignee Address</label>
                <textarea name="pi[consignee_address]" class="form-control suggest" data-field="consignee_address" rows="2"><?= e(v($p,'consignee_address')) ?></textarea></div>
            <div class="col-12"><label class="form-label">Notify Party</label>
                <textarea name="pi[notify_party]" class="form-control suggest" data-field="notify_party" rows="2"><?= e(v($p,'notify_party')) ?></textarea></div>
            <div class="col-md-3"><label class="form-label">Origin</label>
                <input name="pi[country_origin]" class="form-control suggest" data-field="country_origin" value="<?= e(v($p,'country_origin','Pakistan')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Destination</label>
                <input name="pi[country_destination]" class="form-control suggest" data-field="country_destination" value="<?= e(v($p,'country_destination')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Port Loading</label>
                <input name="pi[port_loading]" class="form-control suggest" data-field="port_loading" value="<?= e(v($p,'port_loading','Karachi')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Port Discharge</label>
                <input name="pi[port_discharge]" class="form-control suggest" data-field="port_discharge" value="<?= e(v($p,'port_discharge')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Incoterms</label>
                <input name="pi[incoterms]" class="form-control suggest" data-field="incoterms" value="<?= e(v($p,'incoterms','CFR')) ?>"></div>
            <div class="col-12"><label class="form-label">Payment Terms</label>
                <input name="pi[payment_terms]" class="form-control suggest" data-field="payment_terms" value="<?= e(v($p,'payment_terms', v($prefill,'payment_terms', '100% irrevocable L/C at sight'))) ?>"></div>

            <div class="col-12"><label class="form-label">Shipping Marks</label>

                <textarea name="pi[shipping_marks]" class="form-control" rows="2"><?= e(v($p,'shipping_marks')) ?></textarea></div>

        </div>



        <h6 class="mt-4">Line Items</h6>

        <div class="table-responsive line-items-wrap">

            <table class="table table-sm line-items" data-type="proforma">

                <thead><tr><th>Description</th><th>HS Code</th><th>Qty</th><th>Unit</th><th>Unit Price</th><th>Amount</th><th></th></tr></thead>

                <tbody>

                <?php foreach ($linesP as $row): ?>

                <tr>

                    <td><input name="lines_proforma[][description]" class="form-control form-control-sm suggest" data-field="line_description" value="<?= e($row['description'] ?? '') ?>"></td>

                    <td><input name="lines_proforma[][hs_code]" class="form-control form-control-sm suggest" data-field="line_hs_code" value="<?= e($row['hs_code'] ?? '') ?>"></td>

                    <td><input name="lines_proforma[][quantity]" type="number" step="0.001" class="form-control form-control-sm qty" value="<?= e($row['quantity'] ?? '') ?>"></td>

                    <td>
                        <select name="lines_proforma[][unit]" class="form-select form-select-sm">
                            <option value="MT" <?= ($row['unit'] ?? 'MT') === 'MT' ? 'selected' : '' ?>>MT</option>
                            <option value="KG" <?= ($row['unit'] ?? 'MT') === 'KG' ? 'selected' : '' ?>>KG</option>
                        </select>
                    </td>

                    <td><input name="lines_proforma[][unit_price]" type="number" step="0.0001" class="form-control form-control-sm price" value="<?= e($row['unit_price'] ?? '') ?>"></td>

                    <td><input name="lines_proforma[][amount]" type="number" step="0.01" class="form-control form-control-sm amount" value="<?= e($row['amount'] ?? '') ?>"></td>

                    <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>

                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <button type="button" class="btn btn-sm btn-outline-primary add-line" data-target="proforma">+ Add Row</button>



        <div class="row g-3 mt-3">

            <div class="col-md-3"><label class="form-label">Subtotal</label><input name="pi[subtotal]" id="pi_subtotal" class="form-control total-field" value="<?= e(v($p,'subtotal','0')) ?>"></div>

            <div class="col-md-3"><label class="form-label">Freight</label><input name="pi[freight]" class="form-control" value="<?= e(v($p,'freight','0')) ?>"></div>

            <div class="col-md-3"><label class="form-label">Insurance</label><input name="pi[insurance]" class="form-control" value="<?= e(v($p,'insurance','0')) ?>"></div>

            <div class="col-md-3"><label class="form-label">Grand Total</label><input name="pi[total]" id="pi_total" class="form-control" value="<?= e(v($p,'total','0')) ?>"></div>

            <div class="col-12"><label class="form-label">Bank Details</label>

                <textarea name="pi[bank_details]" class="form-control" rows="3"><?= e(v($p,'bank_details', v($prefill,'bank_details'))) ?></textarea></div>

            <div class="col-12"><label class="form-label">Remarks</label>

                <textarea name="pi[remarks]" class="form-control" rows="2"><?= e(v($p,'remarks')) ?></textarea></div>

        </div>

    </div>

</div>

