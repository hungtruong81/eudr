// At the top of the file
importScripts('./version.js');

function getCurrentWeekAndYear() {
  const currentDate = new Date();
  const year = currentDate.getFullYear();
  const startOfYear = new Date(year, 0, 1);
  const pastDaysOfYear =
    (currentDate -
      startOfYear +
      (startOfYear.getTimezoneOffset() - currentDate.getTimezoneOffset()) * 60 * 1000) /
    86400000;
  const weekNumber = Math.ceil((pastDaysOfYear + startOfYear.getDay() + 1) / 7);
  return { year, weekNumber };
}

const { year, weekNumber } = getCurrentWeekAndYear();

// Get version from imported version.json
const version = self.version || { major: 1, minor: 0, revision: 0 };
const versionString = `${version.major}.${version.minor}.${version.revision}`;

// auto create cacheName after every week by datetime
const cacheName = `cache-${year}-${weekNumber}-v${versionString}`;

const installEvent = () => {
  self.addEventListener("install", (event) => {
    console.log("Service Worker installed");
    event.waitUntil(
      caches.open(cacheName).then((cache) => {
        // Pre-cache assets if needed
      }),
    );
  });
};

const activateEvent = () => {
  self.addEventListener("activate", (event) => {
    console.log("Service Worker activated");
    event.waitUntil(
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((name) => {
            if (name !== cacheName) {
              return caches.delete(name);
            }
          }),
        );
      }),
    );
  });
};

const cacheClone = async (request) => {
  try {
    const response = await fetch(request);
    const responseClone = response.clone();
    const cache = await caches.open(cacheName);
    await cache.put(request, responseClone);
    return response;
  } catch (error) {
    console.log(request.url);
    console.error("Fetch failed; returning cached page instead.", error);
    const cachedResponse = await caches.match(request);
    return cachedResponse || new Response("Network error occurred", { status: 408 });
  }
};

const fetchEvent = () => {
  self.addEventListener("fetch", (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // allow cache from amazonaws.com
    if (url.hostname.includes("amazonaws.com")) {
    } else {
      // Ignore requests to external sites
      if (url.origin !== location.origin) {
        return;
      }
    }

    // skip post request
    if (request.method === "POST") {
      return;
    }

    // skip html type
    if (request.headers.get("Accept").includes("text/html")) {
      return;
    }

    // skip sw.js
    if (request.url.includes("sw.js")) {
      return;
    }

    event.respondWith(cacheClone(request));
  });
};

// Initialize service worker events
installEvent();
activateEvent();
fetchEvent();
