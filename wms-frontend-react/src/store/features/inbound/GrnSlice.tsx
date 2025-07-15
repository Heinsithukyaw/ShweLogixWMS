import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { GrnContent, GrnType } from '../../../type/inbound/grnType'

interface GrnState {
  content: GrnContent | []
  data: GrnType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: GrnState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const grnSlice = createSlice({
  name: 'grn',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<GrnContent>) => {
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
  grnSlice.actions


export default grnSlice.reducer
