import './bootstrap';
import './janeta-loading';

import 'flowbite';

import { createIcons, icons } from 'lucide';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

import 'sweetalert2/dist/sweetalert2.min.css';

createIcons({ icons });

// Global UI
window.Swal = Swal;
window.Alpine = Alpine;

Alpine.start();
