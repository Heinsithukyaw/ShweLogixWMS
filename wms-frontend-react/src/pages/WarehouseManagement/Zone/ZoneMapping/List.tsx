import React from 'react'

interface Props {
  zoneLists: any
  handleReFetchZoneListsApi: () => void
}

const bgColors = [
  'bg-red-300',
  'bg-green-300',
  'bg-blue-300',
  'bg-yellow-300',
  'bg-purple-300',
  'bg-pink-300',
  'bg-indigo-300',
  'bg-teal-300',
  'bg-orange-300',
]

const List: React.FC<Props> = ({ zoneLists }) => {
  return (
    <div className="space-y-10">
      <div className="grid gap-2 md:gap-5 grid-cols-1 md:grid-cols-3 lg:grid-cols-5">
        {zoneLists &&
          zoneLists.map((zone: any, index: number) => {
            const randomColor =
              bgColors[Math.floor(Math.random() * bgColors.length)]

            return (
              <div
                key={index}
                className={`flex flex-col justify-center items-center rounded-md shadow-lg p-3 h-32 ${randomColor}
                  transition duration-300 ease-in-out transform hover:scale-105 hover:shadow-xl`}
              >
                <div>
                  <h1 className="text-base font-semibold my-3">
                    {zone.zone_code}
                  </h1>
                  <span className="text-md text-gray-600">Storage - {zone?.utilization || 0}%</span>
                </div>
              </div>
            )
          })}
      </div>
    </div>
  )
}

export default List
