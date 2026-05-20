<?php /** @var array $gp @var array $linesGp @var string $refNo */ ?>

<div class="card doc-section shadow-sm mb-4">

    <div class="card-header">Gate Pass</div>

    <div class="card-body">

        <div class="row g-3">

            <div class="col-md-4">

                <label class="form-label">Reference / Gate Pass No.</label>

                <input name="gp[gate_pass_no]" class="form-control" value="<?= e(v($gp,'gate_pass_no', $refNo)) ?>" required>

            </div>

            <div class="col-md-4">

                <label class="form-label">Date</label>

                <input type="text" name="gp[gate_pass_date]" class="form-control date-picker" placeholder="DD-MM-YYYY" value="<?= e(format_date(v($gp,'gate_pass_date', date('Y-m-d')))) ?>" required>

            </div>

            <div class="col-md-4">

                <label class="form-label">Container No.</label>

                <input name="gp[container_no]" class="form-control suggest" data-field="container_no" value="<?= e(v($gp,'container_no')) ?>">

            </div>

            <div class="col-12">

                <label class="form-label">Cargo Description</label>

                <textarea name="gp[cargo_description]" class="form-control" rows="2" placeholder="e.g. HC CONTAINING ZINC WHICH MANUFACTURED BY LOCAL RAW MATERIAL"><?= e(v($gp,'cargo_description')) ?></textarea>

            </div>

            <div class="col-md-6">

                <label class="form-label">Export Destination</label>

                <input name="gp[destination]" class="form-control suggest" data-field="country_destination" value="<?= e(v($gp,'destination')) ?>" placeholder="e.g. Qingzhou Port, China">

            </div>

            <div class="col-md-6">

                <label class="form-label">Vehicle No.</label>

                <input name="gp[vehicle_no]" class="form-control suggest" data-field="vehicle_no" value="<?= e(v($gp,'vehicle_no')) ?>">

            </div>

            <div class="col-md-4">

                <label class="form-label">Driver Name</label>

                <input name="gp[driver_name]" class="form-control suggest" data-field="driver_name" value="<?= e(v($gp,'driver_name')) ?>">

            </div>

            <div class="col-md-4">

                <label class="form-label">Driver NIC</label>

                <input name="gp[driver_nic]" class="form-control suggest" data-field="driver_nic" value="<?= e(v($gp,'driver_nic')) ?>">

            </div>

            <div class="col-md-4">

                <label class="form-label">Driver Mobile</label>

                <input name="gp[driver_mobile]" class="form-control suggest" data-field="driver_mobile" value="<?= e(v($gp,'driver_mobile')) ?>">

            </div>

            <div class="col-12">

                <label class="form-label">Additional Authorization Note (optional)</label>

                <textarea name="gp[authorization_note]" class="form-control" rows="2"><?= e(v($gp,'authorization_note')) ?></textarea>

            </div>

        </div>



        <h6 class="mt-4">Items</h6>

        <div class="table-responsive">

            <table class="table table-sm line-items" data-type="gate_pass">

                <thead><tr><th>Description</th><th>Quantity</th><th>Unit</th><th>Remarks</th><th></th></tr></thead>

                <tbody>

                <?php foreach ($linesGp as $row): ?>

                <tr>

                    <td><input name="lines_gate_pass[][description]" class="form-control form-control-sm suggest" data-field="line_description" value="<?= e($row['description'] ?? '') ?>"></td>

                    <td><input name="lines_gate_pass[][quantity]" type="number" step="0.001" class="form-control form-control-sm" value="<?= e($row['quantity'] ?? '') ?>"></td>

                    <td>
                        <select name="lines_gate_pass[][unit]" class="form-select form-select-sm">
                            <option value="KG" <?= ($row['unit'] ?? 'KG') === 'KG' ? 'selected' : '' ?>>KG</option>
                            <option value="MT" <?= ($row['unit'] ?? 'KG') === 'MT' ? 'selected' : '' ?>>MT</option>
                        </select>
                    </td>

                    <td><input name="lines_gate_pass[][remarks]" class="form-control form-control-sm suggest" data-field="line_remarks" value="<?= e($row['remarks'] ?? '') ?>"></td>

                    <td><button type="button" class="btn btn-sm btn-outline-danger rm-row">×</button></td>

                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <button type="button" class="btn btn-sm btn-outline-primary add-line" data-target="gate_pass">+ Add Row</button>

    </div>

</div>

