import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { ZoneContent } from '../../type/shared/zoneType'


interface ZoneState {
  content: ZoneContent | []
  loading: boolean
  error: string | null
}

const initialState: ZoneState = {
  content: [],
  loading: false,
  error: null,
}

const zoneSlice = createSlice({
  name: 'zone',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ZoneContent>) => {
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
  zoneSlice.actions


export default zoneSlice.reducer
