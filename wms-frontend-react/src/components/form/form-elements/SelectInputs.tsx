interface SingleSelectInputProps {
  options: any[]
  valueKey: string
  getOptionLabel: (item: any) => string
  onSingleSelectChange: (val: string) => void
  value?: string | number
  error?: boolean
  hint?: string | number
}

function SingleSelectInput({
  options,
  valueKey,
  getOptionLabel,
  onSingleSelectChange,
  value,
  error = false,
  hint = '',
}: SingleSelectInputProps) {
  return (
    <div className="space-y-1">
      <select
        value={value}
        onChange={(e) => onSingleSelectChange(e.target.value)}
        className={`w-full p-2 border rounded-md focus:outline-none ${
          error ? 'border-red-500' : 'border-gray-300'
        }`}
      >
        <option value="">Select an option</option>
        {options.map((item) => (
          <option key={item[valueKey]} value={item[valueKey]}>
            {getOptionLabel(item)}
          </option>
        ))}
      </select>
      {error && hint && <p className="text-sm text-red-500">{hint}</p>}
    </div>
  )
}



export default SingleSelectInput

// // import ComponentCard from "../../common/ComponentCard";
// // import Label from "../Label";
// import Select from "../Select";
// // import MultiSelect from "../MultiSelect";

// interface Option {
//   id: string
//   name: string
// }

// interface SelectInputsProps {
//   options: Option[]
//   onSingleSelectChange: (value: string) => void
// }

// export default function SelectInputs({
//   options,
//   onSingleSelectChange,
// }: SelectInputsProps) {

//   // const [selectedValues, setSelectedValues] = useState<string[]>([]);

//   // const multiOptions = [
//   //   { value: "1", text: "Option 1", selected: false },
//   //   { value: "2", text: "Option 2", selected: false },
//   //   { value: "3", text: "Option 3", selected: false },
//   //   { value: "4", text: "Option 4", selected: false },
//   //   { value: "5", text: "Option 5", selected: false },
//   // ];
//   return (
//     // <ComponentCard title="Select Inputs">
//     <div className="space-y-6">
//       <div>
//         <Select
//           options={options}
//           placeholder="Select Option"
//           onChange={onSingleSelectChange}
//           className="dark:bg-dark-900"
//         />
//       </div>
//       {/* <div>
//           <MultiSelect
//             label="Multiple Select Options"
//             options={multiOptions}
//             defaultSelected={["1", "3"]}
//             onChange={(values) => setSelectedValues(values)}
//           />
//           <p className="sr-only">
//             Selected Values: {selectedValues.join(", ")}
//           </p>
//         </div> */}
//     </div>
//     // </ComponentCard>
//   )
// }
