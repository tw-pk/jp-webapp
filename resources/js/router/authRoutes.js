// Authenticated routes
const authRoutes = [
  {
    path: '/verify-email',
    redirect: to => {
      return { name: 'verify-email' }
    },
  },
  {
    path: '/select/available-numbers',
    redirect: to => {
      return { name: 'available-numbers-select', query: to.query }
    },
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/payment/selected-number',
    redirect: to => {
      return { name: 'selected-number-payment', query: to.query }
    },
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/pages/user-profile',
    redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/pages/account-settings',
    redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
    meta: {
      requiresAuth: true,
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
      console.log(to)
      if (to.query.email && to.params.token) {
  
        User.verifyResetLink({
          email: to.query.email,
          token: to.params.token,
        }).then(res => {
          console.log(res)
          next({ name: 'reset-password-token', params: { token: to.params.token }, query: to.query })
        })
          .catch(error => {
            // Link is invalid
            console.error('Invalid reset link:', error)
            next({ name: 'forgot-password', params: { token: to.params.token } }) // Redirect to another route, e.g., home
          })
  
      } else {
        // Missing query parameters, redirect to another route
        next()
      }
  
    },
  },
  
  // Add more authenticated routes here
]

export default authRoutes
