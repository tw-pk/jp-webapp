import User from "@/apis/user"
import { canNavigate } from '@layouts/plugins/casl'
import { isUserLoggedIn } from './utils'


export function applyNavigationGuards(router) {
// Docs: https://router.vuejs.org/guide/advanced/navigation-guards.html#global-before-guards
  router.beforeEach(async (to, from, next) => {
    const isLoggedIn = isUserLoggedIn()
  
    if (isLoggedIn && canNavigate(to)) {
      // Check if user is logged in and can navigate to the destination route
      const userData = await User.auth()
  
      const bypassRedirectionRoutes = ['available-numbers-select', 'reset-password-token', 'selected-number-payment']
      
  
      if (!userData.data.email_verified && to.name !== 'verify-email') {
        return next({ name: 'verify-email' })
      } else if (
        userData.data.email_verified &&
        !userData.data.numbers &&
        !bypassRedirectionRoutes.includes(to.name)
      ) {
        return next({ name: "available-numbers-select" })
      }
    }
  
    if (canNavigate(to)) {
      // Check if user can navigate to the destination route
      if (to.meta.redirectIfLoggedIn && isLoggedIn) {
        return next('/')
      }
    } else {
      // Handle not authorized access
      if (isLoggedIn) {
        return next({ name: 'not-authorized' })
      } else {
        return next({ name: 'login', query: { to: to.name !== 'index' ? to.fullPath : undefined } })
      }
    }

    next() // Call next() to proceed with the navigation
  })
}
