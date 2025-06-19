import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { UomContent } from '../../type/shared/uomType'


interface UomState {
  content: UomContent | []
  loading: boolean
  error: string | null
}

const initialState: UomState = {
  content: [],
  loading: false,
  error: null,
}

const uomSlice = createSlice({
  name: 'uom',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<UomContent>) => {
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
  uomSlice.actions


export default uomSlice.reducer
