import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { PalletContent } from '../../type/shared/palletType'


interface PalletState {
  content: PalletContent | []
  loading: boolean
  error: string | null
}

const initialState: PalletState = {
  content: [],
  loading: false,
  error: null,
}

const palletSlice = createSlice({
  name: 'pallet',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = true
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<PalletContent>) => {
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
  palletSlice.actions


export default palletSlice.reducer
