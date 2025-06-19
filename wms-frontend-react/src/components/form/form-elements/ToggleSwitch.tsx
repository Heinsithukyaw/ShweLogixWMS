import React from 'react'
import Switch from '../switch/Switch'

interface ToggleSwitchProps {
  label?: string
  defaultChecked?: boolean
  onToggleChange?: (checked: boolean) => void
  disabled?: boolean
}

const ToggleSwitch: React.FC<ToggleSwitchProps> = ({
  label = '',
  defaultChecked = false,
  onToggleChange,
  disabled = false,
}) => {
  const handleSwitchChange = (checked: boolean) => {
    if (onToggleChange) {
      onToggleChange(checked)
    }
  }

  return (
    <div className="flex gap-4">
      <Switch
        label={label}
        defaultChecked={defaultChecked}
        onChange={handleSwitchChange}
        disabled={disabled}
      />
    </div>
  )
}

export default ToggleSwitch

// import ComponentCard from "../../common/ComponentCard";
// import Switch from "../switch/Switch";

// export default function ToggleSwitch() {
//   const handleSwitchChange = (checked: boolean) => {
//     console.log("Switch is now:", checked ? "ON" : "OFF");
//   };
//   return (
//     <ComponentCard title="Toggle switch input">
//       <div className="flex gap-4">
//         <Switch
//           label="Default"
//           defaultChecked={true}
//           onChange={handleSwitchChange}
//         />
//         <Switch
//           label="Checked"
//           defaultChecked={true}
//           onChange={handleSwitchChange}
//         />
//         <Switch label="Disabled" disabled={true} />
//       </div>{" "}
//       <div className="flex gap-4">
//         <Switch
//           label="Default"
//           defaultChecked={true}
//           onChange={handleSwitchChange}
//           color="gray"
//         />
//         <Switch
//           label="Checked"
//           defaultChecked={true}
//           onChange={handleSwitchChange}
//           color="gray"
//         />
//         <Switch label="Disabled" disabled={true} color="gray" />
//       </div>
//     </ComponentCard>
//   );
// }
