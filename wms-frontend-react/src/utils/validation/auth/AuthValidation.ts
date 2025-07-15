export const provideAuthValidations = () => ({
  registerValidation: (phone_number: string, name: string, email: string, password: string) => {
    const errors: Record<string, string[]> = {}
    if (phone_number === '') {
      errors.phone_number = ['Phone Number is required']
    }
    if (name === '') {
      errors.name = ['Name is required']
    }
    if (email === '') {
      errors.email = ['Email is required']
    }
    if (password === '') {
      errors.password = ['Password is required']
    }
    return errors
  },

  loginValidation: (email: string, password: string) => {
    const errors: Record<string, string[]> = {}
    if (email === '') {
      errors.email = ['Email is required']
    }
    if (password === '') {
      errors.password = ['Password is required']
    }
    return errors
  }
})

export default provideAuthValidations
