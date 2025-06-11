/**
 * Main Stimulus application configuration
 * Registers all controllers for the application
 */
import { Application } from "@hotwired/stimulus";
import { definitionsFromContext } from "@hotwired/stimulus-webpack-helpers";

// Import controllers
import DashboardController from "./controllers/dashboard_controller";
import MessageComposerController from "./controllers/message_composer_controller";
import ConversationListController from "./controllers/conversation_list_controller";
import MessageInterfaceController from "./controllers/message_interface_controller";
import MessagePollingController from "./controllers/message_polling_controller";
import AdminDashboardController from "./controllers/admin_dashboard_controller";
import ApiController from "./controllers/api_controller";

// Import student controllers
import StudentMeetingsController from "./controllers/student_meetings_controller";
import StudentEvaluationsController from "./controllers/student_evaluations_controller";
import StudentPreferencesController from "./controllers/student_preferences_controller";

// Initialize Stimulus application
const application = Application.start();

// Register controllers manually
application.register("dashboard", DashboardController);
application.register("message-composer", MessageComposerController);
application.register("conversation-list", ConversationListController);
application.register("message-interface", MessageInterfaceController);
application.register("message-polling", MessagePollingController);
application.register("admin-dashboard", AdminDashboardController);
application.register("api", ApiController);

// Register student controllers
application.register("student-meetings", StudentMeetingsController);
application.register("student-evaluations", StudentEvaluationsController);
application.register("student-preferences", StudentPreferencesController);

// Register any other controllers automatically
// Uncomment this if you have a webpack context set up
// const context = require.context("./controllers", true, /\.js$/);
// application.load(definitionsFromContext(context));