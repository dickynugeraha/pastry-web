import Alpine from "alpinejs";
import { registerBlatUI } from "./blatui-core.js";
import productPhotoPicker from "./product-photo-picker.js";

window.Alpine = Alpine;

registerBlatUI(Alpine);
Alpine.data("productPhotoPicker", productPhotoPicker);
Alpine.start();

document.querySelectorAll('[data-midtrans-pay]').forEach((button) => {
    button.addEventListener('click', () => {
        const message = document.querySelector('[data-payment-message]');
        if (!window.snap) {
            message.textContent = 'Midtrans belum dimuat. Muat ulang halaman lalu coba lagi.';
            return;
        }
        button.disabled = true;
        const refresh = async () => {
            message.textContent = 'Memeriksa status pembayaran…';
            try {
                await fetch(button.dataset.syncUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                });
            } finally {
                window.location.reload();
            }
        };
        window.snap.pay(button.dataset.token, {
            onSuccess: refresh,
            onPending: refresh,
            onError: refresh,
            onClose: () => {
                button.disabled = false;
                message.textContent = 'Pembayaran belum selesai. Anda dapat melanjutkan pembayaran.';
            },
        });
    });
});
