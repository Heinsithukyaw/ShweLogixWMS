import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { DockContent } from '../../type/shared/dockType'


interface DockState {
  content: DockContent | []
  loading: boolean
  error: string | null
}

const initialState: DockState = {
  content: [],
  loading: false,
  error: null,
}

const dockSlice = createSlice({
  name: 'dock',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<DockContent>) => {
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
  dockSlice.actions


export default dockSlice.reducer
