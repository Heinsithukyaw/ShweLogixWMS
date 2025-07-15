import axios, { AxiosInstance, AxiosResponse } from 'axios'
import { setAuthCookie, getAuthCookie } from '../utils/cookie'

const baseURL = `${import.meta.env.VITE_API_BASE_URL}/api/admin/v1`

const http: AxiosInstance = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

const ensureToken = async () => {
  const token = await getAuthCookie('auth_token')
  console.log('Token from cookie:', token)

  if (token) {
    http.defaults.headers.common['Authorization'] = `Bearer ${token}`
    return token
  } else {
    throw new Error('Token not available')
  }
}

export const register = async (uri: string, data: any) => {
  try {
    const response: AxiosResponse<any> = await http.post(uri, data)
    const user = response.data
    console.log(user)
    
    return user
  } catch (error: any) {
    if (error.status === 422) {
      console.log('validation error in http')
      console.log(error.response?.data)
      return error.response?.data
    }
    throw error
  }
}

export const login = async (uri: string, data: any) => {
  try {
    const response: AxiosResponse<any> = await http.post(uri, data)
    const user = response.data
    console.log(user)
    if (user.data?.token) {
      setAuthCookie('auth_token', user.data.token)
    }
    return user
  } catch (error: any) {
    if (error.status === 422) {
      console.log('validation error in http')
      console.log(error.response?.data)
      return error.response?.data
    }
    throw error
  }
}

export const fetchDataWithToken = async (uri: string) => {
  try {
    await ensureToken()
    const response = await http.get(uri)
    return response
  } catch (error: any) {
    throw error.response?.data || error.message
  }
}

export const postDataWithToken = async (uri: string, data: any) => {
  try {
    await ensureToken()
    const response = await http.post(uri, data, {
      headers: {
        // Let Axios handle the multipart headers
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  } catch (error: any) {
    throw error.response
  }
}

export const putDataWithToken = async (uri: string, data: any) => {
  try {
    await ensureToken()
    const response = await http.put(uri, data)
    return response.data
  } catch (error: any) {
    throw error.response?.data || error
  }
}
// export const putDataWithToken = async (
//   uri: string,
//   data: any,
//   isMultipart = false
// ) => {
//   try {
//     await ensureToken()

//     const headers = isMultipart
//       ? { 'Content-Type': 'multipart/form-data' }
//       : undefined

//     const response = await http.put(uri, data, { headers })

//     return response.data
//   } catch (error: any) {
//     throw error.response?.data || error
//   }
// }


export const deleteDataWithToken = async (uri: string) => {
  try {
    await ensureToken()
    const response = await http.delete(uri)
    return response.data
  } catch (error: any) {
    throw error.response?.data || error
  }
}



export default {
  http,
  register,
  login,
  fetchDataWithToken,
  postDataWithToken,
  putDataWithToken,
  deleteDataWithToken,
}

