import {
  Shield,
  BarChart3,
  Settings,
  AlertTriangle,
  Monitor,
  Clock,
  Users,
  HelpCircle,
  TrendingUp,
} from 'lucide-vue-next'

export interface MenuItem {
  key: string
  title: string
  icon: any
  href?: string
  description?: string
  disabled?: boolean
  children?: {
    title: string
    href: string
  }[]
}

// Main navigation menu items
export const mainMenuItems: MenuItem[] = [
  {
    key: 'dashboard',
    title: 'Dashboard',
    icon: BarChart3,
    href: '/dashboard',
    description: 'SSL & Uptime overview'
  },
  {
    key: 'websites',
    title: 'Websites',
    icon: Monitor,
    href: '/ssl/websites',
    description: 'Manage monitored sites'
  },
  {
    key: 'certificates',
    title: 'SSL Certificates',
    icon: Shield,
    disabled: true,
    children: [
      { title: 'All Certificates', href: '/ssl/certificates' },
      { title: 'Expiring Soon', href: '/ssl/certificates/expiring' },
      { title: 'Issues & Alerts', href: '/ssl/certificates/issues' },
      { title: 'Bulk Operations', href: '/ssl/bulk-operations' }
    ]
  },
  {
    key: 'uptime',
    title: 'Uptime Monitoring',
    icon: Clock,
    disabled: true,
    children: [
      { title: 'Status Overview', href: '/uptime/status' },
      { title: 'Response Times', href: '/uptime/performance' },
      { title: 'Incidents', href: '/uptime/incidents' }
    ]
  },
  {
    key: 'analytics',
    title: 'Analytics',
    icon: TrendingUp,
    href: '/analytics',
    description: 'Performance insights & trends',
    disabled: true
  },
  {
    key: 'reports',
    title: 'Reports',
    icon: BarChart3,
    disabled: true,
    children: [
      { title: 'SSL Reports', href: '/reports/ssl' },
      { title: 'Uptime Reports', href: '/reports/uptime' },
      { title: 'Performance', href: '/reports/performance' }
    ]
  },
  {
    key: 'alerts',
    title: 'Alerts',
    icon: AlertTriangle,
    children: [
      { title: 'Alert Rules', href: '/alerts' },
      { title: 'Notifications', href: '/alerts/notifications' },
      { title: 'History', href: '/alerts/history' }
    ]
  },
  {
    key: 'team',
    title: 'Team',
    icon: Users,
    href: '/settings/team',
    description: 'Team management'
  }
]

// Bottom menu items (for sidebar)
export const bottomMenuItems: MenuItem[] = [
  {
    key: 'settings',
    title: 'Settings',
    icon: Settings,
    href: '/settings',
    description: 'Account & preferences'
  },
  {
    key: 'help',
    title: 'Help',
    icon: HelpCircle,
    href: '/help',
    description: 'Documentation & support',
    disabled: true
  }
]

// For horizontal menu, we might want to exclude certain items or combine them differently
export const horizontalMenuItems: MenuItem[] = mainMenuItems

// Navigation utilities
export function isActiveRoute(href: string): boolean {
  return window.location.pathname === href
}

export function findActiveMenuItem(pathname: string): MenuItem | null {
  // Check main menu items
  for (const item of mainMenuItems) {
    if (item.href === pathname) {
      return item
    }
    // Check children
    if (item.children) {
      for (const child of item.children) {
        if (child.href === pathname) {
          return item
        }
      }
    }
  }

  // Check bottom menu items
  for (const item of bottomMenuItems) {
    if (item.href === pathname) {
      return item
    }
  }

  return null
}