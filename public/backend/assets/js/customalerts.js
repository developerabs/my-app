function showFloatingAlert(type, message) {
    // পুরোনো alert থাকলে সরিয়ে ফেল
    $('.floating-alert').remove();

    // type অনুযায়ী bootstrap alert class ঠিক করা
    let alertClass = '';
    switch (type) {
        case 'success':
            alertClass = 'alert-success';
            break;
        case 'error':
            alertClass = 'alert-danger';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            break;
        default:
            alertClass = 'alert-info';
            break;
    }

    // alert HTML তৈরি করা
    const alertHtml = `
        <div class="alert ${alertClass} floating-alert">
            <strong>${type.charAt(0).toUpperCase() + type.slice(1)}!</strong> ${message}
        </div>
    `;

    // body তে যোগ করা
    $('body').append(alertHtml);

    // 5 সেকেন্ড পরে remove করা (fadeOut animation শেষে)
    setTimeout(() => {
        $('.floating-alert').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
}