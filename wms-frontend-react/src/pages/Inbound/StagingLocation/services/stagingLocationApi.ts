import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/StagingLocationSlice'
import { StagingLocationType } from '../../../../type/inbound/stagingLocationType'

export const fetchStagingLocationLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken('staging-locations')
        console.log('staging location lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Staging Location lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createStagingLocation = (data: StagingLocationType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('staging-locations', data)
    console.log('Create Staging Location successfully!')
    dispatch(fetchStagingLocationLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create Staging Location', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateStagingLocation = (data: StagingLocationType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`staging-locations/${id}`, data)
    console.log('Update Staging Location successfully!')
    dispatch(fetchStagingLocationLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Staging Location', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteStagingLocation = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`staging-locations/${id}`)
    console.log('Deleted Staging Location successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Staging Location', err)
      return false
  }
}
