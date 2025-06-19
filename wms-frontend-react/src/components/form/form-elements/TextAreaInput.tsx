import React from 'react'
import TextArea from '../input/TextArea'
// import Label from '../Label'

interface TextAreaInputProps {
  value: string
  onChange?: (e: React.ChangeEvent<HTMLTextAreaElement>) => void
  disabled?: boolean
}

export default function TextAreaInput({
  value,
  onChange,
  disabled,
}: TextAreaInputProps) {
  return (
    <div className="space-y-6">
      <div>
        {/* <Label>Description</Label> */}
        <TextArea
          value={value}
          onChange={onChange}
          rows={6}
          disabled={disabled}
        />
      </div>
    </div>
  )
}

// import { useState } from "react";
// // import ComponentCard from "../../common/ComponentCard";
// import TextArea from "../input/TextArea";
// import Label from "../Label";

// export default function TextAreaInput() {
//   const [message, setMessage] = useState("");
//   return (
//     // <ComponentCard title="Textarea input field">
//       <div className="space-y-6">
//         {/* Default TextArea */}
//         <div>
//           <Label>Description</Label>
//           <TextArea
//             value={message}
//             onChange={(value) => setMessage(value)}
//             rows={6}
//           />
//         </div>

//         {/* Disabled TextArea */}
//         {/* <div>
//           <Label>Description</Label>
//           <TextArea rows={6} disabled />
//         </div> */}

//         {/* Error TextArea */}
//         {/* <div>
//           <Label>Description</Label>
//           <TextArea
//             rows={6}
//             value={messageTwo}
//             error
//             onChange={(value) => setMessageTwo(value)}
//             hint="Please enter a valid message."
//           />
//         </div> */}
//       </div>
//     // </ComponentCard>
//   );
// }
