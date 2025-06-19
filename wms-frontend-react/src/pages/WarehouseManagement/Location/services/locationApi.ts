import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/masterData/LocationSlice'
import { LocationType } from '../../../../type/masterData/locationType'

export const fetchMainLocationLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'locations'
        )
        console.log('location lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Location lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createLocation = (data: LocationType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('locations', data)
    console.log('Create Location successfully!')
    dispatch(fetchMainLocationLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create Location', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateLocation = (data: LocationType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`locations/${id}`, data)
    console.log('Update Location successfully!')
    dispatch(fetchMainLocationLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Location', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteLocation = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`locations/${id}`)
    console.log('Deleted Location successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Location', err)
      return false
  }
}
