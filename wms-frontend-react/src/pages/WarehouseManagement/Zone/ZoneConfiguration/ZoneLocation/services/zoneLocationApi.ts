import http from '../../../../../../lib/http'
import { AppDispatch } from '../../../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData,
  setZoneLocationContentSuccess,
  deleteZoneLocationContentData
} from '../../../../../../store/features/masterData/LocationSlice'
import { LocationType } from '../../../../../../type/masterData/locationType'

export const fetchZoneLocationLists = (zone_id:any) => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'locations'
        )
        console.log('location lists')
        console.log(res)
        console.log(zone_id)
        dispatch(setContentSuccess(res.data?.data || []))
        dispatch(setZoneLocationContentSuccess(res.data?.data.filter((x:any) => x.zone_id === parseInt(zone_id)) || []))
    } catch (err:any) {
        console.error('Failed to fetch Location lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createZoneLocation =
  (data: LocationType, zone_id: any) => async (dispatch: AppDispatch) => {
    try {
      await http.postDataWithToken('locations', data)
      console.log('Create Location successfully!')
      dispatch(fetchZoneLocationLists(zone_id))
      return { status: false, error: [] }
    } catch (err: any) {
      console.error('Failed to create Location', err)
      if (err?.data.status == 422) {
        return { status: false, error: err?.data }
      }
    }
  }

export const updateZoneLocation =
  (data: LocationType, id: any, zone_id: any) =>
  async (dispatch: AppDispatch) => {
    try {
      await http.putDataWithToken(`locations/${id}`, data)
      console.log('Update Location successfully!')
      dispatch(fetchZoneLocationLists(zone_id))
      return { status: true, error: [] }
    } catch (err: any) {
      console.error('Failed to update ASN', err)
      if (err?.status == 422) {
        console.log(err)
        return { status: false, error: err }
      }
      return { status: false, error: err }
    }
  }

export const deleteZoneLocation =
  (id: any) => async (dispatch: AppDispatch) => {
    try {
      await http.deleteDataWithToken(`locations/${id}`)
      console.log('Deleted Location successfully!')
      dispatch(deleteContentData(id))
      dispatch(deleteZoneLocationContentData(id))
      return true
    } catch (err: any) {
      console.error('Failed to delete Zone Location', err)
      return false
    }
  }
