import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { StagingLocationContent } from '../../type/shared/stagingLocationType'


interface StagingLocationState {
  content: StagingLocationContent | []
  loading: boolean
  error: string | null
}

const initialState: StagingLocationState = {
  content: [],
  loading: false,
  error: null,
}

const stagingLocationSlice = createSlice({
  name: 'shipping-carrier',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<StagingLocationContent>) => {
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
  stagingLocationSlice.actions


export default stagingLocationSlice.reducer
