import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { LocationContent } from '../../type/shared/locationType'


interface LocationState {
  content: LocationContent | []
  loading: boolean
  error: string | null
}

const initialState: LocationState = {
  content: [],
  loading: false,
  error: null,
}

const locationSlice = createSlice({
  name: 'location',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<LocationContent>) => {
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
  locationSlice.actions


export default locationSlice.reducer
