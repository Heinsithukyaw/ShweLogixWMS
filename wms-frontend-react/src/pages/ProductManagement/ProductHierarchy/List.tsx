import React, {useState, useEffect} from 'react'
import http from '../../../lib/http'

import Accordion from '@mui/material/Accordion'
import AccordionSummary from '@mui/material/AccordionSummary'
import AccordionDetails from '@mui/material/AccordionDetails'
import Typography from '@mui/material/Typography'
import ExpandMoreIcon from '@mui/icons-material/ExpandMore'
import List from '@mui/material/List'

import CategoryIcon from '@mui/icons-material/Category'
import CheckCircleOutlineIcon from '@mui/icons-material/CheckCircleOutline'
import AdjustIcon from '@mui/icons-material/Adjust'
import CircleIcon from '@mui/icons-material/Circle'
import AccountTreeIcon from '@mui/icons-material/AccountTree'

interface Product {
  id: string
  product_code: string
  product_name: string
}

interface Brand {
  id: string
  brand_code: string
  brand_name: string
}

interface Category {
  id: string
  category_code: string
  category_name: string
  parent_id: any
}


const ProductHierarchy: React.FC = () => {

  const [productLists, setProductLists] = useState<Product[]>([])
  const [categoryLists, setCategoryLists] = useState<Category[]>([])
  const [subcategoryLists, setSubcategoryLists] = useState<Category[]>([])
  const [brandLists, setBrandLists] = useState<Brand[]>([])

  const [isPageLoading, setIsPageLoading] = useState(false)


  useEffect(() => {
    fetchCategoryLists()
    fetchBrandLists()
    fetchProductLists()
  },[])

  const fetchCategoryLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('categories')
      console.log(res.data)
      const categories = res.data?.data?.filter((x:any) => x.parent_id == null)
      const subcategories = res.data?.data?.filter((x: any) => x.parent_id)
      setCategoryLists(categories || [])
      setSubcategoryLists(subcategories || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Category lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchBrandLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('brands')
      console.log(res.data)
      
      setBrandLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Category lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchProductLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('products')
      console.log(res.data)
      setProductLists(res?.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Category lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  return (
    <>
      <div className="space-y-10">
        <div className="flex justify-between items-center">
          <h1 className="text-xl font-semibold">Product Hierarchy Lists</h1>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <div className="text-gray-500 text-theme-sm dark:text-gray-400 flex items-center justify-start font-semibold">
                <div className="bg-blue-800 rounded-full text-white flex justify-center items-center w-[3em] h-[3em] me-2">
                  <CategoryIcon className="" />
                </div>
                Total Product Hierarchies
              </div>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <div className="text-gray-500 text-theme-sm dark:text-gray-400 flex items-center justify-start font-semibold">
                <div className="bg-green-600 rounded-full text-white flex justify-center items-center w-[3em] h-[3em] me-2">
                  <CheckCircleOutlineIcon className="" />
                </div>
                Active Product Hierarchies
              </div>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
              <div className="text-gray-500 text-theme-sm dark:text-gray-400 flex items-center justify-start font-semibold">
                <div className="bg-blue-400 rounded-full text-white flex justify-center items-center w-[3em] h-[3em] me-2">
                  <AccountTreeIcon className="" />
                </div>
                Total Level Product Hierarchies
              </div>
              <div className="flex items-end justify-between mt-3">
                <div className="">
                  <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    0
                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>
        {isPageLoading ? (
          <div className="flex justify-center items-center space-x-2">
            <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
            <span className="text-sm text-gray-500">Loading...</span>
          </div>
        ) : (
          <div>
            {categoryLists.map((cate: any) => (
              <Accordion>
                <AccordionSummary
                  expandIcon={<ExpandMoreIcon />}
                  aria-controls="panel1-content"
                  id="panel1-header"
                >
                  <Typography component="span">
                    <CategoryIcon className="me-2" />
                    {cate.category_code} - {cate.category_name}
                  </Typography>
                </AccordionSummary>
                <AccordionDetails>
                  {/* subcategory */}
                  {subcategoryLists
                    .filter((y: any) => y.parent_id == cate.id)
                    .map((sub: any) => (
                      <Accordion>
                        <AccordionSummary
                          expandIcon={<ExpandMoreIcon />}
                          aria-controls="panel1-content"
                          id="panel1-header"
                        >
                          <Typography component="span">
                            <AdjustIcon className="me-2" />
                            {sub.category_code} - {sub.category_name}
                          </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                          {/* brand */}
                          {brandLists
                            .filter((b: any) => b.subcategory_id == sub.id)
                            .map((brand: any) => (
                              <Accordion>
                                <AccordionSummary
                                  expandIcon={<ExpandMoreIcon />}
                                  aria-controls="panel1-content"
                                  id="panel1-header"
                                >
                                  <Typography component="span">
                                    <CircleIcon className="me-2" />
                                    {brand.brand_code} - {brand.brand_name}
                                  </Typography>
                                </AccordionSummary>
                                <AccordionDetails>
                                  {/* Product */}

                                  <List>
                                    {productLists
                                      .filter(
                                        (p: any) => p.brand_id == brand.id
                                      )
                                      .map((pro: any, index: number) => (
                                        <div key={pro.id} className="ms-10">
                                          {index+1}.
                                          <span className="ms-2">
                                            {pro.product_code} -{' '}
                                            {pro.product_name}
                                          </span>
                                        </div>
                                      ))}
                                  </List>
                                </AccordionDetails>
                              </Accordion>
                            ))}
                        </AccordionDetails>
                      </Accordion>
                    ))}
                </AccordionDetails>
              </Accordion>
            ))}
          </div>
        )}
      </div>
    </>
  )
}

export default ProductHierarchy
