document.addEventListener('DOMContentLoaded', () => {
    const addressInput = document.getElementById('address');
    const linkInput = document.getElementById('link_address');
    const mapContainer = document.getElementById('map-container');
    const logoInput = document.getElementById('hotel-info__logo-input');
    const logoPreview = document.getElementById('hotel-info__logo-preview');

    // Đổi ảnh logo khi chọn
    logoInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                logoPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    let debounceTimer;

    addressInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const address = addressInput.value;
            if (address.length > 5) {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            const lat = data[0].lat;
                            const lon = data[0].lon;
                            const embedUrl = `https://www.google.com/maps/embed/v1/view?key=AIzaSyBWb5fL7nCFYqPY35QNotTT99PxPY3UpLw&center=${lat},${lon}&zoom=16`;
                            linkInput.value = embedUrl;

                            let iframe = document.getElementById('map-frame');
                            if (!iframe) {
                                iframe = document.createElement('iframe');
                                iframe.id = 'map-frame';
                                iframe.width = '100%';
                                iframe.height = '300';
                                iframe.style.border = '0';
                                iframe.setAttribute('allowfullscreen', '');
                                iframe.setAttribute('loading', 'lazy');
                                mapContainer.appendChild(iframe);
                            }
                            iframe.src = embedUrl;
                        }
                    });
            }
        }, 500);
    });
});
