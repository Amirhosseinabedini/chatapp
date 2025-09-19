const CACHE_NAME = 'chatapp-v1.0.0';
const STATIC_CACHE_URLS = [
    '/',
    '/chat/',
    '/groups/',
    '/profile/',
    '/assets/styles/app.css',
    '/images/default-avatar.svg',
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .then(() => {
                console.log('Service Worker installed');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker installation failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip requests to external domains (except CDNs)
    const url = new URL(event.request.url);
    if (url.origin !== location.origin && 
        !url.hostname.includes('cdn.jsdelivr.net') && 
        !url.hostname.includes('cdnjs.cloudflare.com')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                return response || fetch(event.request)
                    .then(fetchResponse => {
                        // Don't cache API requests or dynamic content
                        if (event.request.url.includes('/api/') || 
                            event.request.url.includes('/_profiler/') ||
                            event.request.url.includes('/_wdt/')) {
                            return fetchResponse;
                        }

                        // Cache successful responses
                        if (fetchResponse.status === 200) {
                            const responseClone = fetchResponse.clone();
                            caches.open(CACHE_NAME)
                                .then(cache => {
                                    cache.put(event.request, responseClone);
                                });
                        }
                        return fetchResponse;
                    })
                    .catch(error => {
                        console.error('Fetch failed:', error);
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                        throw error;
                    });
            })
    );
});

// Push event - handle push notifications
self.addEventListener('push', event => {
    console.log('Push message received');
    
    let notificationData = {
        title: 'Chat App',
        body: 'You have a new message',
        icon: '/images/icon-192x192.png',
        badge: '/images/icon-96x96.png',
        tag: 'chat-notification',
        requireInteraction: false,
        actions: [
            {
                action: 'open',
                title: 'Open Chat',
                icon: '/images/icon-96x96.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss'
            }
        ]
    };

    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = { ...notificationData, ...data };
        } catch (error) {
            console.error('Error parsing push data:', error);
        }
    }

    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked');
    
    event.notification.close();

    if (event.action === 'dismiss') {
        return;
    }

    // Default action or 'open' action
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // Check if chat app is already open
                for (const client of clientList) {
                    if (client.url.includes(location.origin) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Open new window if app is not open
                if (clients.openWindow) {
                    return clients.openWindow('/');
                }
            })
    );
});

// Background sync for offline message sending
self.addEventListener('sync', event => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'send-message') {
        event.waitUntil(sendOfflineMessages());
    }
});

// Function to send offline messages when back online
async function sendOfflineMessages() {
    try {
        // Get offline messages from IndexedDB
        const messages = await getOfflineMessages();
        
        for (const message of messages) {
            try {
                const response = await fetch('/group-messages/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(message.data)
                });
                
                if (response.ok) {
                    // Remove message from offline storage
                    await removeOfflineMessage(message.id);
                }
            } catch (error) {
                console.error('Failed to send offline message:', error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// IndexedDB functions for offline storage
function getOfflineMessages() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ChatAppDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineMessages'], 'readonly');
            const store = transaction.objectStore('offlineMessages');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
        
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains('offlineMessages')) {
                db.createObjectStore('offlineMessages', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

function removeOfflineMessage(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ChatAppDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineMessages'], 'readwrite');
            const store = transaction.objectStore('offlineMessages');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

// Message event from main thread
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(CACHE_NAME)
                .then(cache => cache.addAll(event.data.urls))
        );
    }
});

