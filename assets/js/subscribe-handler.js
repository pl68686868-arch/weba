/**
 * Newsletter Subscription Handler
 * AJAX form submission for the footer newsletter
 */
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('newsletter-form');
    if (!form) return;

    const emailInput = document.getElementById('newsletter-email');
    const submitBtn = document.getElementById('newsletter-submit');
    const messageEl = document.getElementById('newsletter-message');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const email = emailInput.value.trim();
        if (!email) return;

        // UI: Loading state
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Đang gửi...';
        submitBtn.disabled = true;
        messageEl.style.display = 'none';

        try {
            const formData = new FormData(form);

            const response = await fetch('/subscribe.php', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            // Show message
            messageEl.style.display = 'block';
            messageEl.textContent = data.message;
            messageEl.style.color = data.success
                ? 'var(--color-gold, #ECB613)'
                : 'var(--color-text-secondary, #999)';

            if (data.success) {
                emailInput.value = '';
                // Show toast if available
                if (typeof window.showToast === 'function') {
                    window.showToast(data.message, 'success');
                }
            }
        } catch (error) {
            messageEl.style.display = 'block';
            messageEl.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
            messageEl.style.color = 'var(--color-text-secondary, #999)';
        } finally {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    });
});
