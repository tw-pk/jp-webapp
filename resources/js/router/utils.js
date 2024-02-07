import User from "@/apis/user"

/**
 * Return if user is logged in
 * This is completely up to you and how you want to store the token in your frontend application
 * e.g. If you are using cookies to store the application please update this function
 */
export const isUserLoggedIn = () => !!(localStorage.getItem('userData') && localStorage.getItem('accessToken'))
export const isUserSubscribed = () => {
  return User.isUserSubscribed()
    .then(res => {
      return !!res.data.status
    })
}
