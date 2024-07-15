import contact from "@/apis/contact"
import twoFactor from "@/apis/twoFactor"
import User from "@/apis/user"
import axiosIns from "@axios"
import { canNavigate } from '@layouts/plugins/casl'
import { themeConfig } from "@themeConfig"
import NProgress from 'nprogress'
import "nprogress/nprogress.css"
import { setupLayouts } from 'virtual:generated-layouts'
import { createRouter, createWebHistory } from 'vue-router'
import routes from '~pages'
import { isUserLoggedIn, isUserSubscribed } from './utils'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ℹ️ We are redirecting to different pages based on role.
    // NOTE: Role is just for UI purposes. ACL is based on abilities.
    {
      path: '/',
      redirect: to => {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}')
        const userRole = (userData && userData.role) ? userData.role : null
        if (userRole === 'Admin' || "Member")
          return { name: 'dashboards' }

        return { name: 'login', query: to.query }
      },
    },
    {
      path: '/verify-email',
      redirect: to => {
        return { name: 'verify-email' }
      },
    },
    
    {
      path: '/verify-phone',
      redirect: to => {
        return { name: 'verify-phone' }
      },
    },

    {
      path: '/select/available-numbers',
      redirect: to => {
        return { name: 'available-numbers-select', query: to.query }
      },
    },
    {
      path: '/pages/user-profile',
      redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
    },
    {
      path: '/pages/account-settings',
      redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
    },
    {
      path: '/pages/teams-manage-members',
      redirect: () => ({ name: 'pages-teams-manage-members' }),
    },
    {
      path: '/pages/teams-manage-teams',
      redirect: () => ({ name: 'pages-teams-manage-groups' }),
    },
    {
      path: '/pages/phone-numbers',
      redirect: () => ({ name: 'pages-phone-numbers' }),
    },
    {
      path: '/pages/contact',
      redirect: () => ({ name: 'pages-contact' }),
    },
    {
      path: '/pages/contact-details/:id',
      name: 'id',
      beforeEnter: (to, from, next) => {
        // Check if the link is valid before allowing access to the component
        if (to.params.id) {
          contact.findContact({
            contact_id: to.params.id,
          })
            .then(res => {
              if (res.data.status) {
                return next({
                  name: 'pages-contact-details-id',
                  params: { id: to.params.id },
                  query: to.query,
                })
              } else {
                next({ name: 'pages-misc-not-found' })
              }
            })
            .catch(error => {
              next({ name: 'pages-misc-not-found' })
            })
        } else {
          // Missing query parameters, redirect to another route
          next({ name: 'pages-misc-not-found' })
        }

      },
    },
    {
      path: '/pages/recent-calls',
      redirect: () => ({ name: 'pages-recent-calls' }),
    },
    {
      path: '/pages/setting/roles',
      redirect: () => ({ name: 'pages-setting-roles' }),
    },
    {
      path: '/pages/setting/list',
      redirect: () => ({ name: 'pages-setting-list' }),
    },
    {
      path: '/pages/top-up-credit',
      redirect: () => ({ name: 'pages-top-up-credit' }),
    },
    {
      path: '/pages/credit-payment',
      redirect: () => ({ name: 'pages-credit-payment' }),
    },
    {
      path: '/members/invite',
      redirect: () => ({ name: 'team-members-invite' }),
    },
    {
      path: '/member',
      redirect: to => {
        return { name: 'register' }
      },
    },
    {
      path: '/credit/payment',
      redirect: to => {
        return { name: 'pages-top-up-credit-payment' }
      },
    },
    {
      path: '/messages/inbox',
      redirect: () => ({ name: 'messages-inbox' }),
    },
    {
      path: '/2fa-verify',
      redirect: to => {
        if (to.redirectedFrom && to.redirectedFrom.path !== '/2fa-verify') {
          return { name: '2fa-verify', query: { to: to.redirectedFrom.fullPath } }
        } else {
          return { name: '2fa-verify', query: { to: "/" } }
        }
      },
    },
    {
      path: '/reset-password/:token',
      name: 'token',
      meta: {
        layout: 'blank',
        subject: 'Auth',
        action: 'read',
      },
      props: to => ({
        token: to.params.token,
      }),
      beforeEnter: (to, from, next) => {
        // Check if the link is valid before allowing access to the component
        if (to.query.email && to.params.token) {

          User.verifyResetLink({
            email: to.query.email,
            token: to.params.token,
          }).then(res => {
            next({ name: 'reset-password-token', params: { token: to.params.token }, query: to.query })
          })
            .catch(error => {
              // Link is invalid
              next({ name: 'forgot-password', params: { token: to.params.token } }) // Redirect to another route, e.g., home
            })

        } else {
          // Missing query parameters, redirect to another route
          next()
        }

      },
    },
    ...setupLayouts(routes),
  ],

})


// Docs: https://router.vuejs.org/guide/advanced/navigation-guards.html#global-before-guards

/**
 * Checks if the user is logged in and redirects to the login page if not.
 * @param {Object} to - The target route object being navigated to.
 * @param {Object} from - The current route object being navigated away from.
 * @param {Function} next - A callback function to resolve the navigation.
 * @returns {Promise} A promise that resolves the navigation.
 */
const handleAuthentication = async (to, from, next) => {
  const isLoggedIn = isUserLoggedIn()

  if (!isLoggedIn) {
    return Promise.resolve(next({ name: 'login', query: { to: to.name !== 'index' ? to.fullPath : undefined } }))
  }
}

const handleSubscription = async (to, from, next) => {
  const isSubscribed = await isUserSubscribed()
  const userData = await User.auth()
  if (
    !isSubscribed &&
        userData.data.email_verified &&
        userData.data.numbers &&
        userData.data.invitations
  ) {
    return next({ name: 'purchase-summary' })
  }

}

const handle2FA = async (to, from, next) => {
  try {
    const [sessionIsVerified, isSubscribed, userData] = await Promise.all([
      twoFactor.verifySession(),
      isUserSubscribed(),
      User.auth(),
    ])


    if (
      !sessionIsVerified.data.status &&
            isSubscribed &&
            userData.data.email_verified &&
            userData.data.numbers &&
            userData.data.invitations &&
            to.name !== '2fa-verify'
    ) {
      return to.redirectedFrom && to.redirectedFrom.path !== "2fa-verify" ? next({
        name: '2fa-verify',
        query: { to: to.redirectedFrom.path },
      }) : next({ name: '2fa-verify', query: { to: "/" } })
    }

    if (
      sessionIsVerified.data.status &&
            isSubscribed &&
            userData.data.email_verified &&
            userData.data.numbers &&
            userData.data.invitations &&
            to.name === '2fa-verify'
    ) {
      return next("/")
    }
  } catch (error) {
  }
}

const createStripeSession = () => {
  axiosIns.post('/api/auth/create-subscription-checkout')
    .then(res => {
      if (!res.data) {
        return
      }
      if (res.data.checkout_url) {
        window.location.href = res.data.checkout_url
      }
    })
    .catch(error => {
      console.log(error)
    })
}

const capitalizeFirstLetter = string => string.charAt(0).toUpperCase() + string.slice(1)

router.beforeEach(async (to, from, next) => {
  document.title = to.meta.title || capitalizeFirstLetter(to.name)

  NProgress.configure({ showSpinner: false })
  NProgress.start()

  const isLoggedIn = isUserLoggedIn()
  if (isLoggedIn && canNavigate(to)) {
    const [sessionIsVerified] = await Promise.all([
      twoFactor.verifySession(),
    ])

    const isSubscribed = await isUserSubscribed()

    // Check if user is logged in and can navigate to the destination route
    const userData = await User.auth()
    
    console.log('userData')
    console.log(isSubscribed)

    if(userData.data.invitations && !isSubscribed){
      createStripeSession()
    }

    const userRole = await User.isRole()
  
    if(to.name === 'dialer'){
      themeConfig.app.theme.value = 'light'
      localStorage.setItem('JotPhone-theme', 'light')
    }else{
      themeConfig.app.theme.value = 'system'
      localStorage.setItem('JotPhone-theme', 'system')
    }

    if(userRole.data.isAdmin) {
      if (!sessionIsVerified.data.status && isSubscribed && userData.data.email_verified && userData.data.numbers && userData.data.invitations && to.name !== '2fa-verify') {
        return to.redirectedFrom && to.redirectedFrom.path !== "2fa-verify" ? next({
          name: '2fa-verify',
          query: { to: to.redirectedFrom.path },
        }) : next({ name: '2fa-verify', query: { to: "/" } })
      }

      if (sessionIsVerified.data.status && isSubscribed && userData.data.email_verified && userData.data.numbers && userData.data.invitations && to.name === '2fa-verify') {
        return next("/")
      }

      if (!userData.data.email_verified && to.name !== 'verify-email') {
        return next({ name: 'verify-email' })
      } else if (
        userData.data.email_verified &&
                !userData.data.numbers &&
                to.name !== 'available-numbers-select'
      ) {
        return next({ name: "available-numbers-select" })
      } 

      // else if (
      //   userData.data.email_verified &&
      //           userData.data.numbers &&
      //           !userData.data.invitations &&
      //           isSubscribed &&
      //           to.name !== 'team-members-invite'
      // ) {
      //   return next({ name: "team-members-invite" })
      // } 

      else if (
        userData.data.email_verified &&
                userData.data.numbers &&
                !userData.data.invitations &&
                !isSubscribed &&
                to.name !== 'team-members-invite'
      ) {
        return next({ name: "team-members-invite" })
      } 
      else if (
        to.name !== 'purchase-summary' &&
                userData.data.email_verified &&
                userData.data.numbers &&
                userData.data.invitations &&
                !isSubscribed
      ) {
        //return next({ name: "purchase-summary" })
      } else if (
        userData.data.email_verified &&
                userData.data.numbers &&
                userData.data.invitations &&
                isSubscribed &&
                to.name === 'team-members-invite'
      ) {
        return next({ name: "dashboards" })
      } else if (
        to.name === 'purchase-summary' &&
                userData.data.email_verified &&
                userData.data.numbers &&
                userData.data.invitations &&
                isSubscribed
      ) {
        return next({ name: "dashboards" })
      } else if (
        userData.data.email_verified &&
                userData.data.numbers &&
                isSubscribed &&
                to.name === 'available-numbers-select'
      ) {
        return next("/")
      } else if (
        userData.data.email_verified &&
                to.name === 'verify-email'
      ) {
        return next("/")
      }
    }

    if (userRole.data.isMember) {
      
      if(!sessionIsVerified.data.status && userData.data.email_verified && to.name !== '2fa-verify') {
        return to.redirectedFrom && to.redirectedFrom.path !== "2fa-verify" ? next({
          name: '2fa-verify',
          query: { to: to.redirectedFrom.path },
        }) : next({ name: '2fa-verify', query: { to: "/" } })
      }

      if(sessionIsVerified.data.status && userData.data.email_verified && to.name === '2fa-verify') {
        return next("/")
      }

      if(!userData.data.email_verified && to.name !== 'verify-email'){
        return next({ name: 'verify-email' })
      }

      if(userData.data.email_verified && to.name === 'verify-email'){
        return next("/")
      }
      
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


router.afterEach((to, from, next) => {
  NProgress.done()
})


export default router
