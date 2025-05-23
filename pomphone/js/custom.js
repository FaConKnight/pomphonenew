// custom.js - Hotkey สำหรับเปิดรายงานยอดขาย

document.addEventListener('keydown', function(event) {
    // ป้องกันไม่ให้ทำงานขณะพิมพ์ใน input หรือ textarea
    if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;

    // ถ้ากดเครื่องหมายเท่ากับ (=)
    if (event.key === '=') {
        const pw = prompt("กรุณาใส่รหัสผ่านเพื่อดูรายงานยอดขายวันนี้:");
        if (pw === '2535') {
            window.open('../pos/print_daily_summary.php', '_blank');
        } else {
            alert("รหัสผ่านไม่ถูกต้อง");
        }
    }
});
