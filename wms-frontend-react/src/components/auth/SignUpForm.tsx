import { useState } from "react";
import { Link } from "react-router";
import { EyeCloseIcon, EyeIcon } from "../../icons";
import Label from "../form/Label";
import Input from "../form/input/InputField";
import Checkbox from "../form/input/Checkbox";
import Button from "../ui/button/Button"
import http from '../../lib/http'
import provideAuthValidations from '../../utils/validation/auth/AuthValidation'
import Spinner from '../ui/loading/spinner'
import provideUtility from '../../utils/toast'
// import { useDispatch } from "react-redux";
import { useNavigate } from 'react-router-dom'

type Country = {
  name: string
  code: string
  dialCode: string
  flagUrl: string
}

interface Errors {
  dial_code?:string
  phone_number?:string
  name?:string
  email?: string
  password?: string
}

export default function SignUpForm() {

  const { registerValidation } = provideAuthValidations()
  const { showToast } = provideUtility()
  // const dispatch = useDispatch()
  const [showPassword, setShowPassword] = useState(false);
  const [isChecked, setIsChecked] = useState(false);
  const [isLoading, setIsLoading] = useState(false)
  const navigate = useNavigate()
  const [errors, setErrors] = useState<Errors>({})
  const [registerData, setRegisterData] = useState({
    dial_code:'+95',
    phone_number: '',
    name: '',
    email: '',
    password: ''
  })

  const countries: Country[] = [
    {
      name: 'Myanmar',
      code: 'mm',
      dialCode: '+95',
      flagUrl: 'https://flagcdn.com/w40/mm.png',
    },
    {
      name: 'Thailand',
      code: 'th',
      dialCode: '+66',
      flagUrl: 'https://flagcdn.com/w40/th.png',
    },
  ]
  const [selectedCountry, setSelectedCountry] = useState<Country>(countries[0])

  const handleCountryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const dialCode = e.target.value
    const country = countries.find((c) => c.dialCode === dialCode)
    if (country) {
      setSelectedCountry(country)
      setRegisterData((prev) => ({
        ...prev,
        dial_code: country.dialCode,
      }))
    }
  }

  const handleRemove = (field: string) => {
    setErrors((prev) => ({
      ...prev,
      [field]: null,
    }))
  }

 const handleSubmit = async () => {

   setIsLoading(true)
  const errors = registerValidation(
    registerData.phone_number,
    registerData.name,
    registerData.email,
    registerData.password
  )

  if (Object.keys(errors).length === 0) {
    setErrors({})
    try {
      const response = await http.register('/register', registerData)
      if(response.success == true){
         showToast('', 'Register successful', 'top-right', 'success')
         navigate('/signin')
      }else{
        if (response?.status === 422) {
          const apiErrors: Errors = response.errors
          setErrors(apiErrors)
        }else{
          showToast(
            '',
            'Something went wrong!.',
            'top-right',
            'error'
          )
        }
      }
    } catch (err) {
      showToast(
        '',
        'Register failed!',
        'top-right',
        'error'
      )
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  } else {
    showToast('', 'Something went wrong!', 'top-right', 'error')
    setErrors(errors)
    setIsLoading(false)
  }
 }

  return (
    <div className="flex flex-col flex-1 w-full overflow-y-auto lg:w-1/2 no-scrollbar">
      <div className="w-full max-w-md mx-auto mb-5 sm:pt-10">
        {/* <Link
          to="/"
          className="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
        >
          <ChevronLeftIcon className="size-5" />
          Back to dashboard
        </Link> */}
      </div>
      <div className="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
        <div>
          <div className="mb-4 sm:mb-1">
            <h1 className="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
              Sign Up
            </h1>
            {/* <p className="text-sm text-gray-500 dark:text-gray-400">
              Enter your email and password to sign up!
            </p> */}
          </div>
          <div>
            <div className="flex justify-center align-items-center sm:grid-cols-2 sm:gap-5">
              {/* <button className="inline-flex items-center justify-center gap-3 py-3 text-sm font-normal text-gray-700 transition-colors bg-gray-100 rounded-lg px-7 hover:bg-gray-200 hover:text-gray-800 dark:bg-white/5 dark:text-white/90 dark:hover:bg-white/10">
                <svg
                  width="20"
                  height="20"
                  viewBox="0 0 20 20"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M18.7511 10.1944C18.7511 9.47495 18.6915 8.94995 18.5626 8.40552H10.1797V11.6527H15.1003C15.0011 12.4597 14.4654 13.675 13.2749 14.4916L13.2582 14.6003L15.9087 16.6126L16.0924 16.6305C17.7788 15.1041 18.7511 12.8583 18.7511 10.1944Z"
                    fill="#4285F4"
                  />
                  <path
                    d="M10.1788 18.75C12.5895 18.75 14.6133 17.9722 16.0915 16.6305L13.274 14.4916C12.5201 15.0068 11.5081 15.3666 10.1788 15.3666C7.81773 15.3666 5.81379 13.8402 5.09944 11.7305L4.99473 11.7392L2.23868 13.8295L2.20264 13.9277C3.67087 16.786 6.68674 18.75 10.1788 18.75Z"
                    fill="#34A853"
                  />
                  <path
                    d="M5.10014 11.7305C4.91165 11.186 4.80257 10.6027 4.80257 9.99992C4.80257 9.3971 4.91165 8.81379 5.09022 8.26935L5.08523 8.1534L2.29464 6.02954L2.20333 6.0721C1.5982 7.25823 1.25098 8.5902 1.25098 9.99992C1.25098 11.4096 1.5982 12.7415 2.20333 13.9277L5.10014 11.7305Z"
                    fill="#FBBC05"
                  />
                  <path
                    d="M10.1789 4.63331C11.8554 4.63331 12.9864 5.34303 13.6312 5.93612L16.1511 3.525C14.6035 2.11528 12.5895 1.25 10.1789 1.25C6.68676 1.25 3.67088 3.21387 2.20264 6.07218L5.08953 8.26943C5.81381 6.15972 7.81776 4.63331 10.1789 4.63331Z"
                    fill="#EB4335"
                  />
                </svg>
                Sign up with Google
              </button> */}
              {/* <button className="inline-flex items-center justify-center gap-3 py-3 text-sm font-normal text-gray-700 transition-colors bg-gray-100 rounded-lg px-7 hover:bg-gray-200 hover:text-gray-800 dark:bg-white/5 dark:text-white/90 dark:hover:bg-white/10">
                <svg
                  width="21"
                  className="fill-current"
                  height="20"
                  viewBox="0 0 21 20"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path d="M15.6705 1.875H18.4272L12.4047 8.75833L19.4897 18.125H13.9422L9.59717 12.4442L4.62554 18.125H1.86721L8.30887 10.7625L1.51221 1.875H7.20054L11.128 7.0675L15.6705 1.875ZM14.703 16.475H16.2305L6.37054 3.43833H4.73137L14.703 16.475Z" />
                </svg>
                Sign up with X
              </button> */}
            </div>
            <div className="relative py-3 sm:py-5">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-200 dark:border-gray-800"></div>
              </div>
              {/* <div className="relative flex justify-center text-sm">
                <span className="p-2 text-gray-400 bg-white dark:bg-gray-900 sm:px-5 sm:py-2">
                  Or
                </span>
              </div> */}
            </div>
            {/* <form onSubmit={handleSubmit}> */}
            <div className="space-y-5">
              <div className="">
                <Label className="block mb-2 text-sm text-gray-700">
                  Phone Number<span className="text-error-500">*</span>
                </Label>
                <div
                  className={`flex items-center overflow-hidden rounded-md w-full max-w-md p-1
                  ${
                    errors.phone_number
                      ? 'border border-error-500 focus-within:border-error-300 focus-within:ring-error-500/20 dark:text-error-400 dark:border-error-500 dark:focus-within:border-error-800'
                      : 'border border-gray-300 focus-within:ring-2 focus-within:ring-blue-200'
                  }
                `}
                >
                  <div className="flex items-center px-2">
                    <img
                      src={selectedCountry.flagUrl}
                      alt={selectedCountry.name}
                      className="w-5 h-5 mr-1"
                    />
                    <select
                      id="countryCode"
                      className="bg-white text-sm focus:outline-none"
                      value={selectedCountry.dialCode}
                      onChange={handleCountryChange}
                    >
                      {countries.map((country) => (
                        <option key={country.code} value={country.dialCode}>
                          {country.dialCode}
                        </option>
                      ))}
                    </select>
                  </div>
                  <input
                    type="tel"
                    id="phone"
                    placeholder="Enter phone number"
                    className="w-full px-4 py-2 text-base focus:outline-none"
                    value={registerData.phone_number}
                    onChange={(e) => {
                      const onlyNumbers = e.target.value.replace(/\D/g, '')
                      setRegisterData((prev) => ({
                        ...prev,
                        phone_number: onlyNumbers,
                      }))
                    }}
                    onKeyUp={() => handleRemove('phone_number')}
                  />
                </div>
                {errors.phone_number !== undefined &&
                errors.phone_number?.length !== 0 ? (
                  <p className="mt-1.5 text-xs text-error-500">
                    {errors?.phone_number}
                  </p>
                ) : (
                  ''
                )}
              </div>
              <div className="">
                {/* <!-- First Name --> */}
                <div className="sm:col-span-1">
                  <Label>
                    Name<span className="text-error-500">*</span>
                  </Label>
                  <Input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your first name"
                    value={registerData.name}
                    onChange={(e) =>
                      setRegisterData((prev) => ({
                        ...prev,
                        name: e.target.value,
                      }))
                    }
                    onKeyUp={() => handleRemove('name')}
                    error={
                      errors.name ? true : false
                    }
                    hint={errors.name && errors.name?.[0]}
                  />
                </div>
                {/* <!-- Last Name --> */}
                {/* <div className="sm:col-span-1">
                    <Label>
                      Last Name<span className="text-error-500">*</span>
                    </Label>
                    <Input
                      type="text"
                      id="lname"
                      name="lname"
                      placeholder="Enter your last name"
                    />
                  </div> */}
              </div>
              {/* <!-- Email --> */}
              <div>
                <Label>
                  Email<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="email"
                  id="email"
                  name="email"
                  placeholder="Enter your email"
                  value={registerData.email}
                  onChange={(e) =>
                    setRegisterData((prev) => ({
                      ...prev,
                      email: e.target.value,
                    }))
                  }
                  onKeyUp={() => handleRemove('email')}
                  error={
                    errors.email ? true : false
                  }
                  hint={errors.email}
                />
              </div>
              {/* <!-- Password --> */}
              <div>
                <Label>
                  Password<span className="text-error-500">*</span>
                </Label>
                <div className="relative">
                  <Input
                    placeholder="Enter your password"
                    type={showPassword ? 'text' : 'password'}
                    value={registerData.password}
                    onChange={(e) =>
                      setRegisterData((prev) => ({
                        ...prev,
                        password: e.target.value,
                      }))
                    }
                    onKeyUp={() => handleRemove('password')}
                    error={
                      errors.password? true : false
                    }
                    hint={errors.password}
                  />
                  <span
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2"
                  >
                    {showPassword ? (
                      <EyeIcon className="fill-gray-500 dark:fill-gray-400 size-5" />
                    ) : (
                      <EyeCloseIcon className="fill-gray-500 dark:fill-gray-400 size-5" />
                    )}
                  </span>
                </div>
              </div>
              {/* <!-- Checkbox --> */}
              <div className="flex items-center gap-3 ">
                <Checkbox
                  className="w-5 h-5"
                  checked={isChecked}
                  onChange={() => setIsChecked(!isChecked)}
                />
                <p className="inline-block font-normal text-gray-500 dark:text-gray-400">
                  By creating an account means you agree to the{' '}
                  <span className="text-gray-800 dark:text-white/90">
                    Terms and Conditions,
                  </span>{' '}
                  and our{' '}
                  <span className="text-gray-800 dark:text-white">
                    Privacy Policy
                  </span>
                </p>
              </div>
              {/* <!-- Button --> */}
              <div>
                <Button
                  variant="primary"
                  size="sm"
                  className="flex items-center justify-center w-full px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600"
                  startIcon={isLoading && <Spinner size={4} />}
                  onClick={handleSubmit}
                  disabled={!isChecked }
                >
                  Sign Up
                </Button>
              </div>
            </div>
            {/* </form> */}

            <div className="mt-5">
              <p className="text-sm font-normal text-center text-gray-700 dark:text-gray-400 sm:text-start">
                Already have an account? {''}
                <Link
                  to="/signin"
                  className="text-brand-500 hover:text-brand-600 dark:text-brand-400"
                >
                  Sign In
                </Link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
