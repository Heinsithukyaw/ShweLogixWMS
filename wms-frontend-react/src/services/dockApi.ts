import http from '../lib/http'
import { AppDispatch } from '../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
} from '../store/features/DockSlice'

export const fetchDockLists = () => async (dispatch: AppDispatch) => {
  try {
    dispatch(setContentStart())
    const res = await http.fetchDataWithToken('dock-equipments')
    
    dispatch(setContentSuccess(res?.data?.data || []))
  } catch (err: any) {
    console.error('Failed to fetch Dock Equipments lists', err)
    dispatch(setContentFailure(err.message || 'Unknown error'))
  }
}
