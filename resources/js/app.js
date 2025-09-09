import './bootstrap';

import Alpine from 'alpinejs';

import { formatRupiah } from "./helpers";  // kasih .js biar pasti
window.formatRupiah = formatRupiah;

window.Alpine = Alpine;

Alpine.start();
