import SslDashboardController from './SslDashboardController'
import WebsiteController from './WebsiteController'
import Settings from './Settings'
import AlertConfigurationController from './AlertConfigurationController'
import TeamController from './TeamController'
import TeamInvitationController from './TeamInvitationController'
import Debug from './Debug'
import Auth from './Auth'

const Controllers = {
    SslDashboardController: Object.assign(SslDashboardController, SslDashboardController),
    WebsiteController: Object.assign(WebsiteController, WebsiteController),
    Settings: Object.assign(Settings, Settings),
    AlertConfigurationController: Object.assign(AlertConfigurationController, AlertConfigurationController),
    TeamController: Object.assign(TeamController, TeamController),
    TeamInvitationController: Object.assign(TeamInvitationController, TeamInvitationController),
    Debug: Object.assign(Debug, Debug),
    Auth: Object.assign(Auth, Auth),
}

export default Controllers