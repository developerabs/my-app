document.getElementById('enablePush').addEventListener('click', async () => {
    if (!('Notification' in window)) {
        alert('Browser notification supported না');
        return;
    }

    const permission = await Notification.requestPermission();

    if (permission === 'granted') {
        alert('✅ Notification enabled');
    } else {
        alert('❌ Permission denied');
    }
});
