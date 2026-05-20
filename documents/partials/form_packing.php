<?php /** @var array $pk @var array $linesPk */ ?>
<div class="card doc-section shadow-sm mb-4">
    <div class="card-header">Packing List</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Packing List No.</label>
                <input name="pl[packing_list_no]" class="form-control" value="<?= e(v($pk,'packing_list_no')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Date</label>
                <input type="text" name="pl[packing_date]" class="form-control date-picker" placeholder="DD-MM-YYYY" value="<?= e(format_date(v($pk,'packing_date', date('Y-m-d')))) ?>"></div>
            <div class="col-md-4"><label class="form-label">Invoice Ref</label>
                <input name="pl[invoice_ref]" class="form-control" value="<?= e(v($pk,'invoice_ref')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Exporter</label>
                <input name="pl[exporter_name]" class="form-control suggest" data-field="exporter_name" value="<?= e(v($pk,'exporter_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Buyer</label>
                <input name="pl[buyer_name]" class="form-control suggest" data-field="buyer_name" value="<?= e(v($pk,'buyer_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Exporter Address</label>
                <textarea name="pl[exporter_address]" class="form-control suggest" data-field="exporter_address" rows="2"><?= e(v($pk,'exporter_address')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Buyer Address</label>
                <textarea name="pl[buyer_address]" class="form-control suggest" data-field="buyer_address" rows="2"><?= e(v($pk,'buyer_address')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Consignee</label>
                <input name="pl[consignee_name]" class="form-control suggest" data-field="consignee_name" value="<?= e(v($pk,'consignee_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Consignee Address</label>
                <textarea name="pl[consignee_address]" class="form-control suggest" data-field="consignee_address" rows="2"><?= e(v($pk,'consignee_address')) ?></textarea></div>
            <div class="col-md-4"><label class="form-label">Container No.</label>
                <input name="pl[container_no]" class="form-control suggest" data-field="container_no" value="<?= e(v($pk,'container_no')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Seal No.</label>
                <input name="pl[seal_no]" class="form-control" value="<?= e(v($pk,'seal_no')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Total Packages</label>
                <input name="pl[total_packages]" type="number" class="form-control" value="<?= e(v($pk,'total_packages','0')) ?>"></div>
            <div class="col-12"><label class="form-label">Shipping Marks</label>
                <textarea name="pl[shipping_marks]" class="form-control" rows="2"><?= e(v($pk,'shipping_marks')) ?></textarea></div>
        </div>
        <h6 class="mt-4">Packing Details</h6>
        <div class="table-responsive">
            <table class="table table-sm line-items" data-type="packing">
                <thead><tr><th>Description</th><th>Pkgs</th><th>Gross KG</th><th>Net KG</th><th>Dimensions</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($linesPk as $row): ?>
                <tr>
                    <td><input name="lines_packing[][description]" class="form-control form-control-sm suggest" data-field="line_description" value="<?= e($row['description'] ?? '') ?>"></td>
                    <td><input name="lines_packing[][packages]" type="number" class="form-control form-control-sm" value="<?= e($row['packages'] ?? '') ?>"></td>
                    <td><input name="lines_packing[][gross_kg]" type="number" step="0.001" class="form-control form-control-sm" value="<?= e($row['gross_kg'] ?? '') ?>"></td>
                    <td><input name="lines_packing[][net_kg]" type="number" step="0.001" class="form-control form-control-sm" value="<?= e($row['net_kg'] ?? '') ?>"></td>
                    <td><input name="lines_packing[][dimensions]" class="form-control form-control-sm" value="<?= e($row['dimensions'] ?? '') ?>"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary add-line" data-target="packing">+ Add Row</button>
        <div class="row g-3 mt-3">
            <div class="col-md-4"><label class="form-label">Total Gross (KG)</label>
                <input name="pl[total_gross_kg]" class="form-control" value="<?= e(v($pk,'total_gross_kg','0')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Total Net (KG)</label>
                <input name="pl[total_net_kg]" class="form-control" value="<?= e(v($pk,'total_net_kg','0')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Total CBM</label>
                <input name="pl[total_cbm]" class="form-control" value="<?= e(v($pk,'total_cbm','0')) ?>"></div>
        </div>
    </div>
</div>
