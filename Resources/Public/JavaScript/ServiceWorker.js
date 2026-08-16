'use strict';

self.addEventListener('push', (event) => {
    if (event.data === null) {
        return;
    }

    const payload = event.data.json();
    event.waitUntil(self.registration.showNotification(payload.title, payload.options || {}));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data && event.notification.data.url;
    if (typeof url !== 'string' || url === '') {
        return;
    }

    event.waitUntil(self.clients.openWindow(url));
});
