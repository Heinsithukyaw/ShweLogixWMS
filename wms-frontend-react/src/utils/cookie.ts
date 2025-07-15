import Cookies from 'js-cookie'

export const setAuthCookie = (name: string, value: string, days = 30) => {
  Cookies.set(name, value, {
    expires: days,
    secure: true,
    sameSite: 'strict',
    path: '/',
  })
}

export const getAuthCookie = (name: string): string | undefined => {
  return Cookies.get(name)
}

export const clearAuthCookie = (name: string) => {
  Cookies.remove(name, { path: '/' })
}
