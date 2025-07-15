import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { GrnItemContent, GrnItemType } from '../../../type/inbound/grnItemType'

interface GrnItemState {
  content: GrnItemContent | []
  data: GrnItemType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: GrnItemState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const grnItemSlice = createSlice({
  name: 'grn-item',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<GrnItemContent>) => {
      state.loading = false
      state.content = action.payload
      state.isFetched = true
    },
    setContentFailure: (state, action: PayloadAction<string>) => {
      state.loading = false
      state.error = action.payload
    },
    getItemContentData: (state, action) => {
      const id = action.payload
      state.data = state.content?.filter((x: any) => x.grn_id === id) || null
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

export const { setContentStart, setContentSuccess, setContentFailure, setToggleFetched, getItemContentData, deleteContentData } =
  grnItemSlice.actions


export default grnItemSlice.reducer
