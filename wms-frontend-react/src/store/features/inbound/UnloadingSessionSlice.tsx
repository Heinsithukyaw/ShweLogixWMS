import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { UnloadingSessionContent, UnloadingSessionType } from '../../../type/inbound/unloadingSessionType'

interface UnloadingSessionState {
  content: UnloadingSessionContent | []
  data: UnloadingSessionType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: UnloadingSessionState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const unloadingSessionSlice = createSlice({
  name: 'unloading-session',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<UnloadingSessionContent>) => {
      state.loading = false
      state.content = action.payload
      state.isFetched = true
    },
    setContentFailure: (state, action: PayloadAction<string>) => {
      state.loading = false
      state.error = action.payload
    },
    getContentData: (state, action) => {
      const id = action.payload
      state.data = state.content?.find((x: any) => x.id === id) || null
    },
    deleteContentData: (state, action) => {
      const id = action.payload
      state.content = state.content?.filter((x: any) => x.id !== id) || []
    },
    setToggleFetched: (state) => {
      state.isFetched = !state.isFetched
    },
  },
})

export const { setContentStart, setContentSuccess, setContentFailure, setToggleFetched, getContentData, deleteContentData } =
  unloadingSessionSlice.actions


export default unloadingSessionSlice.reducer
