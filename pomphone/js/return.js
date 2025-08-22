document.getElementById('load_items').addEventListener('click', () => {
    const receiptNo = document.getElementById('receipt_input').value.trim();
    if (!receiptNo) {
        alert('กรุณากรอกเลขใบเสร็จ');
        return;
    }

    fetch('../api/fetch_return_items.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({receipt_no: receiptNo})
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            const tbody = document.getElementById('item_list');
            tbody.innerHTML = '';
            data.items.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" name="return_items" value="\${item.imei}"></td>
                    <td>\${item.product_name}</td>
                    <td>\${item.imei}</td>
                    <td>\${item.price}</td>
                `;
                tbody.appendChild(row);
            });
            document.getElementById('product_section').style.display = 'block';
        }
    })
    .catch(err => alert('เกิดข้อผิดพลาดในการโหลดสินค้า'));
});

document.getElementById('return_form').addEventListener('submit', function(e) {
    e.preventDefault();

    const selected = [...document.querySelectorAll('input[name="return_items"]:checked')].map(i => i.value);
    const reason = document.getElementById('return_reason').value.trim();
    const method = document.getElementById('refund_method').value;
    const receipt_no = document.getElementById('receipt_input').value.trim();

    if (selected.length === 0) return alert('กรุณาเลือก IMEI ที่ต้องการคืน');
    if (!reason) return alert('กรุณากรอกเหตุผลการคืน');
    if (!method) return alert('กรุณาเลือกวิธีการคืนเงิน');

    fetch('../api/process_return.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            receipt_no,
            items: selected,
            reason,
            method
        })
    })
    .then(res => res.text())
    .then(resp => {
        alert(resp);
        location.reload();
    })
    .catch(err => alert('เกิดข้อผิดพลาดในการคืนสินค้า'));
});