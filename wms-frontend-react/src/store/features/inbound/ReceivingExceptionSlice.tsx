import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import {
  ReceivingExceptionContent,
  ReceivingExceptionType,
} from '../../../type/inbound/receivingExceptionType'

interface ReceivingExceptionState {
  content: ReceivingExceptionContent | []
  data: ReceivingExceptionType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: ReceivingExceptionState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false,
}

const receivingExceptionSlice = createSlice({
  name: 'receiving-exception',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<ReceivingExceptionContent>) => {
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
} = receivingExceptionSlice.actions

export default receivingExceptionSlice.reducer
