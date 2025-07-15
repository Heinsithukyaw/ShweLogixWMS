import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { LocationContent, LocationType } from '../../../type/masterData/locationType'

interface LocationState {
  content: LocationContent | []
  zoneContent: LocationContent | []
  data: LocationType | null
  zoneData: LocationType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: LocationState = {
  content: [],
  zoneContent:[],
  data: null,
  zoneData:null,
  loading: false,
  error: null,
  isFetched: false
}

const locationSlice = createSlice({
  name: 'location',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<LocationContent>) => {
      state.loading = false
      state.content = action.payload
      state.isFetched = true
    },
    setContentFailure: (state, action: PayloadAction<string>) => {
      state.loading = false
      state.error = action.payload
    },
    setZoneLocationContentSuccess: (
      state,
      action: PayloadAction<LocationContent>
    ) => {
      state.loading = false
      state.zoneContent = action.payload
      state.isFetched = true
    },
    getContentData: (state, action) => {
      const id = action.payload
      state.data = state.content?.find((x: any) => x.id === id) || null
    },
    getZoneLocationSpecificContentData: (state, action) => {
      const id = action.payload
      state.zoneData = state.zoneContent?.find((x: any) => x.id === id) || null
    },
    deleteContentData: (state, action) => {
      const id = action.payload
      state.content = state.content?.filter((x: any) => x.id !== id) || []
    },
    deleteZoneLocationContentData: (state, action) => {
      const id = action.payload
      state.zoneContent =
        state.zoneContent?.filter((x: any) => x.id !== id) || []
    },
    setToggleFetched: (state) => {
      state.isFetched = !state.isFetched
    },
    getZoneLocationContentData: (state, action) => {
      const zone_id = action.payload
      state.zoneContent =
        state.content?.filter((x: any) => x.zone_id === zone_id) || null
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
  getZoneLocationContentData,
  setZoneLocationContentSuccess,
  deleteZoneLocationContentData,
  getZoneLocationSpecificContentData,
} = locationSlice.actions


export default locationSlice.reducer
