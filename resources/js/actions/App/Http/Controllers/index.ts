import SslDashboardController from './SslDashboardController'
import WebsiteController from './WebsiteController'
import AlertConfigurationController from './AlertConfigurationController'
import TeamController from './TeamController'
import TeamInvitationController from './TeamInvitationController'
import Settings from './Settings'
import Auth from './Auth'

const Controllers = {
    SslDashboardController: Object.assign(SslDashboardController, SslDashboardController),
    WebsiteController: Object.assign(WebsiteController, WebsiteController),
    AlertConfigurationController: Object.assign(AlertConfigurationController, AlertConfigurationController),
    TeamController: Object.assign(TeamController, TeamController),
    TeamInvitationController: Object.assign(TeamInvitationController, TeamInvitationController),
    Settings: Object.assign(Settings, Settings),
    Auth: Object.assign(Auth, Auth),
}

export default Controllers