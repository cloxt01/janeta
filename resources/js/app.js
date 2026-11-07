import './bootstrap';
import { createIcons, icons } from 'lucide';

createIcons({ icons });

document.querySelectorAll('.jn-dropdown').forEach((dropdown) => {
    dropdown.addEventListener('toggle', () => {
        if (!dropdown.open) {
            return;
        }

        document.querySelectorAll('.jn-dropdown').forEach((otherDropdown) => {
            if (otherDropdown !== dropdown) {
                otherDropdown.removeAttribute('open');
            }
        });
    });
});
