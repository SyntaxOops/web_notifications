(() => {
    'use strict';

    const script = document.currentScript;
    if (!(script instanceof HTMLScriptElement)) {
        return;
    }

    const applicationServerKey = script.dataset.applicationServerKey || '';
    const serviceWorkerUrl = script.dataset.serviceWorkerUrl || '';
    const serviceWorkerScope = script.dataset.serviceWorkerScope || '/';
    const subscriptionUrl = script.dataset.subscriptionUrl || '';

    document.addEventListener('DOMContentLoaded', initialize, {once: true});

    async function initialize() {
        if (
            applicationServerKey === ''
            || serviceWorkerUrl === ''
            || subscriptionUrl === ''
            || !('serviceWorker' in navigator)
            || !('PushManager' in window)
            || !('showNotification' in ServiceWorkerRegistration.prototype)
            || Notification.permission === 'denied'
        ) {
            return;
        }

        try {
            const registration = await navigator.serviceWorker.register(serviceWorkerUrl, {
                scope: serviceWorkerScope,
            });
            let subscription = await registration.pushManager.getSubscription();
            if (subscription === null) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(applicationServerKey),
                });
            }

            await saveSubscription(subscription);
        } catch (error) {
            console.error('[Web Notifications] Subscription failed.', error);
        }
    }

    async function saveSubscription(subscription) {
        const response = await fetch(subscriptionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                subscription: subscription.toJSON(),
                contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
            }),
        });

        if (!response.ok) {
            throw new Error(`The subscription endpoint returned HTTP ${response.status}.`);
        }
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let index = 0; index < rawData.length; index += 1) {
            outputArray[index] = rawData.charCodeAt(index);
        }

        return outputArray;
    }
})();
