import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { AreaContent } from '../../type/shared/areaType'


interface AreaState {
  content: AreaContent | []
  loading: boolean
  error: string | null
}

const initialState: AreaState = {
  content: [],
  loading: false,
  error: null,
}

const areaSlice = createSlice({
  name: 'area',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<AreaContent>) => {
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
  areaSlice.actions


export default areaSlice.reducer
