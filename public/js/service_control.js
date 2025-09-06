document.addEventListener('DOMContentLoaded', function () {
    function formatPrice(price) {
        if (!price || isNaN(price)) return '';
        return parseFloat(price).toLocaleString('en-US') + ' VNĐ';
    }

    document.querySelectorAll('td:nth-child(3)').forEach(cell => {
        cell.textContent = formatPrice(cell.textContent.replace(/,/g, '').replace(' VNĐ', ''));
    });

    document.getElementById('addServiceBtn').onclick = function () {
        document.getElementById('formTitle').innerText = 'Thêm dịch vụ';
        document.getElementById('serviceFormElement').action = '/admin/services/store';
        document.getElementById('serviceId').value = '';
        document.getElementById('name').value = '';
        document.getElementById('price').value = '';
        document.getElementById('description').value = '';
        document.getElementById('serviceForm').classList.remove('hidden');
    };

    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const price = btn.dataset.price;
            const description = btn.dataset.description;

            document.getElementById('formTitle').innerText = 'Sửa dịch vụ';
            document.getElementById('serviceFormElement').action = `/admin/services/update/${id}`;
            document.getElementById('serviceId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('price').value = parseFloat(price).toLocaleString('en-US');
            document.getElementById('description').value = description;

            document.getElementById('serviceForm').classList.remove('hidden');
        };
    });

    document.getElementById('cancelBtn').onclick = function () {
        document.getElementById('serviceForm').classList.add('hidden');
    };

    document.querySelectorAll('.deleteBtn').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            const form = document.getElementById('deleteForm');
            form.action = `/admin/services/delete/${id}`;
            document.getElementById('deleteConfirm').classList.remove('hidden');
        };
    });

    document.getElementById('cancelDeleteBtn').onclick = function () {
        document.getElementById('deleteConfirm').classList.add('hidden');
    };

    // Format khi nhập giá
    const priceInput = document.getElementById('price');
    priceInput.addEventListener('input', function () {
        let number = this.value.replace(/[^0-9]/g, '');
        if (number) {
            this.value = new Intl.NumberFormat('en-US').format(number);
        } else {
            this.value = '';
        }
    });

    // Loại bỏ dấu ',' khi submit form (thêm/sửa)
    const serviceForm = document.getElementById('serviceFormElement');
    serviceForm.addEventListener('submit', function () {
        const rawPrice = document.getElementById('price').value.replace(/,/g, '');
        document.getElementById('price').value = rawPrice;
    });
});