import {
  BluetoothConnectedIcon,
  Bot,
  Factory,
  Map,
  ShoppingCart,
  Ticket,
  Truck,
  Settings2,
} from "lucide-react";

import {
  COMPANY_GROUP_PERMISSIONS,
  COMPANY_MEMBER_PERMISSIONS,
  COMPANY_PERMISSIONS,
  CUSTOM_FIELD_PERMISSIONS,
  FACTORY_PERMISSIONS,
  FINISHED_GOODS_RECEIPT_PERMISSIONS,
  HARVEST_PERMISSIONS,
  INSPECTOR_PERMISSIONS,
  LAND_PERMISSIONS,
  PLANT_PERMISSIONS,
  PRODUCT_TANK_PERMISSIONS,
  PRODUCT_TYPE_PERMISSIONS,
  PRODUCTION_ORDER_PERMISSIONS,
  RAW_MATERIAL_RELEASE_PERMISSIONS,
  RAW_MATERIAL_TANK_PERMISSIONS,
  SALES_CUSTOMER_PERMISSIONS,
  SALES_ISSUE_PERMISSIONS,
  SALES_ORDER_PERMISSIONS,
  TRANSACTION_TICKET_PERMISSIONS,
  TRANSPORTATION_ROUTE_PERMISSIONS,
  USER_PERMISSIONS,
  VEHICLE_PERMISSIONS,
  PALLET_PERMISSIONS,
  PRICE_PERMISSIONS,
} from "@/lib/permissions";
import { MdManageAccounts } from "react-icons/md";
import { MenuItem } from "@/components/app-sidebar";

export const withAllPermissions = (...modules: Record<string, string>[]) =>
  Array.from(new Set(modules.flatMap(Object.values)));

export const pickByIncludes = (
  permissions: Record<string, string>,
  keywords: string[],
) =>
  Object.values(permissions).filter(
    (p) => keywords.some((k) => p.includes(k)) || p.endsWith(".*"),
  );

export const menuConfig = (
  isFarmer?: boolean,
  isInspector?: boolean,
  isCompany?: boolean,
  isAdmin?: boolean,
): MenuItem[] => {
  return [
    {
      title: "manage_land",
      url: "#",
      icon: Map,
      requiredPermissions: withAllPermissions(
        LAND_PERMISSIONS,
        PLANT_PERMISSIONS,
      ),
      items: [
        {
          title: "land_list",
          url: "/land/land-list",
          requiredPermissions: Object.values(LAND_PERMISSIONS),
        },
        {
          title: "map",
          url: "/land/map",
          requiredPermissions: Object.values(LAND_PERMISSIONS),
        },
        {
          title: "plants",
          url: "/land/plants",
          requiredPermissions: Object.values(PLANT_PERMISSIONS),
        },
      ],
    },
    {
      title: "farmer",
      url: "#",
      icon: Bot,
      requiredPermissions: withAllPermissions(
        USER_PERMISSIONS,
        HARVEST_PERMISSIONS,
      ),
      items: [
        {
          title: "harvest_plan",
          url: "/farmer/harvest-plan",
          requiredPermissions: Object.values(HARVEST_PERMISSIONS).filter((p) =>
            p.includes("harvest_plan."),
          ),
        },
        {
          title: "harvest",
          url: "/farmer/harvest",
          requiredPermissions: Object.values(HARVEST_PERMISSIONS).filter(
            (p) =>
              p.includes("harvest_schedule.") || p.includes("harvest_result."),
          ),
        },
      ],
    },
    {
      title: "voucher",
      url: "/voucher",
      icon: Ticket,
      requiredPermissions: withAllPermissions(TRANSACTION_TICKET_PERMISSIONS),
      items: [
        {
          title: "purchase_voucher",
          url: "/voucher/manage-purchase",
          requiredPermissions: pickByIncludes(TRANSACTION_TICKET_PERMISSIONS, [
            ".purchase.",
          ]),
        },
        {
          title: "thu_mua",
          url: "/voucher/purchase",
          requiredPermissions: pickByIncludes(TRANSACTION_TICKET_PERMISSIONS, [
            ".purchase.",
          ]),
        },
        {
          title: "sale_voucher",
          url: "/voucher/manage-sale",
          requiredPermissions: pickByIncludes(TRANSACTION_TICKET_PERMISSIONS, [
            ".sale.",
          ]),
        },
      ],
    },
    {
      title: "connection",
      url: "#",
      icon: BluetoothConnectedIcon,
      items: [
        {
          title: "manage_connection",
          url: "/connection",
        },
        {
          title: "statistics_connect",
          url: "/connection/statistics-connect",
        },
        {
          title: isFarmer ? "statistics_share" : "statistics_share_garden",
          url: "/connection/statistics-share",
        },
      ],
    },
    {
      title: "transportation_route",
      url: "#",
      icon: Truck,
      requiredPermissions: withAllPermissions(
        TRANSPORTATION_ROUTE_PERMISSIONS,
        VEHICLE_PERMISSIONS,
      ),
      items: [
        {
          title: "route",
          url: "/route-manage",
          requiredPermissions: Object.values(TRANSPORTATION_ROUTE_PERMISSIONS),
        },
        {
          title: "vehicle",
          url: "/route-manage/vehicle",
          requiredPermissions: Object.values(VEHICLE_PERMISSIONS),
        },
      ],
    },
    {
      title: "factory",
      url: "#",
      icon: Factory,
      requiredPermissions: withAllPermissions(
        FACTORY_PERMISSIONS,
        PRODUCT_TYPE_PERMISSIONS,
        PRODUCT_TANK_PERMISSIONS,
        RAW_MATERIAL_TANK_PERMISSIONS,
        RAW_MATERIAL_RELEASE_PERMISSIONS,
        PRODUCTION_ORDER_PERMISSIONS,
        FINISHED_GOODS_RECEIPT_PERMISSIONS,
        PALLET_PERMISSIONS,
      ),
      items: [
        {
          title: "factory",
          url: "/factory",
          requiredPermissions: withAllPermissions(
            FACTORY_PERMISSIONS,
            PRODUCT_TYPE_PERMISSIONS,
            PRODUCT_TANK_PERMISSIONS,
            RAW_MATERIAL_TANK_PERMISSIONS,
          ),
        },
        {
          title: "receive_material",
          url: "/factory/receive-material",
          requiredPermissions: Object.values(RAW_MATERIAL_RELEASE_PERMISSIONS),
        },
        {
          title: "production",
          url: "/factory/production",
          requiredPermissions: Object.values(PRODUCTION_ORDER_PERMISSIONS),
        },
        {
          title: "production_management",
          url: "/factory/production-management",
          requiredPermissions: withAllPermissions(
            RAW_MATERIAL_TANK_PERMISSIONS,
            PRODUCTION_ORDER_PERMISSIONS,
            FINISHED_GOODS_RECEIPT_PERMISSIONS,
            PALLET_PERMISSIONS,
          ),
        },
        {
          title: "external_material",
          url: "/factory/external-material",
          requiredPermissions: Object.values(RAW_MATERIAL_RELEASE_PERMISSIONS),
        },
        {
          title: "manage_production_ticket",
          url: "/factory/ticket-product",
          requiredPermissions: Object.values(PRODUCTION_ORDER_PERMISSIONS),
        },
        {
          title: "manage_bale",
          url: "/factory/fg-receipt-summary",
          requiredPermissions: Object.values(
            FINISHED_GOODS_RECEIPT_PERMISSIONS,
          ),
        },
        {
          title: "manage_lot",
          url: "/factory/lot-container",
          requiredPermissions: Object.values(
            FINISHED_GOODS_RECEIPT_PERMISSIONS,
          ),
        },
      ],
    },
    {
      title: "sale",
      url: "#",
      icon: ShoppingCart,
      requiredPermissions: withAllPermissions(
        SALES_CUSTOMER_PERMISSIONS,
        SALES_ORDER_PERMISSIONS,
        SALES_ISSUE_PERMISSIONS,
        PALLET_PERMISSIONS,
        PRICE_PERMISSIONS,
      ),
      items: [
        {
          title: "customers",
          url: "/trader/customers",
          requiredPermissions: Object.values(SALES_CUSTOMER_PERMISSIONS),
        },
        {
          title: "sales_orders",
          url: "/trader/orders",
          requiredPermissions: Object.values(SALES_ORDER_PERMISSIONS),
        },
        {
          title: "purchase_orders",
          url: "/trader/purchase-orders",
          requiredPermissions: Object.values(SALES_ORDER_PERMISSIONS),
        },
        {
          title: "delivery_note",
          url: "/trader/delivery-note",
          requiredPermissions: Object.values(SALES_ISSUE_PERMISSIONS),
        },
        {
          title: "external_product_lot",
          url: "/trader/product-lot",
          requiredPermissions: Object.values(SALES_ORDER_PERMISSIONS),
        },
        {
          title: "inventory",
          url: "/trader/inventory",
          requiredPermissions: Object.values(SALES_ORDER_PERMISSIONS),
        },
        {
          title: "packing_price",
          url: "/trader/packing-price",
          requiredPermissions: withAllPermissions(
            PALLET_PERMISSIONS,
            PRICE_PERMISSIONS,
          ),
        },
      ],
    },
    {
      title: "custom_field",
      url: "/custom-field",
      icon: Settings2,
      // requiredPermissions: Object.values(CUSTOM_FIELD_PERMISSIONS),
    },
    {
      title: "system_management",
      url: "#",
      icon: MdManageAccounts,
      requiredPermissions: withAllPermissions(
        COMPANY_PERMISSIONS,
        COMPANY_GROUP_PERMISSIONS,
        COMPANY_MEMBER_PERMISSIONS,
      ),
      items: [
        {
          title: "company",
          url: "/management/company",
          requiredPermissions: Object.values(COMPANY_PERMISSIONS),
        },
        {
          title: "grade",
          url: "/management/grade",
          // requiredPermissions: Object.values(COMPANY_PERMISSIONS),
        },
        {
          title: "group_permissions",
          url: "/management/group-permission",
          requiredPermissions: Object.values(COMPANY_GROUP_PERMISSIONS),
        },
        {
          title: "users",
          url: "/management/user-management",
          requiredPermissions: Object.values(COMPANY_MEMBER_PERMISSIONS),
        },
        {
          title: "general_settings",
          url: "/management/general-settings",
          requiredPermissions: Object.values(COMPANY_MEMBER_PERMISSIONS),
        },
      ],
    },
    isInspector && {
      title: "land_support",
      url: "#",
      icon: Map,
      requiredPermissions: Object.values(INSPECTOR_PERMISSIONS),
      items: [
        {
          title: "create_land",
          url: "/support/land",
          requiredPermissions: Object.values(INSPECTOR_PERMISSIONS),
        },
      ],
    },
  ].filter(Boolean) as MenuItem[];
};
