<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;


$logo_path = __DIR__ . '/assets/logo.png';
$signature_path = __DIR__ . '/assets/signature.png';

$logo_base64 = '';
$signature_base64 = '';

if (file_exists($logo_path)) {
    $logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
}
if (file_exists($signature_path)) {
    $signature_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($signature_path));
}



$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$employee_id) { die("Invalid employee ID."); }

$employee = $conn->query("SELECT * FROM employee WHERE id = $employee_id")->fetch_assoc();
if (!$employee) die("Employee not found");

$items = [];
$res = $conn->query("SELECT i.asset_id, i.serial_number, i.name
                     FROM assignments a
                     JOIN items i ON a.item_id = i.item_id
                     WHERE a.user_id = $employee_id AND a.return_date IS NULL");
if ($res) while ($row = $res->fetch_assoc()) $items[] = $row;

$today = date("Y-m-d");

ob_start();
?>
<!DOCTYPE html>
<html><head><style>
    @page {
        margin: 20mm 20mm 30mm 20mm; /* Leave more space at bottom for footer */
    }
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
.logo { width: 120px; }
.items-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.items-table th, .items-table td { border: 1px solid #000; padding: 6px; text-align: left; }
.agreement_text {text-align: justify; font-size:10px;}
.signature { margin-top: 30px; }
.signature-box { text-align: left; }
.signature-box-bottom{ text-align: left; }
.signature-box img { height: 50px; margin-bottom: 5px; }
/* Repeating footer styles */
.page_footer {
    position: fixed;
    left: 0;
    bottom: -50px; /* adjust as needed */
    right: 0;
    height: 30px; /* height of footer */
    text-align: center;
    font-size: 11px;
    color: #555;
}
</style></head><body>
<?php if ($logo_base64): ?>
<img src="<?php echo $logo_base64; ?>" class="logo">
<?php endif; ?>
<h3>Leverage General Contracting L.L.C</h3>
<p><strong>From:</strong> Leverage - IT Department<br>
<strong>Date:</strong> <?php echo $today; ?></p>
<p><strong>Receiver:</strong> <?php echo $employee['employee_id'] . ' - ' . $employee['employee_name']; ?><br></p>
<table class="items-table"><thead><tr>
<th>S. No</th><th>Asset Code</th><th>Serial Number</th><th>Item Description</th>
</tr></thead><tbody>
<?php foreach ($items as $i => $item): ?>
<tr><td><?php echo $i + 1; ?></td>
<td><?php echo htmlspecialchars($item['asset_id']); ?></td>
<td><?php echo strtoupper(htmlspecialchars($item['serial_number'])); ?></td>
<td><?php echo htmlspecialchars($item['name']); ?></td></tr>
<?php endforeach; ?></tbody></table>



<p class="agreement_text">
I, the undersigned, hereby confirm that I have received all the items listed above in the condition stated. 
I understand that these items are provided for official use, and I undertake to return them either upon completion 
of the assigned purpose, at the request of management, or prior to taking leave. I accept full responsibility for 
maintaining the items in good condition and agree to be held accountable for any loss, damage, or misuse. I further 
acknowledge that the organization reserves the right to recover the cost of any such loss or damage through appropriate 
deductions from my salary or personal funds.
</p>
<br>
<p><?php echo $employee['employee_id'] . ' - ' . $employee['employee_name']; ?><br>
<strong>Designation:</strong> <?php echo $employee['designation']; ?></p><br><br>

<div class="signature">
    <div class="signature-box">
        <?php if ($signature_base64): ?>
        <img src="<?php echo $signature_base64; ?>" alt="Signature">
        <?php endif; ?>
        <div class="signature-box-bottom">
            <div><strong>Issued By:</strong> <?php echo ISSUEDBY_NAME; ?></div>
            <div><strong>Designation:</strong> <?php echo ISSUEDBY_DESIGNATION; ?></div>
        </div>
    </div>
</div>
<!-- Repeating Footer -->
<div class="page_footer">
    IT Inventory System | by Leverage IT
</div>
</body></html>
<?php

$html = ob_get_clean();
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Assigned_Items_{$employee['employee_id']}.pdf", ["Attachment" => false]); 
?>
