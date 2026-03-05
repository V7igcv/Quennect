// import { createRouter, createWebHistory } from 'vue-router'

// // Import layouts
// import KioskLayout from '../layouts/KioskLayout.vue'
// import FrontdeskLayout from '../layouts/FrontdeskLayout.vue'
// import SuperadminLayout from '../layouts/SuperadminLayout.vue'
// import MonitorLayout from '../layouts/MonitorLayout.vue'

// // Import pages
// import Login from '../pages/auth/Login.vue'

// // Kiosk pages
// import KioskWelcome from '../pages/kiosk/Welcome.vue'
// import OfficeSelection from '../pages/kiosk/OfficeSelection.vue'
// import ServiceSelection from '../pages/kiosk/ServiceSelection.vue'
// import PersonalDetails from '../pages/kiosk/PersonalDetails.vue'
// import PrintPage from '../pages/kiosk/PrintPage.vue'
// import ClosingPage from '../pages/kiosk/ClosingPage.vue'

// // Frontdesk pages
// import FrontdeskDashboard from '../pages/frontdesk/Dashboard.vue'
// import FrontDeskAnalytics from '../pages/frontdesk/FrontDeskAnalytics.vue'

// // Superadmin pages
// import AdminAnalytics from '../pages/superadmin/AdminAnalytics.vue'
// import OfficeManagement from '../pages/superadmin/OfficeManagement.vue'
// import UserManagement from '../pages/superadmin/UserManagement.vue'

// // Monitor pages
// import MonitorDisplay from '../pages/monitor/Display.vue'

// const routes = [
//   {
//     path: '/login',
//     name: 'login',
//     component: Login,
//     meta: { layout: 'guest' }
//   },
  
//   // Kiosk routes (public)
//   {
//     path: '/kiosk',
//     component: KioskLayout,
//     children: [
//       { path: '', name: 'kiosk.welcome', component: KioskWelcome },
//       { path: 'offices', name: 'kiosk.offices', component: OfficeSelection },
//       { path: 'offices/:officeId/services', name: 'kiosk.services', component: ServiceSelection },
//       { path: 'details', name: 'kiosk.details', component: PersonalDetails },
//       { path: 'print', name: 'kiosk.print', component: PrintPage },
//       { path: 'closing', name: 'kiosk.closing', component: ClosingPage }
//     ]
//   },
  
//   // Frontdesk routes (protected)
//   {
//     path: '/frontdesk',
//     component: FrontdeskLayout,
//     meta: { requiresAuth: true, role: 'FRONTDESK' },
//     children: [
//       { path: '', name: 'frontdesk.dashboard', component: FrontdeskDashboard },
//       { path: 'analytics', name: 'frontdesk.analytics', component: FrontDeskAnalytics }
//     ]
//   },
  
//   // Superadmin routes (protected)
//   {
//     path: '/superadmin',
//     component: SuperadminLayout,
//     meta: { requiresAuth: true, role: 'SUPERADMIN' },
//     children: [
//       { path: '', name: 'superadmin.dashboard', component: AdminAnalytics },
//       { path: 'offices', name: 'superadmin.offices', component: OfficeManagement },
//       { path: 'users', name: 'superadmin.users', component: UserManagement }
//     ]
//   },
  
//   // Monitor routes (public, no layout needed as it's built into component)
//   {
//     path: '/monitor/:officeId',
//     name: 'monitor.display',
//     component: MonitorDisplay
//   },
  
//   // Redirect root based on auth
//   {
//     path: '/',
//     redirect: to => {
//       const token = localStorage.getItem('token')
//       if (token) {
//         const user = JSON.parse(localStorage.getItem('user') || '{}')
//         return user.role === 'SUPERADMIN' ? '/superadmin' : '/frontdesk'
//       }
//       return '/kiosk'
//     }
//   }
// ]

// const router = createRouter({
//   history: createWebHistory(),
//   routes
// })

// // Navigation guard
// router.beforeEach((to, from, next) => {
//   const token = localStorage.getItem('token')
//   const user = JSON.parse(localStorage.getItem('user') || '{}')
  
//   // Check if route requires auth
//   if (to.meta.requiresAuth) {
//     if (!token) {
//       return next('/login')
//     }
    
//     // Check role if specified
//     if (to.meta.role && user.role !== to.meta.role) {
//       return next('/')
//     }
//   }
  
//   // Redirect to appropriate dashboard if already logged in and trying to access login
//   if (to.path === '/login' && token) {
//     return next(user.role === 'SUPERADMIN' ? '/superadmin' : '/frontdesk')
//   }
  
//   next()
// })

// export default router



////////////////////////////////////////////////////////////////////

import { createRouter, createWebHistory } from 'vue-router'

// Import layouts
import KioskLayout from '../layouts/KioskLayout.vue'
import FrontdeskLayout from '../layouts/FrontdeskLayout.vue'
import SuperadminLayout from '../layouts/SuperadminLayout.vue'
import MonitorLayout from '../layouts/MonitorLayout.vue'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/auth/Login.vue')
    },
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
    {
        path: '/frontdesk',
        component: FrontdeskLayout,
        children: [
            {
                path: 'dashboard',
                name: 'frontdesk.dashboard',
                component: () => import('../pages/frontdesk/Dashboard.vue')
            },
            {
                path: 'analytics',
                name: 'frontdesk.analytics',
                component: () => import('../pages/frontdesk/FrontDeskAnalytics.vue')
            }
        ]
    },
    {
        path: '/superadmin',
        component: SuperadminLayout,
        children: [
            {
                path: 'analytics',
                name: 'superadmin.analytics',
                component: () => import('../pages/superadmin/AdminAnalytics.vue')
            },
            {
                path: 'offices',
                name: 'superadmin.offices',
                component: () => import('../pages/superadmin/OfficeManagement.vue')
            },
            {
                path: 'users',
                name: 'superadmin.users',
                component: () => import('../pages/superadmin/UserManagement.vue')
            }
        ]
    },
    {
        path: '/monitor',
        component: MonitorLayout,
        children: [
            {
                path: 'display',
                name: 'monitor.display',
                component: () => import('../pages/monitor/Display.vue')
            }
        ]
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router




// import { createRouter, createWebHistory } from 'vue-router'

// // Simple routes for testing
// const routes = [
//   {
//     path: '/',
//     name: 'home',
//     component: () => import('../pages/kiosk/Welcome.vue')
//   },
//   {
//     path: '/kiosk/welcome',
//     name: 'kiosk.welcome',
//     component: () => import('../pages/kiosk/Welcome.vue')
//   }
// ]

// const router = createRouter({
//   history: createWebHistory(),
//   routes
// })

// // Add navigation guard to debug
// router.beforeEach((to, from, next) => {
//   console.log('Navigating to:', to.path)
//   next()
// })

// export default router