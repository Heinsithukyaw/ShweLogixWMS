import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { SupervisorContent } from '../../type/shared/supervisorType'


interface SupervisorState {
  content: SupervisorContent | []
  loading: boolean
  error: string | null
}

const initialState: SupervisorState = {
  content: [],
  loading: false,
  error: null,
}

const supervisorSlice = createSlice({
  name: 'supervisor',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<SupervisorContent>) => {
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
  supervisorSlice.actions


export default supervisorSlice.reducer
