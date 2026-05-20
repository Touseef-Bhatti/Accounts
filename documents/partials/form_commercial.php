<?php
/** @var array $c @var array $linesC */
?>
<div class="card doc-section shadow-sm mb-4">
    <div class="card-header">Commercial Invoice</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Invoice No.</label>
                <input name="ci[invoice_no]" class="form-control suggest" data-field="ci_invoice_no" value="<?= e(v($c,'invoice_no')) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Date</label>
                <input type="text" name="ci[invoice_date]" class="form-control date-picker" placeholder="DD-MM-YYYY" value="<?= e(format_date(v($c,'invoice_date', date('Y-m-d')))) ?>"></div>
            <div class="col-md-3"><label class="form-label">L/C No.</label>
                <input name="ci[lc_no]" class="form-control suggest" data-field="lc_no" value="<?= e(v($c,'lc_no')) ?>"></div>
            <div class="col-md-3"><label class="form-label">L/C Date</label>
                <input type="text" name="ci[lc_date]" class="form-control date-picker" placeholder="DD-MM-YYYY" value="<?= e(format_date(v($c,'lc_date'))) ?>"></div>
            <div class="col-md-6"><label class="form-label">Exporter</label>
                <input name="ci[exporter_name]" class="form-control suggest" data-field="exporter_name" value="<?= e(v($c,'exporter_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Currency</label>
                <select name="ci[currency]" class="form-select">
                    <?php foreach (['USD','EUR','GBP','AED','SAR','PKR'] as $cur): ?>
                    <option <?= v($c,'currency','USD') === $cur ? 'selected' : '' ?>><?= $cur ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-12"><label class="form-label">Exporter Address</label>
                <textarea name="ci[exporter_address]" class="form-control suggest" data-field="exporter_address" rows="2"><?= e(v($c,'exporter_address')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Buyer</label>
                <input name="ci[buyer_name]" class="form-control suggest" data-field="buyer_name" value="<?= e(v($c,'buyer_name')) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Consignee</label>
                <input name="ci[consignee_name]" class="form-control suggest" data-field="consignee_name" value="<?= e(v($c,'consignee_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Buyer Address</label>
                <textarea name="ci[buyer_address]" class="form-control suggest" data-field="buyer_address" rows="2"><?= e(v($c,'buyer_address')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Consignee Address</label>
                <textarea name="ci[consignee_address]" class="form-control suggest" data-field="consignee_address" rows="2"><?= e(v($c,'consignee_address')) ?></textarea></div>
            <div class="col-12"><label class="form-label">Notify Party</label>
                <textarea name="ci[notify_party]" class="form-control suggest" data-field="notify_party" rows="2"><?= e(v($c,'notify_party')) ?></textarea></div>
            <div class="col-md-3"><label class="form-label">Origin</label>
                <input name="ci[country_origin]" class="form-control suggest" data-field="country_origin" value="<?= e(v($c,'country_origin','Pakistan')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Destination</label>
                <input name="ci[country_destination]" class="form-control suggest" data-field="country_destination" value="<?= e(v($c,'country_destination')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Port Loading</label>
                <input name="ci[port_loading]" class="form-control suggest" data-field="port_loading" value="<?= e(v($c,'port_loading','Karachi')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Port Discharge</label>
                <input name="ci[port_discharge]" class="form-control suggest" data-field="port_discharge" value="<?= e(v($c,'port_discharge')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Vessel / Flight</label>
                <input name="ci[vessel_flight]" class="form-control suggest" data-field="vessel_flight" value="<?= e(v($c,'vessel_flight')) ?>"></div>
            <div class="col-md-4"><label class="form-label">B/L or AWB No.</label>
                <input name="ci[bl_awb_no]" class="form-control suggest" data-field="bl_awb_no" value="<?= e(v($c,'bl_awb_no')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Incoterms</label>
                <input name="ci[incoterms]" class="form-control suggest" data-field="incoterms" value="<?= e(v($c,'incoterms','CFR')) ?>"></div>
            <div class="col-12"><label class="form-label">Payment Terms</label>
                <input name="ci[payment_terms]" class="form-control suggest" data-field="payment_terms" value="<?= e(v($c,'payment_terms','100% irrevocable L/C at sight')) ?>"></div>
            <div class="col-12"><label class="form-label">Shipping Marks</label>
                <textarea name="ci[shipping_marks]" class="form-control suggest" data-field="shipping_marks" rows="2"><?= e(v($c,'shipping_marks')) ?></textarea></div>
        </div>
        <h6 class="mt-4">Line Items</h6>
        <div class="table-responsive">
            <table class="table table-sm line-items" data-type="commercial">
                <thead><tr><th>Description</th><th>HS</th><th>Qty</th><th>Unit</th><th>Price</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($linesC as $row): ?>
                <tr>
                    <td><input name="lines_commercial[][description]" class="form-control form-control-sm suggest" data-field="line_description" value="<?= e($row['description'] ?? '') ?>"></td>
                    <td><input name="lines_commercial[][hs_code]" class="form-control form-control-sm suggest" data-field="line_hs_code" value="<?= e($row['hs_code'] ?? '') ?>"></td>
                    <td><input name="lines_commercial[][quantity]" type="number" step="0.001" class="form-control form-control-sm qty" value="<?= e($row['quantity'] ?? '') ?>"></td>
                    <td>
                        <select name="lines_commercial[][unit]" class="form-select form-select-sm">
                            <option value="MT" <?= ($row['unit'] ?? 'MT') === 'MT' ? 'selected' : '' ?>>MT</option>
                            <option value="KG" <?= ($row['unit'] ?? 'MT') === 'KG' ? 'selected' : '' ?>>KG</option>
                        </select>
                    </td>
                    <td><input name="lines_commercial[][unit_price]" type="number" step="0.0001" class="form-control form-control-sm price" value="<?= e($row['unit_price'] ?? '') ?>"></td>
                    <td><input name="lines_commercial[][amount]" type="number" step="0.01" class="form-control form-control-sm amount" value="<?= e($row['amount'] ?? '') ?>"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary add-line" data-target="commercial">+ Add Row</button>
        <div class="row g-3 mt-3">
            <div class="col-md-3"><label class="form-label">Subtotal</label><input name="ci[subtotal]" class="form-control" value="<?= e(v($c,'subtotal','0')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Freight</label><input name="ci[freight]" class="form-control" value="<?= e(v($c,'freight','0')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Insurance</label><input name="ci[insurance]" class="form-control" value="<?= e(v($c,'insurance','0')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Total</label><input name="ci[total]" class="form-control" value="<?= e(v($c,'total','0')) ?>"></div>
            <div class="col-12"><label class="form-label">Bank Details</label>
                <textarea name="ci[bank_details]" class="form-control" rows="3"><?= e(v($c,'bank_details')) ?></textarea></div>
        </div>
    </div>
</div>
