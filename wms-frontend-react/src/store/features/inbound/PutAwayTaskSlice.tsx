import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import {
  PutAwayTaskContent,
  PutAwayTaskType,
} from '../../../type/inbound/putAwayTaskType'

interface PutAwayTaskState {
  content: PutAwayTaskContent | []
  data: PutAwayTaskType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: PutAwayTaskState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false,
}

const putAwayTaskSlice = createSlice({
  name: 'put-away-task',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<PutAwayTaskContent>) => {
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

export const {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  setToggleFetched,
  getContentData,
  deleteContentData,
} = putAwayTaskSlice.actions

export default putAwayTaskSlice.reducer
