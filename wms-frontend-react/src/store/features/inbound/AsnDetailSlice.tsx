import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { AsnDetailContent, AsnDetailType } from '../../../type/inbound/asnDetailType'

interface AsnDetailState {
  content: AsnDetailContent | []
  data: AsnDetailType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: AsnDetailState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const asnDetailSlice = createSlice({
  name: 'asn-detail',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<AsnDetailContent>) => {
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
  asnDetailSlice.actions


export default asnDetailSlice.reducer
