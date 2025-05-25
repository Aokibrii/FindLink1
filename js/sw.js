const CACHE_NAME = "lost-and-found-v1";
const urlsToCache = [
  "/",
  "/index.php",
  "/user_page.php",
  "/Profile.php",
  "/Post_Lost_and_Found.php",
  "/css/user_page.css",
  "/css/home.css",
  "/css/featured-item.css",
  "/js/script.js",
  "/images/Icon.jpg",
  "/images/default-item.jpg",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      if (response) {
        return response;
      }
      return fetch(event.request).then((response) => {
        if (!response || response.status !== 200 || response.type !== "basic") {
          return response;
        }
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
        return response;
      });
    })
  );
});
