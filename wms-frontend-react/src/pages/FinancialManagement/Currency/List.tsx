import React, { useState, useEffect, useMemo} from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import BaseModal from '../../../components/ui/modal'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import ToggleSwitchInput from '../../../components/form/form-elements/ToggleSwitch'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  currency_code: string
  currency_name: string
  symbol: string
  country: string
  exchange_rate:string
  base_currency:string
  decimal_places:string
  creation_date:string
  created_by:string
  last_modified_date:string
  last_modified_by:string
  status: number
  [key: string]: any
}

interface Errors {
  currency_code?: string
  currency_name?: string
  symbol?: string
  country?: string
  status?: any
}

const columns: TableColumn<RowData>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row: RowData) => row.currency_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.currency_name,
    sortable: true,
  },
  {
    name: 'Symbol',
    selector: (row: RowData) => row.symbol || '-',
    sortable: true,
  },
  {
    name: 'Country',
    selector: (row: RowData) => row.country || '-',
    sortable: true,
  },
  {
    name: 'Email Address',
    selector: (row: RowData) => row.email || '-',
    sortable: true,
  },
  {
    name: 'Exchange Rate',
    selector: (row: RowData) => row.exchange_rate || '-',
    sortable: true,
  },
  {
    name: 'Base Currency',
    selector: (row: RowData) => row.base_currency || '-',
    sortable: true,
  },
  {
    name: 'Decimal Places',
    selector: (row: RowData) => row.decimal_places || '-',
    sortable: true,
  },
  {
    name: 'Creation Date',
    selector: (row: RowData) => row.creation_date || '-',
    sortable: true,
  },
  {
    name: 'Created By',
    selector: (row: RowData) => row.created_by || '-',
    sortable: true,
  },
  {
    name: 'Last Modified Date',
    selector: (row: RowData) => row.last_modified_date || '-',
    sortable: true,
  },
  {
    name: 'Last Modified By',
    selector: (row: RowData) => row.last_modified_by || '-',
    sortable: true,
  },
  {
    name: 'Status',
    cell: (row: RowData) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.status === 1
            ? 'bg-green-100 text-green-700'
            : 'bg-red-100 text-red-700'
        }`}
      >
        {row.status === 1 ? 'Active' : 'In Active'}
      </span>
    ),
    sortable: true,
  },
]

const List: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [currencyLists, setCurrencyLists] = useState<any>([])
  const [currency, setCurrency] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const countryData = [
    { id: 1, value: 'USA' },
    { id: 2, value: 'UK' },
    { id: 3, value: 'India' },
    { id: 4, value: 'Singapore' },
    { id: 5, value: 'Germany' },
    { id: 6, value: 'Australia' },
    { id: 7, value: 'France' },
    { id: 8, value: 'China' },
    { id: 9, value: 'Japan' },
    { id: 10, value: 'Canada' },
  ]

  const [formData, setFormData] = useState({
    currency_code: '',
    currency_name: '',
    symbol: '',
    country: '',
    exchange_rate: '',
    base_currency: '',
    decimal_places: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: 1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    currency_code: '',
    currency_name: '',
    symbol: '',
    country: '',
    exchange_rate: '',
    base_currency: '',
    decimal_places: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchCurrencyLists()
  }, [])

  const fetchCurrencyLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('currencies')
      console.log(res)
      setCurrencyLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Shipping Currency lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const handleRemove = (field: string) => {
   
    setErrors((prev) => ({
      ...prev,
      [field]: null,
    }))
  }

  const handleToggle = (checked: boolean) => {
    const is_active = checked?1:0
    if(isCreateOpen){
      setFormData((prev: any) => ({
        ...prev,
        status: is_active,
      }))
    }else{
      setUpdateFormData((prev: any) => ({
        ...prev,
        status: is_active,
      }))
    }
  }

  const handleCloseModal = () => {
    setIsCreateOpen(false)
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

  const handleView = (row: any) => {
    setCurrency(currencyLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        currency_code: '',
        currency_name: '',
        symbol: '',
        country: '',
        status: ''
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      currency_code: '',
      currency_name: '',
      symbol: '',
      country: '',
      status: '',
    })
    const currency_data = currencyLists.find((x: any) => x.id === row.id)
    if (currency_data) {
      setUpdateFormData({
        id: currency_data.id,
        currency_code: currency_data.currency_code,
        currency_name: currency_data.currency_name,
        symbol: currency_data.symbol,
        country: currency_data.country,
        exchange_rate: currency_data.exchange_rate,
        base_currency: currency_data.base_currency,
        decimal_places: currency_data.decimal_places,
        creation_date: currency_data.creation_date,
        created_by: currency_data.created_by,
        last_modified_date: currency_data.last_modified_date,
        last_modified_by: currency_data.last_modified_by,
        status: currency_data.status,
      })
    }
  }

  const handleDelete = async (row:any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/currencies/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Shipping Currency has been deleted.',
          icon: 'success',
        })
        fetchCurrencyLists()
      }else{
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      }
    } catch (error: any) {
      Swal.fire({
        title: 'Error!',
        text: error.message || 'Failed to delete item.',
        icon: 'error',
      })
    }
  }

  const handleChange = (field: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const value = e.target.value
    if(isCreateOpen){
      setFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }else{
      setUpdateFormData((prev) => ({
        ...prev,
        [field]: value,
      }))
    }
  }

  const handleSubmit = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.postDataWithToken(
        '/currencies',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Shipping Carrier successful', 'top-right', 'success')
          setFormData({
            currency_code: '',
            currency_name: '',
            symbol: '',
            country: '',
            exchange_rate: '',
            base_currency: '',
            decimal_places: '',
            creation_date: '',
            created_by: '',
            last_modified_date: '',
            last_modified_by: '',
            status: 1,
          })
          fetchCurrencyLists()
        }else{
        
          showToast(
            '',
            'Something went wrong!.',
            'top-right',
            'error'
          )
      }
    } catch (err:any) {
      if(err?.status === 422){
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      }else{
        showToast('', 'Create Currency failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const handleUpdate = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.putDataWithToken(
        `/currencies/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Currency successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          currency_code: '',
          currency_name: '',
          symbol: '',
          country: '',
          exchange_rate: '',
          base_currency: '',
          decimal_places: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status:''
        })
        fetchCurrencyLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Currency failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
        if (!filterText) return currencyLists

        return currencyLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, currencyLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Currency Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Currency
        </Button>
      </div>

      {isPageLoading ? (
        <div className="flex justify-center items-center space-x-2">
          <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
          <span className="text-sm text-gray-500">Loading...</span>
        </div>
      ) : (
        <AdvancedDataTable
          data={filteredData || []}
          columns={columns}
          onView={handleView}
          onEdit={handleEdit}
          onDelete={handleDelete}
          subHeader
          subHeaderComponent={
            <div className="w-full flex items-center justify-between px-0 py-2 bg-muted">
              <Input
                type="text"
                placeholder="Search Currencies…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      )}

      {isCreateOpen && (
        <BaseModal
          isOpen={isCreateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Add New Currency
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Currency Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.currency_code}
                  onChange={handleChange('currency_code')}
                  onKeyUp={() => handleRemove('currency_code')}
                  error={!!errors.currency_code}
                  hint={errors.currency_code}
                />
              </div>
              <div>
                <Label>
                  Currency Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.currency_name}
                  onChange={handleChange('currency_name')}
                  onKeyUp={() => handleRemove('currency_name')}
                  error={!!errors.currency_name}
                  hint={errors.currency_name}
                />
              </div>
              <div>
                <Label>
                  Symbol<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.symbol}
                  onChange={handleChange('symbol')}
                  onKeyUp={() => handleRemove('symbol')}
                  error={!!errors.symbol}
                  hint={errors.symbol}
                />
              </div>
              <div>
                <Label>Country</Label>
                <SingleSelectInput
                  options={countryData}
                  valueKey="value"
                  value={formData.country}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('country')
                    setFormData((prev) => ({
                      ...prev,
                      country: val,
                    }))
                  }}
                  error={!!errors.country}
                  hint={errors.country}
                />
              </div>
              <div>
                <Label>Exchange Rate</Label>
                <Input
                  type="text"
                  value={formData.exchange_rate}
                  onChange={handleChange('exchange_rate')}
                  onKeyUp={() => handleRemove('exchange_rate')}
                />
              </div>
              <div>
                <Label>Base Currency</Label>
                <Input
                  type="text"
                  value={formData.base_currency}
                  onChange={handleChange('base_currency')}
                  onKeyUp={() => handleRemove('base_currency')}
                />
              </div>

              <div>
                <Label>Decimal Places</Label>
                <Input
                  type="text"
                  value={formData.decimal_places}
                  onChange={handleChange('decimal_places')}
                  onKeyUp={() => handleRemove('decimal_places')}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!formData.status}
                  onToggleChange={handleToggle}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button
                variant="primary"
                startIcon={isLoading && <Spinner size={4} />}
                onClick={handleSubmit}
              >
                Confirm
              </Button>
            </div>
          </div>
        </BaseModal>
      )}

      {isViewOpen && (
        <BaseModal
          isOpen={isViewOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">Currency</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Currency Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={currency.currency_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Currency Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={currency.currency_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Symbol<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={currency.symbol} disabled={true} />
              </div>
              <div>
                <Label>Country</Label>
                <Input type="text" value={currency.country} disabled={true} />
              </div>
              <div>
                <Label>Exchange Rate</Label>
                <Input
                  type="text"
                  value={currency.exchange_rate}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Base Currency</Label>
                <Input
                  type="text"
                  value={currency.base_currency}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Decimal Places</Label>
                <Input
                  type="text"
                  value={currency.decimal_places}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!currency.status}
                  onToggleChange={handleToggle}
                  disabled={true}
                />
              </div>
            </div>
          </div>
        </BaseModal>
      )}

      {isUpdateOpen && (
        <BaseModal
          isOpen={isUpdateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Edit Currency
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Currency Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.currency_code}
                  onChange={handleChange('currency_code')}
                  onKeyUp={() => handleRemove('currency_code')}
                  error={!!errors.currency_code}
                  hint={errors.currency_code}
                />
              </div>
              <div>
                <Label>
                  Currency Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.currency_name}
                  onChange={handleChange('currency_name')}
                  onKeyUp={() => handleRemove('currency_name')}
                  error={!!errors.currency_name}
                  hint={errors.currency_name}
                />
              </div>
              <div>
                <Label>
                  Symbol<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.symbol}
                  onChange={handleChange('symbol')}
                  onKeyUp={() => handleRemove('symbol')}
                  error={!!errors.symbol}
                  hint={errors.symbol}
                />
              </div>
              <div>
                <Label>Country</Label>
                <SingleSelectInput
                  options={countryData}
                  valueKey="value"
                  value={updateFormData.country}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('country')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      country: val,
                    }))
                  }}
                  error={!!errors.country}
                  hint={errors.country}
                />
              </div>
              <div>
                <Label>Exchange Rate</Label>
                <Input
                  type="text"
                  value={updateFormData.exchange_rate}
                  onChange={handleChange('exchange_rate')}
                  onKeyUp={() => handleRemove('exchange_rate')}
                />
              </div>
              <div>
                <Label>Base Currency</Label>
                <Input
                  type="text"
                  value={updateFormData.base_currency}
                  onChange={handleChange('base_currency')}
                  onKeyUp={() => handleRemove('base_currency')}
                />
              </div>

              <div>
                <Label>Decimal Places</Label>
                <Input
                  type="text"
                  value={updateFormData.decimal_places}
                  onChange={handleChange('decimal_places')}
                  onKeyUp={() => handleRemove('decimal_places')}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!updateFormData.status}
                  onToggleChange={handleToggle}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button
                variant="primary"
                startIcon={isLoading && <Spinner size={4} />}
                onClick={handleUpdate}
              >
                Update
              </Button>
            </div>
          </div>
        </BaseModal>
      )}
    </div>
  )
}

export default List


