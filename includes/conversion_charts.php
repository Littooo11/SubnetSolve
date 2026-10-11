<?php
// includes/conversion_charts.php
// Reference charts shown on the Binary and Hex practice pages.
// Self-contained: includes its own styles, so it works on any page.

function cc_styles() {
    static $done = false;
    if ($done) return "";
    $done = true;
    return <<<'CSS'
<style>
.cc-wrap { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem 1.3rem; margin-top: 1.2rem; }
.cc-wrap summary { cursor: pointer; font-weight: bold; font-size: 0.95rem; list-style: none; }
.cc-wrap summary::-webkit-details-marker { display: none; }
.cc-wrap summary .cc-hint { font-weight: normal; font-size: 0.72rem; color: var(--text-dim); margin-left: 0.5rem; }
.cc-label { font-size: 0.75rem; color: var(--text-dim); margin: 1rem 0 0.5rem; text-transform: uppercase; letter-spacing: 0.04em; }
.cc-row { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.cc-cell { min-width: 70px; text-align: center; background: var(--panel-light); border: 1px solid var(--border); border-radius: 8px; padding: 0.5rem 0.8rem; }
.cc-big { font-family: Consolas, monospace; font-size: 1.3rem; font-weight: bold; color: var(--blue); }
.cc-small { font-size: 0.7rem; color: var(--text-dim); margin-top: 0.15rem; }
.cc-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 0.4rem; }
.cc-hex { text-align: center; background: var(--panel-light); border: 1px solid var(--border); border-radius: 8px; padding: 0.4rem 0.2rem; }
.cc-hex .cc-big { font-size: 1.15rem; color: var(--green); }
.cc-hex .cc-dec { font-size: 0.82rem; font-weight: bold; }
.cc-hex .cc-bin { font-family: Consolas, monospace; font-size: 0.72rem; color: var(--text-dim); }
.cc-tip { font-size: 0.8rem; color: var(--text-dim); margin: 0.9rem 0 0; }
.cc-tip b { color: var(--text); }
@media (max-width: 700px) { .cc-grid { grid-template-columns: repeat(4, 1fr); } }
</style>
CSS;
}

// Place values (powers of 2) sized to the difficulty's digit count.
function render_binary_chart($digits) {
    ob_start();
    echo cc_styles();
    ?>
    <details class="cc-wrap" open>
        <summary>📊 Binary Reference Chart <span class="cc-hint">(click to hide / show)</span></summary>

        <div class="cc-label">Place values for <?= $digits ?>-digit binary</div>
        <div class="cc-row">
            <?php for ($i = $digits - 1; $i >= 0; $i--): ?>
                <div class="cc-cell">
                    <div class="cc-big"><?= 1 << $i ?></div>
                    <div class="cc-small">2<sup><?= $i ?></sup></div>
                </div>
            <?php endfor; ?>
        </div>

        <p class="cc-tip"><b>How to use it:</b> line your binary number up under these values, then add up the place values wherever there's a <b>1</b>. A <b>0</b> adds nothing.</p>
    </details>
    <?php
    return ob_get_clean();
}

// Hex digit table (0-F with decimal + binary) and place values (powers of 16).
function render_hex_chart($digits) {
    ob_start();
    echo cc_styles();
    ?>
    <details class="cc-wrap" open>
        <summary>📊 Hex Reference Chart <span class="cc-hint">(click to hide / show)</span></summary>

        <div class="cc-label">Hex digits → decimal → binary</div>
        <div class="cc-grid">
            <?php for ($n = 0; $n < 16; $n++): ?>
                <div class="cc-hex">
                    <div class="cc-big"><?= strtoupper(dechex($n)) ?></div>
                    <div class="cc-dec"><?= $n ?></div>
                    <div class="cc-bin"><?= str_pad(decbin($n), 4, "0", STR_PAD_LEFT) ?></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="cc-label">Place values for <?= $digits ?>-digit hex</div>
        <div class="cc-row">
            <?php for ($i = $digits - 1; $i >= 0; $i--): ?>
                <div class="cc-cell">
                    <div class="cc-big"><?= number_format(16 ** $i) ?></div>
                    <div class="cc-small">16<sup><?= $i ?></sup></div>
                </div>
            <?php endfor; ?>
        </div>

        <p class="cc-tip"><b>How to use it:</b> look up each digit's decimal value in the top chart (A=10 … F=15), multiply it by its place value, then add the results together.</p>
    </details>
    <?php
    return ob_get_clean();
}