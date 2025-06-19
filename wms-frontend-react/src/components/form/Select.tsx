import { useState } from 'react'

interface SelectProps<T> {
  options: T[]
  labelKey?: keyof T
  valueKey?: keyof T
  placeholder?: string
  onChange: (value: any, selectedObject: T) => void
  className?: string
  defaultValue?: any
}

const Select = <T extends Record<string, any>>({
  options,
  labelKey = 'name', // default to "name"
  valueKey = 'id', // default to "id"
  placeholder = 'Select an option',
  onChange,
  className = '',
  defaultValue = '',
}: SelectProps<T>) => {
  const [selectedValue, setSelectedValue] = useState<any>(defaultValue)

  const handleChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const value = e.target.value
    const selectedObject = options.find(
      (opt) => String(opt[valueKey]) === value
    )
    setSelectedValue(value)
    if (selectedObject) {
      onChange(value, selectedObject)
    }
  }

  return (
    <select
      className={`h-11 w-full rounded-lg border px-4 py-2.5 text-sm ${className}`}
      value={selectedValue}
      onChange={handleChange}
    >
      <option value="" disabled>
        {placeholder}
      </option>
      {options.map((option) => (
        <option key={option[valueKey] as string} value={option[valueKey]}>
          {option[labelKey]}
        </option>
      ))}
    </select>
  )
}

export default Select
