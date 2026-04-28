import { createRouter, createWebHistory } from 'vue-router'
import { authService } from '../services/auth'

// Import layouts
import KioskLayout from '../layouts/KioskLayout.vue'
import FrontdeskLayout from '../layouts/FrontdeskLayout.vue'
import SuperadminLayout from '../layouts/SuperadminLayout.vue'
import CityMayorLayout from '../layouts/CityMayorLayout.vue'
import CswdoLayout from '../layouts/CswdoLayout.vue'

const getHomePathForRole = (role) => {
  if (role === 'SUPERADMIN') return '/superadmin'
  if (role === 'CITY MAYOR') return '/city-mayor'
  return '/frontdesk'
}

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/auth/Login.vue'),
    meta: { requiresGuest: true }
  },
  
  // Kiosk routes (public - no auth required)
  {
    path: '/kiosk',
    component: KioskLayout,
    children: [
      {
        path: 'intro',
        name: 'kiosk.intro',
        component: () => import('../pages/kiosk/IntroVideoPage.vue')
      },
      {
        path: 'welcome',
        name: 'kiosk.welcome',
        component: () => import('../pages/kiosk/Welcome.vue')
      },
      {
        path: 'office-selection',
        name: 'kiosk.office',
        component: () => import('../pages/kiosk/OfficeSelection.vue')
      },
      {
        path: 'service-selection',
        name: 'kiosk.service',
        component: () => import('../pages/kiosk/ServiceSelection.vue')
      },
      {
        path: 'personal-details',
        name: 'kiosk.details',
        component: () => import('../pages/kiosk/PersonalDetails.vue')
      },
      {
        path: 'print',
        name: 'kiosk.print',
        component: () => import('../pages/kiosk/PrintPage.vue')
      },
      {
        path: 'closing',
        name: 'kiosk.closing',
        component: () => import('../pages/kiosk/ClosingPage.vue')
      }
    ]
  },
  
  // Frontdesk routes (protected - FRONTDESK only)
  {
    path: '/frontdesk',
    component: FrontdeskLayout,
    meta: { requiresAuth: true, role: 'OFFICE FRONTDESK' },
    children: [
      {
        path: '',
        name: 'frontdesk.dashboard',
        component: () => import('../pages/frontdesk/Dashboard.vue')
      },
      {
        path: 'analytics',
        name: 'frontdesk.analytics',
        component: () => import('../pages/shared/QueueAnalytics.vue')
      },
      {
        path: 'csm-analytics',
        name: 'frontdesk.csm-analytics',
        component: () => import('../pages/shared/CSMAnalytics.vue')
      },
      {
        path: 'internal-transactions',
        name: 'frontdesk.internal-transactions',
        component: () => import('../pages/frontdesk/InternalTransactions.vue')
      },
      {
        path: 'create',
        name: 'frontdesk.create',
        component: () => import('../pages/frontdesk/Create.vue')
      },
      {
        path: 'chat',
        name: 'frontdesk.chat',
        component: () => import('../pages/frontdesk/ChatModule.vue')
      },
      // ✅ NEW
      {
        path: 'backlog',
        name: 'frontdesk.backlog',
        component: () => import('../pages/frontdesk/Backlog.vue')
      }
    ]
  },
  
  // Superadmin routes (protected - SUPERADMIN only)
  {
    path: '/superadmin',
    component: SuperadminLayout,
    meta: { requiresAuth: true, role: 'SUPERADMIN' },
    children: [
      {
        path: '',
        name: 'superadmin.dashboard',
        component: () => import('../pages/shared/QueueAnalytics.vue')
      },
      {
        path: 'offices',
        name: 'superadmin.offices',
        component: () => import('../pages/superadmin/OfficeManagement.vue')
      },
      {
        path: 'offices/:id/services/:name',
        name: 'OfficeServices',
        component: () => import('../pages/superadmin/OfficeServices.vue')
      },
      {
        path: 'users',
        name: 'superadmin.users',
        component: () => import('../pages/superadmin/UserManagement.vue')
      }
    ]
  },

  // City Mayor routes (protected - CITY MAYOR only)
  {
    path: '/city-mayor',
    component: CityMayorLayout,
    meta: { requiresAuth: true, role: 'CITY MAYOR' },
    children: [
      {
        path: '',
        name: 'city-mayor.dashboard',
        component: () => import('../pages/shared/QueueAnalytics.vue')
      },
      {
        path: 'office-service-efficiency',
        name: 'city-mayor.office-service-efficiency',
        component: () => import('../pages/citymayor/OfficeServiceEfficiency.vue')
      }
    ]
  },

  // CSWDO Focal routes (protected - CSWDO FOCAL only)
  {
    path: '/cswdo-focal',
    component: CswdoLayout,
    meta: { requiresAuth: true, role: 'CSWDO FOCAL' },
    children: [
      {
        path: '',
        name: 'cswdo.aics-analytics',
        component: () => import('../pages/cswdo/AicsAnalytics.vue')
      }
    ]
  },
  
  // Monitor routes (public)
  {
    path: '/monitor/:officeId/display',
    name: 'monitor.display',
    component: () => import('../pages/monitor/Display.vue')
  },
  
  // Redirect root based on auth status
  {
    path: '/',
    redirect: () => {
      if (authService.isAuthenticated()) {
        const user = authService.getCurrentUser()
        return getHomePathForRole(user?.role)
      }
      return '/kiosk/intro'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation Guard
router.beforeEach((to, from) => {
  const isAuthenticated = authService.isAuthenticated()
  const user = authService.getCurrentUser()
  
  console.log('Navigation to:', to.path)
  console.log('Auth status:', isAuthenticated)
  console.log('User:', user)
  
  if (to.meta.requiresGuest) {
    if (isAuthenticated) {
      return getHomePathForRole(user?.role)
    }
    return true
  }
  
  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      return '/login'
    }
    
    const requiredRoles = Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role].filter(Boolean)
    if (requiredRoles.length && !requiredRoles.includes(user?.role)) {
      return getHomePathForRole(user?.role)
    }
  }
  
  return true
})

export default router