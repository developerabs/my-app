// Online SVG
const onlineSVG = `
<svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" width="24" height="24" viewBox="0 0 24 24"><title>Online</title><path fill="green" d="M12 12C9.97 12 8.1 12.67 6.6 13.8L4.8 11.4C6.81 9.89 9.3 9 12 9S17.19 9.89 19.2 11.4L17.92 13.1C17.55 13.17 17.18 13.27 16.84 13.41C15.44 12.5 13.78 12 12 12M21 9L22.8 6.6C19.79 4.34 16.05 3 12 3S4.21 4.34 1.2 6.6L3 9C5.5 7.12 8.62 6 12 6S18.5 7.12 21 9M12 15C10.65 15 9.4 15.45 8.4 16.2L12 21L13.04 19.61C13 19.41 13 19.21 13 19C13 17.66 13.44 16.43 14.19 15.43C13.5 15.16 12.77 15 12 15M17.75 19.43L16.16 17.84L15 19L17.75 22L22.5 17.25L21.34 15.84L17.75 19.43Z" /></svg>
`;

// Offline SVG
const offlineSVG = `
<svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" width="24" height="24" viewBox="0 0 24 24"><title>offline</title><path fill="red" d="M12 6C8.62 6 5.5 7.12 3 9L1.2 6.6C4.21 4.34 7.95 3 12 3S19.79 4.34 22.8 6.6L21 9C18.5 7.12 15.38 6 12 6M15.53 12.72C16.42 12.26 17.43 12 18.5 12C18.58 12 18.66 12 18.74 12L19.2 11.4C17.19 9.89 14.7 9 12 9S6.81 9.89 4.8 11.4L6.6 13.8C8.1 12.67 9.97 12 12 12C13.26 12 14.45 12.26 15.53 12.72M12 15C10.65 15 9.4 15.45 8.4 16.2L12 21L12.34 20.54C12.13 19.9 12 19.22 12 18.5C12 17.24 12.36 16.08 13 15.08C12.66 15.03 12.33 15 12 15M23 18.5C23 21 21 23 18.5 23S14 21 14 18.5 16 14 18.5 14 23 16 23 18.5M20 21.08L15.92 17C15.65 17.42 15.5 17.94 15.5 18.5C15.5 20.16 16.84 21.5 18.5 21.5C19.06 21.5 19.58 21.35 20 21.08M21.5 18.5C21.5 16.84 20.16 15.5 18.5 15.5C17.94 15.5 17.42 15.65 17 15.92L21.08 20C21.35 19.58 21.5 19.06 21.5 18.5Z" /></svg>
`;


function updateNetworkSVG() {
    const container = document.getElementById('wifi-icon-container');

    if (navigator.onLine) {
        container.innerHTML = onlineSVG;
        container.title = 'Online';
    } else {
        container.innerHTML = offlineSVG;
        container.title = 'Offline';
    }
}

// Page load + network change
window.addEventListener('load', updateNetworkSVG);
window.addEventListener('online', updateNetworkSVG);
window.addEventListener('offline', updateNetworkSVG);