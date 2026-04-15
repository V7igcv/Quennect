import { createRouter, createWebHistory } from 'vue-router'
import { authService } from '../services/auth'

// Import layouts
import KioskLayout from '../layouts/KioskLayout.vue'
import FrontdeskLayout from '../layouts/FrontdeskLayout.vue'
import SuperadminLayout from '../layouts/SuperadminLayout.vue'
import CityMayorLayout from '../layouts/CityMayorLayout.vue'

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
        path: 'intro',           // Intro video page
        name: 'kiosk.intro',
        component: () => import('../pages/kiosk/IntroVideoPage.vue')
      },
      {
        path: 'welcome',         // Welcome page after intro
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
        path: 'csm-analytics',
        name: 'city-mayor.csm-analytics',
        component: () => import('../pages/shared/CSMAnalytics.vue')
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
      return '/kiosk/intro'     // Redirect to intro video page for non-authenticated users
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation Guard - Updated (no next() callback)
router.beforeEach((to, from) => {
  const isAuthenticated = authService.isAuthenticated()
  const user = authService.getCurrentUser()
  
  // Debug logging
  console.log('Navigation to:', to.path)
  console.log('Auth status:', isAuthenticated)
  console.log('User:', user)
  
  // Handle routes that require guest access (login page)
  if (to.meta.requiresGuest) {
    if (isAuthenticated) {
      // If already logged in, redirect to appropriate dashboard
      return getHomePathForRole(user?.role)
    }
    return true // Allow access
  }
  
  // Handle routes that require authentication
  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      // Not logged in, redirect to login
      return '/login'
    }
    
    // Check role-based access
    const requiredRoles = Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role].filter(Boolean)
    if (requiredRoles.length && !requiredRoles.includes(user?.role)) {
      // User doesn't have required role, redirect to their appropriate dashboard
      return getHomePathForRole(user?.role)
    }
  }
  
  // Allow navigation
  return true
})

export default router