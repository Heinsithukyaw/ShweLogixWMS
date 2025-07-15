import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { MaterialHandlingEqContent } from '../../type/shared/materialHandlingEqType'


interface MaterialHandlingEqState {
  content: MaterialHandlingEqContent | []
  loading: boolean
  error: string | null
}

const initialState: MaterialHandlingEqState = {
  content: [],
  loading: false,
  error: null,
}

const materialHandlingEqSlice = createSlice({
  name: 'material-handling-eq',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<MaterialHandlingEqContent>) => {
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
  materialHandlingEqSlice.actions


export default materialHandlingEqSlice.reducer
