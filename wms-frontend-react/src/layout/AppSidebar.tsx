import { useCallback, useEffect, useRef, useState } from "react";
import { Link, useLocation } from "react-router";
import { RxDashboard } from 'react-icons/rx'
import { LiaSitemapSolid } from 'react-icons/lia'
import { IoBusinessOutline } from 'react-icons/io5'
import { MdOutlineWarehouse } from 'react-icons/md'
import { LiaTruckMovingSolid } from 'react-icons/lia'
import { IoPersonAddOutline } from 'react-icons/io5'
import { RxCube } from 'react-icons/rx'
import { MdOutlineEmojiTransportation } from 'react-icons/md'
import { MdOutlineAttachMoney } from 'react-icons/md'
import { IoLocationOutline } from 'react-icons/io5'
import { TbSettings2 } from 'react-icons/tb'
// import { CiAlignRight } from 'react-icons/ci'
import { IoDocumentAttachOutline } from 'react-icons/io5'


// Assume these icons are imported from an icon library
import {
  ChevronDownIcon,
  HorizontaLDots,
} from "../icons";
import { useSidebar } from "../context/SidebarContext";
// import SidebarWidget from "./SidebarWidget";

type BaseItem = {
  name: string
  icon: React.ReactNode
  path?: string
  subItems?: { name: string; path: string; pro?: boolean; new?: boolean }[]
}

type NavItem = BaseItem
type MasterItem = BaseItem


const navItems: NavItem[] = [
  {
    icon: <RxDashboard />,
    name: 'Dashboard',
    // subItems: [{ name: "Ecommerce", path: "/", pro: false }],
    path: '/',
  },
  // {
  //   icon: null,
  //   name: 'Calendar',
  //   path: '/calendar',
  // },
  // {
  //   icon: <UserCircleIcon />,
  //   name: 'User Profile',
  //   path: '/profile',
  // },
  // {
  //   name: 'Forms',
  //   icon: <ListIcon />,
  //   subItems: [{ name: 'Form Elements', path: '/form-elements', pro: false }],
  // },
  // {
  //   name: 'Tables',
  //   icon: <TableIcon />,
  //   subItems: [{ name: 'Basic Tables', path: '/basic-tables', pro: false }],
  // },
  // {
  //   name: 'Pages',
  //   icon: <PageIcon />,
  //   subItems: [
  //     { name: 'Blank Page', path: '/blank', pro: false },
  //     { name: '404 Error', path: '/error-404', pro: false },
  //   ],
  // },
]

const masterItems: MasterItem[] = [
  {
    icon: <LiaSitemapSolid />,
    name: 'Product Management',
    subItems: [
      {
        name: 'Unit Of Measure',
        path: '/product-management/unit_of_measure',
        pro: false,
      },
      {
        name: 'Category',
        path: '/product-management/category',
        pro: false,
      },
      { name: 'Brand', path: '/product-management/brand', pro: false },
      { name: 'Product', path: '/product-management/product', pro: false },
      {
        name: 'hierarchy',
        path: '/product-management/product-hierarchy',
        pro: false,
      },
    ],
  },

  {
    icon: <IoBusinessOutline />,
    name: 'Business Management',
    subItems: [
      { name: 'Party', path: '/business-management/party', pro: false },
      {
        name: 'Contact Person',
        path: '/business-management/contact',
        pro: false,
      },
    ],
  },

  {
    icon: <MdOutlineWarehouse />,
    name: 'Warehouse Management',
    subItems: [
      {
        name: 'Warehouse',
        path: '/warehouse-management/warehouse',
        pro: false,
      },
      { name: 'Area', path: '/warehouse-management/area', pro: false },
      { name: 'Zone', path: '/warehouse-management/zone', pro: false },
      { name: 'Location', path: '/warehouse-management/location', pro: false },
      {
        name: 'Location-Hierarchy',
        path: '/warehouse-management/location-hierarchy',
        pro: false,
      },
      {
        name: 'Staging Location',
        path: '/warehouse-management/staging-location',
        pro: false,
      },
    ],
  },

  {
    icon: <LiaTruckMovingSolid />,
    name: 'Equipment Management',
    subItems: [
      {
        name: 'MHE',
        path: 'equipment-management/material-handling',
        pro: false,
      },
      { name: 'Storage', path: 'equipment-management/storage', pro: false },
      { name: 'Pallet', path: 'equipment-management/pallet', pro: false },
      { name: 'Dock', path: 'equipment-management/dock', pro: false },
    ],
  },

  {
    icon: <IoPersonAddOutline />,
    name: 'HR Management',
    subItems: [
      { name: 'employee', path: 'hr-management/employees', pro: false },
    ],
  },

  {
    icon: <RxCube />,
    name: 'Order Type Management',
    subItems: [
      { name: 'type', path: '/order-type-management/order-type', pro: false },
    ],
  },

  {
    icon: <MdOutlineEmojiTransportation />,
    name: 'Carrier Management',
    subItems: [
      { name: 'carrier', path: '/shipping-management/carrier', pro: false },
    ],
  },

  {
    icon: <MdOutlineAttachMoney />,
    name: 'Financial Management',
    subItems: [
      { name: 'Category', path: '/financial-management/category', pro: false },
      {
        name: 'Cost Type',
        path: '/financial-management/cost-type',
        pro: false,
      },
      { name: 'Currency', path: '/financial-management/currency', pro: false },
      { name: 'Tax Type', path: '/financial-management/tax-type', pro: false },
      {
        name: 'Payment Terms',
        path: '/financial-management/payment-terms',
        pro: false,
      },
    ],
  },

  {
    icon: <IoLocationOutline />,
    name: 'Geo Management',
    subItems: [
      { name: 'country', path: '/geo-management/country', pro: false },
      { name: 'state', path: '/geo-management/state', pro: false },
      { name: 'city', path: '/geo-management/city', pro: false },
    ],
  },

  {
    icon: <TbSettings2 />,
    name: 'Operation Management',
    subItems: [
      { name: 'Status', path: '/operational-management/status', pro: false },
      {
        name: 'Activity Type',
        path: '/operational-management/activity-type',
        pro: false,
      },
    ],
  },

  // {
  //   icon: <CiAlignRight />,
  //   name: 'Quality Management',
  //   subItems: [
  //     {
  //       name: 'Data Quality Dashboard',
  //       path: '/data-quality-dashboard',
  //       pro: false,
  //     },
  //     { name: 'Data Lineage', path: '/data-lineage', pro: false },
  //     { name: 'Data Reports', path: '/data-reports', pro: false },
  //   ],
  // },
]

const inboundItems: MasterItem[] = [
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Dashboard',
    path: '/inbound-operation/dashboard',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'ASN',
    path: '/inbound-operation/asn',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'ASN Detail',
    path: '/inbound-operation/asn-detail',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Inbound Shipment',
    path: '/inbound-operation/inbound-shipment',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Shipment Detail',
    path: '/inbound-operation/inbound-shipment-detail',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Receiving Appointment',
    path: '/inbound-operation/receiving-appointment',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Unloading Session',
    path: '/inbound-operation/unloading-session',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Quality Inspection',
    path: '/inbound-operation/quality-inspection',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Good Received Note',
    path: '/inbound-operation/good-received-note',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Receiving Exception',
    path: '/inbound-operation/receiving-exception',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Putaway Task',
    path: '/inbound-operation/putaway-task',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Cross Docking Task',
    path: '/inbound-operation/cross-docking-task',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Receiving Labor Tracking',
    path: '/inbound-operation/receiving-labor-tracking',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Receiving Dock',
    path: '/inbound-operation/receiving-dock',
  },
  {
    icon: <IoDocumentAttachOutline />,
    name: 'Receiving Equipment',
    path: '/inbound-operation/receiving-equipment',
  },
]

const AppSidebar: React.FC = () => {
  const { isExpanded, isMobileOpen, isHovered, setIsHovered } = useSidebar();
  const location = useLocation();

  const [openSubmenu, setOpenSubmenu] = useState<{
    type: "main" | "master" | "others";
    index: number;
  } | null>(null);
  const [subMenuHeight, setSubMenuHeight] = useState<Record<string, number>>(
    {}
  );
  const subMenuRefs = useRef<Record<string, HTMLDivElement | null>>({});

  // const isActive = (path: string) => location.pathname === path;
  const isActive = useCallback(
    (path: string) => location.pathname === path,
    [location.pathname]
  );

  useEffect(() => {
    let submenuMatched = false;
    ["main", "master", "others"].forEach((menuType) => {
      const items = menuType === "main" ? navItems : menuType === "master" ? masterItems : inboundItems;
      items.forEach((nav, index) => {
        if (nav.subItems) {
          nav.subItems.forEach((subItem) => {
            if (isActive(subItem.path)) {
              setOpenSubmenu({
                type: menuType as "main" | "master" | "others",
                index,
              });
              submenuMatched = true;
            }
          });
        }
      });
    });

    if (!submenuMatched) {
      setOpenSubmenu(null);
    }
  }, [location, isActive]);

  useEffect(() => {
    if (openSubmenu !== null) {
      const key = `${openSubmenu.type}-${openSubmenu.index}`;
      if (subMenuRefs.current[key]) {
        setSubMenuHeight((prevHeights) => ({
          ...prevHeights,
          [key]: subMenuRefs.current[key]?.scrollHeight || 0,
        }));
      }
    }
  }, [openSubmenu]);

  const handleSubmenuToggle = (index: number, menuType: "main" | "master" | "others") => {
    setOpenSubmenu((prevOpenSubmenu) => {
      if (
        prevOpenSubmenu &&
        prevOpenSubmenu.type === menuType &&
        prevOpenSubmenu.index === index
      ) {
        return null;
      }
      return { type: menuType, index };
    });
  };

  const renderMenuItems = (items: NavItem[], menuType: "main" | "master" | "others") => (
    <ul className="flex flex-col gap-4">
      {items.map((nav, index) => (
        <li key={nav.name}>
          {nav.subItems ? (
            <button
              onClick={() => handleSubmenuToggle(index, menuType)}
              className={`menu-item group ${
                openSubmenu?.type === menuType && openSubmenu?.index === index
                  ? "menu-item-active"
                  : "menu-item-inactive"
              } cursor-pointer ${
                !isExpanded && !isHovered
                  ? "lg:justify-center"
                  : "lg:justify-start"
              }`}
            >
              <span
                className={`menu-item-icon-size  ${
                  openSubmenu?.type === menuType && openSubmenu?.index === index
                    ? "menu-item-icon-active"
                    : "menu-item-icon-inactive"
                }`}
              >
                {nav.icon}
              </span>
              {(isExpanded || isHovered || isMobileOpen) && (
                <span className="menu-item-text">{nav.name}</span>
              )}
              {(isExpanded || isHovered || isMobileOpen) && (
                <ChevronDownIcon
                  className={`ml-auto w-5 h-5 transition-transform duration-200 ${
                    openSubmenu?.type === menuType &&
                    openSubmenu?.index === index
                      ? "rotate-180 text-brand-500"
                      : ""
                  }`}
                />
              )}
            </button>
          ) : (
            nav.path && (
              <Link
                to={nav.path}
                className={`menu-item group ${
                  isActive(nav.path) ? "menu-item-active" : "menu-item-inactive"
                }`}
              >
                <span
                  className={`menu-item-icon-size ${
                    isActive(nav.path)
                      ? "menu-item-icon-active"
                      : "menu-item-icon-inactive"
                  }`}
                >
                  {nav.icon}
                </span>
                {(isExpanded || isHovered || isMobileOpen) && (
                  <span className="menu-item-text">{nav.name}</span>
                )}
              </Link>
            )
          )}
          {nav.subItems && (isExpanded || isHovered || isMobileOpen) && (
            <div
              ref={(el) => {
                subMenuRefs.current[`${menuType}-${index}`] = el;
              }}
              className="overflow-hidden transition-all duration-300"
              style={{
                height:
                  openSubmenu?.type === menuType && openSubmenu?.index === index
                    ? `${subMenuHeight[`${menuType}-${index}`]}px`
                    : "0px",
              }}
            >
              <ul className="mt-2 space-y-1 ml-9">
                {nav.subItems.map((subItem) => (
                  <li key={subItem.name}>
                    <Link
                      to={subItem.path}
                      className={`menu-dropdown-item ${
                        isActive(subItem.path)
                          ? "menu-dropdown-item-active"
                          : "menu-dropdown-item-inactive"
                      }`}
                    >
                      {subItem.name}
                      <span className="flex items-center gap-1 ml-auto">
                        {subItem.new && (
                          <span
                            className={`ml-auto ${
                              isActive(subItem.path)
                                ? "menu-dropdown-badge-active"
                                : "menu-dropdown-badge-inactive"
                            } menu-dropdown-badge`}
                          >
                            new
                          </span>
                        )}
                        {subItem.pro && (
                          <span
                            className={`ml-auto ${
                              isActive(subItem.path)
                                ? "menu-dropdown-badge-active"
                                : "menu-dropdown-badge-inactive"
                            } menu-dropdown-badge`}
                          >
                            pro
                          </span>
                        )}
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </li>
      ))}
    </ul>
  );

  return (
    <aside
      className={`fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-50 border-r border-gray-200 
        ${
          isExpanded || isMobileOpen
            ? 'w-[290px]'
            : isHovered
            ? 'w-[290px]'
            : 'w-[90px]'
        }
        ${isMobileOpen ? 'translate-x-0' : '-translate-x-full'}
        lg:translate-x-0`}
      onMouseEnter={() => !isExpanded && setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div
        className={`pt-4 pb-2 flex ${
          !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start'
        }`}
      >
        <Link to="/">
          {isExpanded || isHovered || isMobileOpen ? (
            <>
              <img
                className="dark:hidden"
                src="/images/logo/logo-1-removebg.png"
                alt="Logo"
                width={150}
                height={40}
              />
              <img
                className="hidden dark:block"
                src="/images/logo/logo-dark.svg"
                alt="Logo"
                width={150}
                height={40}
              />
            </>
          ) : (
            <img
              src="/images/logo/logo-icon.svg"
              alt="Logo"
              width={32}
              height={32}
            />
          )}
        </Link>
      </div>
      <div className="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
        <nav className="mb-6">
          <div className="flex flex-col gap-4">
            <div>
              <h2
                className={`mb-4 text-xs uppercase flex leading-[20px] text-gray-400 ${
                  !isExpanded && !isHovered
                    ? 'lg:justify-center'
                    : 'justify-start'
                }`}
              >
                {isExpanded || isHovered || isMobileOpen ? (
                  ''
                ) : (
                  <HorizontaLDots className="size-6" />
                )}
              </h2>
              {renderMenuItems(navItems, 'main')}
            </div>
            <div>
              <h2
                className={`mb-4 text-xs uppercase flex leading-[20px] text-gray-400 ${
                  !isExpanded && !isHovered
                    ? 'lg:justify-center'
                    : 'justify-start'
                }`}
              >
                {isExpanded || isHovered || isMobileOpen ? (
                  'Master Data'
                ) : (
                  <HorizontaLDots className="size-6" />
                )}
              </h2>
              {renderMenuItems(masterItems, 'master')}
            </div>
            <div className="">
              <h2
                className={`mb-4 text-xs uppercase flex leading-[20px] text-gray-400 ${
                  !isExpanded && !isHovered
                    ? 'lg:justify-center'
                    : 'justify-start'
                }`}
              >
                {isExpanded || isHovered || isMobileOpen ? (
                  'Inbound Operation'
                ) : (
                  <HorizontaLDots />
                )}
              </h2>
              {renderMenuItems(inboundItems, 'others')}
            </div>
          </div>
        </nav>
        {/* {isExpanded || isHovered || isMobileOpen ? <SidebarWidget /> : null} */}
      </div>
    </aside>
  )
};

export default AppSidebar;
