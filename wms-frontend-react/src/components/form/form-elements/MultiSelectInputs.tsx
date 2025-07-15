// interface MultiSelectInputProps {
//   options: any[]
//   valueKey: string
//   getOptionLabel: (item: any) => string
//   onMultiSelectChange: (val: string[]) => void
//   value?: string[]
//   error?: boolean
//   hint?: string
// }

// function MultiSelectInput({
//   options,
//   valueKey,
//   getOptionLabel,
//   onMultiSelectChange,
//   value = [],
//   error = false,
//   hint = '',
// }: MultiSelectInputProps) {
//   const handleChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
//     const selectedValues = Array.from(e.target.selectedOptions).map(
//       (option) => option.value
//     )
//     onMultiSelectChange(selectedValues)
//   }

//   return (
//     <div className="space-y-1">
//       <select
//         multiple
//         value={value}
//         onChange={handleChange}
//         className={`w-full p-2 border rounded-md focus:outline-none ${
//           error ? 'border-red-500' : 'border-gray-300'
//         }`}
//       >
//         {options.map((item) => (
//           <option key={item[valueKey]} value={item[valueKey]}>
//             {getOptionLabel(item)}
//           </option>
//         ))}
//       </select>
//       {error && hint && <p className="text-sm text-red-500">{hint}</p>}
//     </div>
//   )
// }

// export default MultiSelectInput


import React from 'react'
import MultiSelect from '../MultiSelect'

interface MultiSelectInputProps {
  options: any[]
  valueKey: string | ((item: any) => string)
  getOptionLabel: (item: any) => string
  onMultiSelectChange: (val: string[]) => void
  value?: string[]
  error?: boolean
  hint?: string
}

function MultiSelectInput({
  options,
  valueKey,
  getOptionLabel,
  onMultiSelectChange,
  value = [],
  error = false,
  hint = '',
}: MultiSelectInputProps) {
  const handleChange = (selectedValues: string[]) => {
    onMultiSelectChange(selectedValues)
  }

  const getValue = (item: any): string => {
    if (typeof valueKey === 'function') {
      return valueKey(item)
    }
    return item[valueKey] 
  }

  return (
    <div className="space-y-1">
      <MultiSelect
        label=""
        options={options.map((item) => ({
          value: getValue(item),
          text: getOptionLabel(item),
        }))}
        value={value}
        onChange={handleChange}
        error={error}
      />
      {error && hint && <p className="text-sm text-red-500">{hint}</p>}
    </div>
  )
}

export default MultiSelectInput
