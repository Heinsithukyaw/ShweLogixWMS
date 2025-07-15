import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { EmpContent } from '../../type/shared/empType'


interface EmpState {
  content: EmpContent | []
  loading: boolean
  error: string | null
}

const initialState: EmpState = {
  content: [],
  loading: false,
  error: null,
}

const empSlice = createSlice({
  name: 'emp',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<EmpContent>) => {
      state.loading = false
      state.content = action.payload
    },
    setContentFailure: (state, action: PayloadAction<string>) => {
      state.loading = false
      state.error = action.payload
    },
  },
})

export const { setContentStart, setContentSuccess, setContentFailure } =
  empSlice.actions

export default empSlice.reducer
