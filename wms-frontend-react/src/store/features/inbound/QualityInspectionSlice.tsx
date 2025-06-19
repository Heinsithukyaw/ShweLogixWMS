import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { QualityInspectionContent, QualityInspectionType } from '../../../type/inbound/qualityInspectionType'

interface QualityInspectionState {
  content: QualityInspectionContent | []
  data: QualityInspectionType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: QualityInspectionState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const qualityInspectionSlice = createSlice({
  name: 'quality-inspection',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<QualityInspectionContent>) => {
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
  qualityInspectionSlice.actions


export default qualityInspectionSlice.reducer
