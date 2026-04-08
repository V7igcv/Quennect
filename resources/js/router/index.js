import { createRouter, createWebHistory } from 'vue-router'
import { authService } from '../services/auth'

// Import layouts
import KioskLayout from '../layouts/KioskLayout.vue'
import FrontdeskLayout from '../layouts/FrontdeskLayout.vue'
import SuperadminLayout from '../layouts/SuperadminLayout.vue'

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
        component: () => import('../pages/frontdesk/FrontDeskAnalytics.vue')
      },
      {
        path: 'csm-analytics',
        name: 'frontdesk.csm-analytics',
        component: () => import('../pages/frontdesk/CSMAnalytics.vue')
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
        component: () => import('../pages/superadmin/AdminAnalytics.vue')
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
        return user?.role === 'SUPERADMIN' ? '/superadmin' : '/frontdesk'
      }
      return '/kiosk/welcome'
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
      return user?.role === 'SUPERADMIN' ? '/superadmin' : '/frontdesk'
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
    if (to.meta.role && user?.role !== to.meta.role) {
      // User doesn't have required role, redirect to their appropriate dashboard
      return user?.role === 'SUPERADMIN' ? '/superadmin' : '/frontdesk'
    }
  }
  
  // Allow navigation
  return true
})

export default router