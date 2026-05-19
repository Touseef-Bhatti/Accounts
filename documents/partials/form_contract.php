<?php /** @var array $ct @var array $prefill */ ?>
<div class="card doc-section shadow-sm mb-4">
    <div class="card-header">Export Sales Contract</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Contract No.</label>
                <input name="ec[contract_no]" class="form-control" value="<?= e(v($ct,'contract_no')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Contract Date</label>
                <input type="date" name="ec[contract_date]" class="form-control" value="<?= e(v($ct,'contract_date', date('Y-m-d'))) ?>"></div>
            <div class="col-md-4"><label class="form-label">Currency</label>
                <select name="ec[currency]" class="form-select">
                    <?php foreach (['USD','EUR','GBP','PKR'] as $cur): ?>
                    <option <?= v($ct,'currency','USD') === $cur ? 'selected' : '' ?>><?= $cur ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-6"><label class="form-label">Seller</label>
                <input name="ec[seller_name]" class="form-control" value="<?= e(v($ct,'seller_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Buyer</label>
                <input name="ec[buyer_name]" class="form-control suggest" data-field="buyer_name" value="<?= e(v($ct,'buyer_name')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Seller Address</label>
                <textarea name="ec[seller_address]" class="form-control" rows="2"><?= e(v($ct,'seller_address')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Buyer Address</label>
                <textarea name="ec[buyer_address]" class="form-control" rows="2"><?= e(v($ct,'buyer_address')) ?></textarea></div>
            <div class="col-12"><label class="form-label">Product Description</label>
                <textarea name="ec[product_description]" class="form-control" rows="3"><?= e(v($ct,'product_description')) ?></textarea></div>
            <div class="col-md-4"><label class="form-label">Quantity</label>
                <input name="ec[quantity]" class="form-control" value="<?= e(v($ct,'quantity')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Unit Price</label>
                <input name="ec[unit_price]" class="form-control" value="<?= e(v($ct,'unit_price')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Total Value</label>
                <input name="ec[total_value]" class="form-control" value="<?= e(v($ct,'total_value')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Delivery Terms</label>
                <textarea name="ec[delivery_terms]" class="form-control" rows="2"><?= e(v($ct,'delivery_terms', v($prefill,'delivery_terms',''))) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Payment Terms</label>
                <textarea name="ec[payment_terms]" class="form-control" rows="2"><?= e(v($ct,'payment_terms', v($prefill,'payment_terms',''))) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Port of Loading</label>
                <input name="ec[port_loading]" class="form-control" value="<?= e(v($ct,'port_loading')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Port of Discharge</label>
                <input name="ec[port_discharge]" class="form-control" value="<?= e(v($ct,'port_discharge')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Shipment Period</label>
                <input name="ec[shipment_period]" class="form-control" value="<?= e(v($ct,'shipment_period')) ?>" placeholder="e.g. Within 30 days of L/C"></div>
            <div class="col-md-6"><label class="form-label">Governing Law</label>
                <input name="ec[governing_law]" class="form-control" value="<?= e(v($ct,'governing_law', 'Laws of Pakistan')) ?>"></div>
            <div class="col-12"><label class="form-label">Inspection Terms</label>
                <textarea name="ec[inspection_terms]" class="form-control" rows="2"><?= e(v($ct,'inspection_terms', v($prefill,'inspection_terms',''))) ?></textarea></div>
            <div class="col-12"><label class="form-label">Force Majeure</label>
                <textarea name="ec[force_majeure]" class="form-control" rows="2"><?= e(v($ct,'force_majeure', v($prefill,'force_majeure',''))) ?></textarea></div>
            <div class="col-12"><label class="form-label">Arbitration / Jurisdiction</label>
                <textarea name="ec[arbitration]" class="form-control" rows="2"><?= e(v($ct,'arbitration', v($prefill,'arbitration',''))) ?></textarea></div>
            <div class="col-12"><label class="form-label">Special Conditions</label>
                <textarea name="ec[special_conditions]" class="form-control" rows="2"><?= e(v($ct,'special_conditions')) ?></textarea></div>
        </div>
    </div>
</div>
