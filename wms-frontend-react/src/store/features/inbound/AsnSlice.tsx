import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import { AsnContent, AsnType } from '../../../type/inbound/asnType'

// interface AsnContent {
// //   asn_code: string | null
// //   asn_name: string | null
// //   purchase_order_name: string | null
// //   expected_arrival: string | null
// //   carrier_code: string | null
// //   tracking_number: string | null
// //   total_items: string | null
// //   total_pallets: string | null
// //   dimensions: string | null
// //   received_date: string | null
// //   status: any | null
//   [key: string]: any
// }

interface AsnState {
  //   asn_code: null
  //   asn_name: null
  //   purchase_order_name: null
  //   expected_arrival: null
  //   carrier_code: null
  //   tracking_number: null
  //   total_items: null
  //   total_pallets: null
  //   dimensions: null
  //   received_date: null
  //   status: null
  content: AsnContent | []
  data: AsnType | null
  loading: boolean
  error: string | null
  isFetched: boolean
}

const initialState: AsnState = {
  content: [],
  data: null,
  loading: false,
  error: null,
  isFetched: false
}

const asnSlice = createSlice({
  name: 'asn',
  initialState,
  reducers: {
    setContentStart: (state) => {
      state.loading = !state.isFetched
      state.error = null
    },
    setContentSuccess: (state, action: PayloadAction<AsnContent>) => {
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
    // setAsnData: (
    //   state,
    //   action: PayloadAction<asnState>
    // ) => {
    //   state.id = action.payload.id
    //   state.asn_code = action.payload.asn_code
    //   state.supplier_id = action.payload.supplier_id
    //   state.purchase_order_id = action.payload.purchase_order_id
    //   state.expected_arrival = action.payload.expected_arrival
    //   state.carrier_id = action.payload.carrier_id
    //   state.tracking_number = action.payload.tracking_number
    //   state.total_items = action.payload.total_items
    //   state.total_pallet = action.payload.total_pallet
    //   state.notes = action.payload.notes
    //   state.status = action.payload.status
    // },
    // clearAsnData: (state) => {
    //     state.id = null
    //     state.asn_code = null
    //     state.supplier_id = null
    //     state.purchase_order_id = null
    //     state.expected_arrival = null
    //     state.carrier_id = null
    //     state.tracking_number = null
    //     state.total_items = null
    //     state.total_pallet = null
    //     state.notes = null
    //     state.status = null
    // },
  },
})

export const { setContentStart, setContentSuccess, setContentFailure, setToggleFetched, getContentData, deleteContentData } =
  asnSlice.actions


export default asnSlice.reducer
