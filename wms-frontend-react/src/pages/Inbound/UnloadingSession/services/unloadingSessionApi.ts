import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/UnloadingSessionSlice'
import { UnloadingSessionType } from '../../../../type/inbound/unloadingSessionType'

export const fetchUnloadingSessionLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'unloading-sessions'
        )
        console.log('unloading session lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Unloading Session lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createUnloadingSession = (data: UnloadingSessionType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('unloading-sessions', data)
    console.log('Create Unloading Session successfully!')
    dispatch(fetchUnloadingSessionLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create Unloading Session', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateUnloadingSession = (data: UnloadingSessionType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`unloading-sessions/${id}`, data)
    console.log('Update Unloading Session successfully!')
    dispatch(fetchUnloadingSessionLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Unloading Session', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteUnloadingSession = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`unloading-sessions/${id}`)
    console.log('Deleted Unloading Session successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Unloading Session', err)
      return false
  }
}
