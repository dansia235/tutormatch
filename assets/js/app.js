// Import CSS
import '../css/app.css';

// Import Stimulus
import { Application } from '@hotwired/stimulus';

// Create Stimulus application
const application = Application.start();

// Import controllers
import ChartController from './controllers/chart_controller';
import DropdownController from './controllers/dropdown_controller';
import FormController from './controllers/form_controller';
import FormFieldController from './controllers/form_field_controller';
import ModalController from './controllers/modal_controller';
import TabsController from './controllers/tabs_controller';
import SidebarController from './controllers/sidebar_controller';
import AlertController from './controllers/alert_controller';
import FilterController from './controllers/filter_controller';
import LiveSearchController from './controllers/live_search_controller';
import DaterangeController from './controllers/daterange_controller';
import NotificationController from './controllers/notification_controller';
import AssignmentMatrixController from './controllers/assignment_matrix_controller';
import AssignmentAlgorithmController from './controllers/assignment_algorithm_controller';
import AdminDashboardApiController from './controllers/admin_dashboard_controller_api';
import SettingsController from './controllers/settings_controller';
import StudentMeetingsController from './controllers/student_meetings_controller';

// Register controllers with the application
application.register('chart', ChartController);
application.register('dropdown', DropdownController);
application.register('form', FormController);
application.register('form-field', FormFieldController);
application.register('modal', ModalController);
application.register('tabs', TabsController);
application.register('sidebar', SidebarController);
application.register('alert', AlertController);
application.register('filter', FilterController);
application.register('live-search', LiveSearchController);
application.register('daterange', DaterangeController);
application.register('notification', NotificationController);
application.register('assignment-matrix', AssignmentMatrixController);
application.register('assignment-algorithm', AssignmentAlgorithmController);
application.register('admin-dashboard-api', AdminDashboardApiController);
application.register('settings', SettingsController);
application.register('student-meetings', StudentMeetingsController);

// Make app globally accessible
window.application = application;
window.Stimulus = application;

// Import API client
import './api-init';

// Import other modules
import './modules/animations';
import './modules/theme';
import './modules/assignment-algorithms';