'use client'
import toast from 'react-hot-toast'

export type ToastType = 'success' | 'error'

export interface ToastOptions {
  icon?: string
  position?: any
}

export const provideUtility = () => ({
  showToast: (
    icon: string,
    message: string,
    position: any,
    type: ToastType = 'success'
  ) => {
    const options: ToastOptions = {
      icon: icon || undefined,
      position: position,
    }

    if (type === 'success') {
      toast.success(message, options)
    } else if (type === 'error') {
      toast.error(message, options)
    }
  },
})

export default provideUtility
