self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const notification = event.data.json();

    event.waitUntil(
        self.registration.showNotification(notification.title, {
            body: notification.body,
            icon: './assets/images/favicon.ico',
            data: {
                url: notification.url,
            },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.notification.data?.url) {
        event.waitUntil(clients.openWindow(event.notification.data.url));
    }
});
