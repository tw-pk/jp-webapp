const nonAuthRoutes = [
  // Application-related routes
  {
    path: '/verify-email',
    redirect: { name: 'verify-email' },
  },

  // Add more non-authenticated routes here
]
  
export default nonAuthRoutes
