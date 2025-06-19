import React, { useState, useEffect, useMemo} from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import BaseModal from '../../../components/ui/modal'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import TextAreaInput from '../../../components/form/form-elements/TextAreaInput'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import ToggleSwitchInput from '../../../components/form/form-elements/ToggleSwitch'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  payment_term_code: string
  payment_term_name: string
  payment_type: string
  payment_due_day: string
  discount_percent:string
  discount_day:string
  creation_date:string
  created_by:string
  last_modified_date:string
  last_modified_by:string
  status: number
  [key: string]: any
}

interface Errors {
  payment_term_code?: string
  payment_term_name?: string
  payment_type?: string
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
    selector: (row: RowData) => row.payment_term_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.payment_term_name,
    sortable: true,
  },
  {
    name: 'Type',
    selector: (row: RowData) => row.payment_type || '-',
    sortable: true,
  },
  {
    name: 'Payment Due Days',
    selector: (row: RowData) => row.payment_due_day || '-',
    sortable: true,
  },
  {
    name: 'Discount Percent',
    selector: (row: RowData) => row.discount_percent != null ? `${row.discount_percent} %` : '-',
    sortable: true,
  },
  {
    name: 'Discount Days',
    selector: (row: RowData) => row.discount_day || '-',
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
  const [paymentTermLists, setPaymentTermLists] = useState<any>([])
  const [paymentTerm, setPaymentTerm] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const paymentTermTypeData = [
    { id: 1, value: 'Credit' },
    { id: 2, value: 'Immediate' },
    { id: 3, value: 'Advance' },
  ]

  const [formData, setFormData] = useState({
    payment_term_code:  '',
    payment_term_name:  '',
    payment_type:  '',
    payment_due_day:  '',
    discount_percent: '',
    discount_day: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    description:'',
    status: 1
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    payment_term_code: '',
    payment_term_name: '',
    payment_type: '',
    payment_due_day: '',
    discount_percent: '',
    discount_day: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    description:'',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchPaymentTermLists()
  }, [])

  const fetchPaymentTermLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('payment-terms')
      console.log(res)
      setPaymentTermLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Payment Terms lists:', err)
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
    setPaymentTerm(paymentTermLists?.find((x:any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        payment_term_code: '',
        payment_term_name: '',
        payment_type: '',
        status: '',
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      payment_term_code: '',
      payment_term_name: '',
      payment_type: '',
      status: '',
    })
    const payment_term_data = paymentTermLists.find((x: any) => x.id === row.id)
    if (payment_term_data) {
      setUpdateFormData({
        id: payment_term_data.id,
        payment_term_code: payment_term_data.payment_term_code,
        payment_term_name: payment_term_data.payment_term_name,
        payment_type: payment_term_data.payment_type,
        payment_due_day: payment_term_data.payment_due_day,
        discount_percent: payment_term_data.discount_percent,
        discount_day: payment_term_data.discount_day,
        creation_date: payment_term_data.creation_date,
        created_by: payment_term_data.created_by,
        last_modified_date: payment_term_data.last_modified_date,
        last_modified_by: payment_term_data.last_modified_by,
        description: payment_term_data.description,
        status: payment_term_data.status,
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
      const response = await http.deleteDataWithToken(`/payment-terms/${row.id}`)
      console.log(response)
      if(response.status == true){
        Swal.fire({
          title: 'Deleted!',
          text: 'Payment Term has been deleted.',
          icon: 'success',
        })
        fetchPaymentTermLists()
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
        '/payment-terms',
        formData
      )
      if(response.status === true){
          setIsCreateOpen(false)
          showToast('', 'Create Payment Terms successful', 'top-right', 'success')
          setFormData({
            payment_term_code: '',
            payment_term_name: '',
            payment_type: '',
            payment_due_day: '',
            discount_percent: '',
            discount_day: '',
            creation_date: '',
            created_by: '',
            last_modified_date: '',
            last_modified_by: '',
            description:'',
            status: 1,
          })
          fetchPaymentTermLists()
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
        showToast('', 'Create Payment Term failed!', 'top-right', 'error')
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
        `/payment-terms/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast(
          '',
          'Update Tax successful',
          'top-right',
          'success'
        )
        setUpdateFormData({
          id: '',
          payment_term_code: '',
          payment_term_name: '',
          payment_type: '',
          payment_due_day: '',
          discount_percent: '',
          discount_day: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          description:'',
          status: '',
        })
        fetchPaymentTermLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Payment Term failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
    const filteredData = useMemo(() => {
        if (!filterText) return paymentTermLists

        return paymentTermLists.filter((item: any) =>
        Object.values(item).some(
            (val) =>
            val && val.toString().toLowerCase().includes(filterText.toLowerCase())
        )
        )
    }, [filterText, paymentTermLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Payment Term Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add New Payment Term
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
                placeholder="Search Payment Terms…"
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
            <h2 className="text-xl font-semibold text-gray-800">Add New Tax</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Payment Term Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.payment_term_code}
                  onChange={handleChange('payment_term_code')}
                  onKeyUp={() => handleRemove('payment_term_code')}
                  error={!!errors.payment_term_code}
                  hint={errors.payment_term_code}
                />
              </div>
              <div>
                <Label>
                  Payment Term Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.payment_term_name}
                  onChange={handleChange('payment_term_name')}
                  onKeyUp={() => handleRemove('payment_term_name')}
                  error={!!errors.payment_term_name}
                  hint={errors.payment_term_name}
                />
              </div>

              <div>
                <Label>
                  Payment Type<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={paymentTermTypeData}
                  valueKey="value"
                  value={formData.payment_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('payment_type')
                    setFormData((prev) => ({
                      ...prev,
                      payment_type: val,
                    }))
                  }}
                  error={!!errors.payment_type}
                  hint={errors.payment_type}
                />
              </div>
              <div>
                <Label>Payment Due Days</Label>
                <Input
                  type="number"
                  value={formData.payment_due_day}
                  onChange={handleChange('payment_due_day')}
                  onKeyUp={() => handleRemove('payment_due_day')}
                />
              </div>
              <div>
                <Label>Discount Percent</Label>
                <Input
                  type="number"
                  value={formData.discount_day}
                  onChange={handleChange('discount_day')}
                  onKeyUp={() => handleRemove('discount_day')}
                />
              </div>

              <div>
                <Label>Discount Days</Label>
                <Input
                  type="number"
                  value={formData.discount_day}
                  onChange={handleChange('discount_day')}
                  onKeyUp={() => handleRemove('discount_day')}
                />
              </div>
              <div>
                <Label>Description</Label>
                <TextAreaInput
                  value={formData.description}
                  onChange={(value) =>
                    handleChange('description')({
                      target: { value },
                    } as React.ChangeEvent<any>)
                  }
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
            <h2 className="text-xl font-semibold text-gray-800">
              Payment Term
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Payment Term Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={paymentTerm.payment_term_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Payment Term Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={paymentTerm.payment_term_name}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Payment Type</Label>
                <Input
                  type="text"
                  value={paymentTerm.payment_type}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Payment Due Days</Label>
                <Input
                  type="number"
                  value={paymentTerm.payment_due_day}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Discount Percent</Label>
                <Input
                  type="number"
                  value={paymentTerm.discount_percent}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Discount Days</Label>
                <Input
                  type="number"
                  value={paymentTerm.discount_day}
                  onChange={handleChange('discount_day')}
                  onKeyUp={() => handleRemove('discount_day')}
                />
              </div>
              <div>
                <Label>Description</Label>
                <TextAreaInput
                  value={paymentTerm.description}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!paymentTerm.status}
                  onToggleChange={handleToggle}
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
              Edit Payment Terms
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Payment Term Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.payment_term_code}
                  onChange={handleChange('payment_term_code')}
                  onKeyUp={() => handleRemove('payment_term_code')}
                  error={!!errors.payment_term_code}
                  hint={errors.payment_term_code}
                />
              </div>
              <div>
                <Label>
                  Payment Term Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.payment_term_name}
                  onChange={handleChange('payment_term_name')}
                  onKeyUp={() => handleRemove('payment_term_name')}
                  error={!!errors.payment_term_name}
                  hint={errors.payment_term_name}
                />
              </div>

              <div>
                <Label>Payment Type</Label>
                <SingleSelectInput
                  options={paymentTermTypeData}
                  valueKey="value"
                  value={updateFormData.payment_type}
                  getOptionLabel={(item) => `${item.value}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('payment_type')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      payment_type: val,
                    }))
                  }}
                  error={!!errors.payment_type}
                  hint={errors.payment_type}
                />
              </div>
              <div>
                <Label>Payment Due Days</Label>
                <Input
                  type="number"
                  value={updateFormData.payment_due_day}
                  onChange={handleChange('payment_due_day')}
                  onKeyUp={() => handleRemove('payment_due_day')}
                />
              </div>
              <div>
                <Label>Discount Percent</Label>
                <Input
                  type="number"
                  value={updateFormData.discount_day}
                  onChange={handleChange('discount_day')}
                  onKeyUp={() => handleRemove('discount_day')}
                />
              </div>

              <div>
                <Label>Discount Days</Label>
                <Input
                  type="number"
                  value={updateFormData.discount_day}
                  onChange={handleChange('discount_day')}
                  onKeyUp={() => handleRemove('discount_day')}
                />
              </div>
              <div>
                <Label>Description</Label>
                <TextAreaInput
                  value={updateFormData.description}
                  onChange={(value) =>
                    handleChange('description')({
                      target: { value },
                    } as React.ChangeEvent<any>)
                  }
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


