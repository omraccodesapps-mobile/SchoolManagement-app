import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
// Register our advanced UI Stimulus controllers so they're available app-wide.
import AdvancedCardController from './controllers/advanced_card_controller.js';
import AdvancedModalController from './controllers/advanced_modal_controller.js';
import AdvancedFormController from './controllers/advanced_form_controller.js';
import AdvancedToastController from './controllers/advanced_toast_controller.js';
import AdvancedCollapseController from './controllers/advanced_collapse_controller.js';

app.register('advanced-card', AdvancedCardController);
app.register('advanced-modal', AdvancedModalController);
app.register('advanced-form', AdvancedFormController);
app.register('advanced-toast', AdvancedToastController);
app.register('advanced-collapse', AdvancedCollapseController);
