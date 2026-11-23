import './bootstrap';
import './janeta-loading';

import 'flowbite';

import { createIcons, icons } from 'lucide';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

import 'sweetalert2/dist/sweetalert2.min.css';

createIcons({ icons });

document.querySelectorAll('.jn-dropdown').forEach((dropdown) => {
    dropdown.addEventListener('toggle', () => {
        if (!dropdown.open) return;

        document.querySelectorAll('.jn-dropdown').forEach((other) => {
            if (other !== dropdown) {
                other.removeAttribute('open');
            }
        });
    });
});

// Global UI
window.Swal = Swal;
window.Alpine = Alpine;

Alpine.start();
