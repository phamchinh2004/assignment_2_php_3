import { observeLocalDateTimes } from './shared/datetime';
import './bootstrap';
import './echo';
import './ui/dialog';
import '../css/dialog.css';
import Fireworks from 'fireworks-js';
window.Fireworks = Fireworks;
import AutoNumeric from 'autonumeric';
window.AutoNumeric = AutoNumeric;
import SlimSelect from 'slim-select';
import 'slim-select/styles';
window.SlimSelect = SlimSelect;

const startLocalDateTimeObserver = () => observeLocalDateTimes(document.body);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startLocalDateTimeObserver, { once: true });
} else {
    startLocalDateTimeObserver();
}
